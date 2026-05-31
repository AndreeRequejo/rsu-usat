<?php

use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Flux\Flux;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterContractType = '';
    public string $filterStatus = 'all';
    public string $searchInput = '';
    public string $filterContractTypeInput = '';
    public string $filterStatusInput = 'all';
    public bool $showFormModal = false;
    public ?int $editingId = null;
    public ?int $deactivatingId = null;

    public string $employeeSearch = '';
    public ?int $employee_id = null;
    public string $contract_type = '';
    public string $start_date = '';
    public string $end_date = '';
    public string $salary = '';
    public ?int $probation_period_months = null;
    public bool $is_active = true;
    public ?int $department_id = null;
    public int $employeePage = 1;
    public int $employeesPerPage = 5;

    private const CONTRACT_TYPES = ['Permanente', 'Nombrado', 'Temporal'];

    protected function rules(): array
    {
        $endDateRules = ['nullable', 'date', 'after_or_equal:start_date'];
        if ($this->contract_type === 'Temporal') {
            array_unshift($endDateRules, 'required');
        }

        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'contract_type' => ['required', Rule::in(self::CONTRACT_TYPES)],
            'start_date' => ['required', 'date'],
            'end_date' => $endDateRules,
            'salary' => ['required', 'numeric', 'min:0'],
            'probation_period_months' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ];
    }

    protected function messages(): array
    {
        return [
            'employee_id.required' => __('El personal es obligatorio.'),
            'employee_id.exists' => __('El personal seleccionado no existe.'),
            'contract_type.required' => __('El tipo de contrato es obligatorio.'),
            'contract_type.in' => __('El tipo de contrato no es valido.'),
            'start_date.required' => __('La fecha de inicio es obligatoria.'),
            'start_date.date' => __('La fecha de inicio no es valida.'),
            'end_date.required' => __('La fecha de finalizacion es obligatoria para contratos temporales.'),
            'end_date.date' => __('La fecha de finalizacion no es valida.'),
            'end_date.after_or_equal' => __('La fecha de finalizacion debe ser posterior a la fecha de inicio.'),
            'salary.required' => __('El salario es obligatorio.'),
            'salary.numeric' => __('El salario debe ser un numero valido.'),
            'salary.min' => __('El salario no puede ser negativo.'),
            'probation_period_months.integer' => __('El periodo de prueba debe ser un numero entero.'),
            'probation_period_months.min' => __('El periodo de prueba no puede ser negativo.'),
            'department_id.exists' => __('El departamento seleccionado no existe.'),
        ];
    }

    #[Computed]
    public function contracts()
    {
        return Contract::query()
            ->with(['employee.employeeType', 'employee.user'])
            ->when($this->search !== '', function ($query) {
                $query->where('contract_type', 'like', '%' . $this->search . '%')
                    ->orWhereHas('employee', function ($employeeQuery) {
                        $employeeQuery->where('dni', 'like', '%' . $this->search . '%')
                            ->orWhere('first_name', 'like', '%' . $this->search . '%')
                            ->orWhere('last_name', 'like', '%' . $this->search . '%');
                    });
            })
            ->when($this->filterContractType !== '', function ($query) {
                $query->where('contract_type', $this->filterContractType);
            })
            ->when($this->filterStatus === 'active', function ($query) {
                $query->where('is_active', true);
            })
            ->when($this->filterStatus === 'inactive', function ($query) {
                $query->where('is_active', false);
            })
            ->orderByDesc('start_date')
            ->paginate(10);
    }

    #[Computed]
    public function employeesForSelect()
    {
        if (mb_strlen($this->employeeSearch) < 2 && !$this->employee_id) {
            return collect();
        }

        $query = $this->employeeSearchQuery();
        $offset = ($this->employeePage - 1) * $this->employeesPerPage;

        return $query
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->skip($offset)
            ->take($this->employeesPerPage)
            ->get();
    }

    #[Computed]
    public function employeesForSelectTotal(): int
    {
        if (mb_strlen($this->employeeSearch) < 2 && !$this->employee_id) {
            return 0;
        }

        return $this->employeeSearchQuery()->count();
    }

    #[Computed]
    public function departments()
    {
        return Department::orderBy('name')->get();
    }

    #[Computed]
    public function contractTypes()
    {
        return self::CONTRACT_TYPES;
    }

    public function applyFilters(): void
    {
        $this->search = $this->searchInput;
        $this->filterContractType = $this->filterContractTypeInput;
        $this->filterStatus = $this->filterStatusInput;
        $this->resetPage();
    }

    public function updatedContractType(): void
    {
        if ($this->contract_type !== 'Temporal') {
            $this->end_date = '';
        }
    }

    public function updatedEmployeeSearch(): void
    {
        $this->employeePage = 1;
        if ($this->employeeSearch === '') {
            $this->employee_id = null;
        }
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->contract_type = '';
        $this->is_active = true;
        $this->department_id = null;
        $this->showFormModal = true;
        Flux::modal('contract-form')->show();
    }

    public function openEdit(int $id): void
    {
        $contract = Contract::with('employee')->findOrFail($id);
        $this->editingId = $id;
        $this->employee_id = $contract->employee_id;
        if ($contract->employee) {
            $this->employeeSearch = $contract->employee->last_name . ' ' . $contract->employee->first_name . ' - ' . $contract->employee->dni;
        }
        $this->contract_type = $contract->contract_type;
        $this->start_date = $contract->start_date?->format('Y-m-d') ?? '';
        $this->end_date = $contract->end_date?->format('Y-m-d') ?? '';
        $this->salary = (string) $contract->salary;
        $this->probation_period_months = $contract->probation_period_months;
        $this->is_active = (bool) $contract->is_active;
        $this->department_id = $contract->department_id;
        $this->showFormModal = true;
        Flux::modal('contract-form')->show();
    }

    public function closeFormModal(): void
    {
        $this->resetForm();
        $this->showFormModal = false;
        Flux::modal('contract-form')->close();
    }

    public function save(): void
    {
        $validated = $this->validate();

        $newStart = Carbon::parse($validated['start_date']);
        $newEnd = !empty($validated['end_date']) ? Carbon::parse($validated['end_date']) : null;

        $conflictingContract = Contract::query()
            ->where('employee_id', $validated['employee_id'])
            ->where('is_active', true)
            ->when($this->editingId, function ($query) {
                $query->where('id', '!=', $this->editingId);
            })
            ->where(function ($query) use ($newStart) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $newStart->toDateString());
            })
            ->when($newEnd, function ($query) use ($newEnd) {
                $query->where('start_date', '<=', $newEnd->toDateString());
            })
            ->first();

        if ($conflictingContract) {
            $conflictStart = Carbon::parse($conflictingContract->start_date)->format('d/m/Y');
            if ($conflictingContract->end_date) {
                $conflictEnd = Carbon::parse($conflictingContract->end_date)->format('d/m/Y');
                $message = __('El empleado ya tiene un contrato activo del :start al :end (:type).', [
                    'start' => $conflictStart,
                    'end' => $conflictEnd,
                    'type' => $conflictingContract->contract_type,
                ]);
            } else {
                $message = __('El empleado ya tiene un contrato activo desde :start (:type).', [
                    'start' => $conflictStart,
                    'type' => $conflictingContract->contract_type,
                ]);
            }

            Flux::toast(variant: 'warning', text: $message);
            return;
        }

        $payload = [
            'employee_id' => $validated['employee_id'],
            'contract_type' => $validated['contract_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['contract_type'] === 'Temporal' ? $validated['end_date'] : null,
            'salary' => $validated['salary'],
            'probation_period_months' => $validated['probation_period_months'] ?? 0,
            'is_active' => $validated['is_active'],
            'department_id' => $validated['department_id'] ?? null,
        ];

        if ($this->editingId) {
            $contract = Contract::findOrFail($this->editingId);
            $contract->update($payload);
            Flux::toast(variant: 'success', text: __('Contrato actualizado correctamente.'));
        } else {
            Contract::create($payload);
            Flux::toast(variant: 'success', text: __('Contrato registrado correctamente.'));
        }

        $this->closeFormModal();
    }

    public function selectEmployee(int $employeeId): void
    {
        $employee = Employee::find($employeeId);
        if (!$employee) {
            return;
        }

        $this->employee_id = $employee->id;
        $this->employeeSearch = $employee->last_name . ' ' . $employee->first_name . ' - ' . $employee->dni;
    }

    public function nextEmployeePage(): void
    {
        $total = $this->employeesForSelectTotal;
        $maxPage = max(1, (int) ceil($total / $this->employeesPerPage));
        $this->employeePage = min($this->employeePage + 1, $maxPage);
    }

    public function prevEmployeePage(): void
    {
        $this->employeePage = max(1, $this->employeePage - 1);
    }

    private function employeeSearchQuery(): Builder
    {
        $query = Employee::query()->with('employeeType');

        if (mb_strlen($this->employeeSearch) >= 2) {
            $query->where('active', true)
                ->where(function ($employeeQuery) {
                    $employeeQuery->where('dni', 'like', '%' . $this->employeeSearch . '%')
                        ->orWhere('first_name', 'like', '%' . $this->employeeSearch . '%')
                        ->orWhere('last_name', 'like', '%' . $this->employeeSearch . '%');
                });
        } elseif ($this->employee_id) {
            $query->where('id', $this->employee_id);
        }

        return $query;
    }

    public function confirmDeactivate(int $id): void
    {
        $this->deactivatingId = $id;
        Flux::modal('confirm-deactivate')->show();
    }

    public function deactivate(): void
    {
        if (!$this->deactivatingId) return;

        $contract = Contract::findOrFail($this->deactivatingId);
        if (!$contract->is_active) {
            Flux::toast(variant: 'warning', text: __('El contrato ya esta inactivo.'));
        } else {
            $contract->update(['is_active' => false]);
            Flux::toast(variant: 'success', text: __('Contrato desactivado correctamente.'));
        }

        $this->deactivatingId = null;
        Flux::modal('confirm-deactivate')->close();
    }

    private function resetForm(): void
    {
        $this->reset([
            'employeeSearch',
            'employee_id',
            'contract_type',
            'start_date',
            'end_date',
            'salary',
            'probation_period_months',
            'is_active',
            'department_id',
            'editingId',
            'employeePage',
        ]);
        $this->resetErrorBag();
        $this->resetValidation();
    }
}; ?>

<div class="min-h-screen bg-white p-6 text-[#333333]">
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-[#2E8B57]">
                {{ __('Gestion de contratos') }}
            </h1>
            <p class="text-sm text-[#333333] mt-1">
                {{ __('Administracion de contratos registrados del personal.') }}
            </p>
        </div>
        <flux:button wire:click="openCreate" variant="primary" icon="plus-circle" class="bg-[#2E8B57] text-white">
            {{ __('Nuevo Contrato') }}
        </flux:button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] p-5 mb-6">
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-sm font-medium text-[#333333] mb-2">
                    {{ __('Buscar') }}
                </label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-[#333333]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="searchInput"
                        placeholder="{{ __('DNI, nombre o tipo...') }}"
                        class="w-full pl-10 pr-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                    />
                </div>
            </div>

            <div class="min-w-[200px]">
                <label class="block text-sm font-medium text-[#333333] mb-2">
                    {{ __('Tipo de contrato') }}
                </label>
                <select
                    wire:model="filterContractTypeInput"
                    class="w-full rounded-lg border border-[#A5D6A7] bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                >
                    <option value="">Todos</option>
                    @foreach ($this->contractTypes as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-[180px]">
                <label class="block text-sm font-medium text-[#333333] mb-2">
                    {{ __('Estado') }}
                </label>
                <select
                    wire:model="filterStatusInput"
                    class="w-full rounded-lg border border-[#A5D6A7] bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                >
                    <option value="all">Todos</option>
                    <option value="active">Activos</option>
                    <option value="inactive">Inactivos</option>
                </select>
            </div>

            <div class="min-w-[140px]">
                <button
                    type="button"
                    wire:click="applyFilters"
                    class="w-full px-6 py-2.5 bg-[#2E8B57] text-white text-sm font-medium rounded-lg"
                >
                    {{ __('Filtrar') }}
                </button>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] overflow-hidden">
        @include('pages.personnel.contracts.components.table')
    </div>

    @include('pages.personnel.contracts.components.form')
    @include('pages.personnel.contracts.components.deactivate')
</div>
