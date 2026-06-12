<?php

use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\Scheduling;
use App\Models\Shift;
use App\Models\StaffGroup;
use App\Models\Vehicle;
use App\Models\Zone;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Flux\Flux;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public ?int $zone_id = null;
    public ?int $shift_id = null;
    public ?int $vehicle_id = null;
    public ?int $driver_id = null;
    public ?int $helper_one_id = null;
    public ?int $helper_two_id = null;
    public array $work_days = [];
    public bool $active = true;

    public ?int $deletingId = null;

    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('staff_groups', 'name')->ignore($this->editingId),
            ],
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'driver_id' => ['required', 'integer', 'exists:employees,id'],
            'helper_one_id' => [
                'nullable', 'integer', 'exists:employees,id',
                function ($attribute, $value, $fail) {
                    if ($value && $value === $this->driver_id) {
                        $fail(__('El ayudante 1 no puede ser el conductor.'));
                    }
                    if ($value && $value === $this->helper_two_id) {
                        $fail(__('El ayudante 1 y ayudante 2 no pueden ser la misma persona.'));
                    }
                },
            ],
            'helper_two_id' => [
                'nullable', 'integer', 'exists:employees,id',
                function ($attribute, $value, $fail) {
                    if ($value && $value === $this->driver_id) {
                        $fail(__('El ayudante 2 no puede ser el conductor.'));
                    }
                },
            ],
            'work_days' => ['required', 'array', 'min:1'],
            'work_days.*' => ['integer', 'in:1,2,3,4,5,6,7'],
            'active' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => __('El nombre es obligatorio.'),
            'name.unique' => __('Ya existe un grupo con ese nombre.'),
            'name.max' => __('El nombre no puede tener mas de 120 caracteres.'),
            'zone_id.required' => __('La zona es obligatoria.'),
            'zone_id.exists' => __('La zona seleccionada no es valida.'),
            'shift_id.required' => __('El turno es obligatorio.'),
            'shift_id.exists' => __('El turno seleccionado no es valido.'),
            'vehicle_id.required' => __('El vehiculo es obligatorio.'),
            'vehicle_id.exists' => __('El vehiculo seleccionado no es valido.'),
            'driver_id.required' => __('El conductor es obligatorio.'),
            'driver_id.exists' => __('El conductor seleccionado no es valido.'),
            'helper_one_id.exists' => __('El ayudante 1 seleccionado no es valido.'),
            'helper_two_id.exists' => __('El ayudante 2 seleccionado no es valido.'),
            'work_days.required' => __('Seleccione al menos un dia de trabajo.'),
            'work_days.min' => __('Seleccione al menos un dia de trabajo.'),
        ];
    }

    public function save(): void
    {
        $this->validate();

        $errors = array_merge(
            $this->validateEmployeeContracts(),
            $this->validateEmployeeOverlap(),
            $this->validateZoneOverlap(),
            $this->validateVehicleAvailability(),
        );

        foreach ($errors as $field => $message) {
            $this->addError($field, $message);
        }

        if (!empty($errors)) return;

        $data = [
            'name' => $this->name,
            'zone_id' => $this->zone_id,
            'shift_id' => $this->shift_id,
            'vehicle_id' => $this->vehicle_id,
            'driver_id' => $this->driver_id,
            'helper_one_id' => $this->helper_one_id,
            'helper_two_id' => $this->helper_two_id,
            'work_days' => $this->work_days,
            'active' => $this->active,
        ];

        if ($this->editingId) {
            $group = StaffGroup::findOrFail($this->editingId);
            $group->update($data);
            Flux::toast(variant: 'success', text: __('Grupo actualizado.'));
        } else {
            StaffGroup::create($data);
            Flux::toast(variant: 'success', text: __('Grupo creado.'));
        }

        $this->resetForm();
        $this->showModal = false;
        Flux::modal('staff-group-form')->close();
    }

    private function validateEmployeeContracts(): array
    {
        $errors = [];
        $employeeIds = array_filter([$this->driver_id, $this->helper_one_id, $this->helper_two_id]);
        if (empty($employeeIds)) return $errors;

        $employees = Employee::with('contracts')->whereIn('id', $employeeIds)->get();

        foreach ($employees as $employee) {
            $hasActiveContract = $employee->contracts
                ->first(fn ($c) => $c->isEffectivelyActive());

            if (!$hasActiveContract) {
                $field = match ($employee->id) {
                    $this->driver_id => 'driver_id',
                    $this->helper_one_id => 'helper_one_id',
                    $this->helper_two_id => 'helper_two_id',
                };
                $errors[$field] = __('El empleado :name no tiene un contrato activo.', [
                    'name' => $employee->first_name.' '.$employee->last_name,
                ]);
            }
        }
        return $errors;
    }

    private function validateEmployeeOverlap(): array
    {
        $errors = [];
        if (!$this->shift_id || empty($this->work_days)) return $errors;

        $selectedIds = array_filter([$this->driver_id, $this->helper_one_id, $this->helper_two_id]);
        if (empty($selectedIds)) return $errors;

        $overlapGroups = StaffGroup::where('shift_id', $this->shift_id)
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->get(['id', 'driver_id', 'helper_one_id', 'helper_two_id', 'work_days']);

        foreach ($overlapGroups as $group) {
            $groupDays = is_array($group->work_days) ? $group->work_days : [];
            $dayOverlap = array_intersect($this->work_days, $groupDays);
            if (empty($dayOverlap)) continue;

            $groupEmployeeIds = array_filter([
                $group->driver_id, $group->helper_one_id, $group->helper_two_id,
            ]);
            $conflicts = array_intersect($groupEmployeeIds, $selectedIds);
            if (empty($conflicts)) continue;

            foreach ($conflicts as $employeeId) {
                $employee = Employee::find($employeeId);
                if (!$employee) continue;
                $field = match ($employeeId) {
                    $this->driver_id => 'driver_id',
                    $this->helper_one_id => 'helper_one_id',
                    $this->helper_two_id => 'helper_two_id',
                };
                $errors[$field] = __('El empleado :name ya esta asignado a otro grupo en el mismo turno con dias que se cruzan.', [
                    'name' => $employee->first_name.' '.$employee->last_name,
                ]);
            }
        }
        return $errors;
    }

    private function validateZoneOverlap(): array
    {
        $errors = [];
        if (!$this->zone_id || !$this->shift_id || empty($this->work_days)) return $errors;

        $overlap = StaffGroup::where('zone_id', $this->zone_id)
            ->where('shift_id', $this->shift_id)
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->get(['id', 'work_days']);

        foreach ($overlap as $group) {
            $groupDays = is_array($group->work_days) ? $group->work_days : [];
            $dayOverlap = array_intersect($this->work_days, $groupDays);
            if (!empty($dayOverlap)) {
                $errors['zone_id'] = __('Esta zona ya esta asignada a otro grupo en el mismo turno con dias que se cruzan.');
                return $errors;
            }
        }
        return $errors;
    }

    private function validateVehicleAvailability(): array
    {
        $errors = [];
        if (!$this->vehicle_id || !$this->shift_id || empty($this->work_days)) return $errors;

        $vehicle = Vehicle::find($this->vehicle_id);
        if (!$vehicle) return $errors;

        if (!$vehicle->status) {
            $errors['vehicle_id'] = __('El vehiculo seleccionado no esta activo.');
            return $errors;
        }

        $overlap = StaffGroup::where('vehicle_id', $this->vehicle_id)
            ->where('shift_id', $this->shift_id)
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->get(['id', 'work_days']);

        foreach ($overlap as $group) {
            $groupDays = is_array($group->work_days) ? $group->work_days : [];
            $dayOverlap = array_intersect($this->work_days, $groupDays);
            if (!empty($dayOverlap)) {
                $errors['vehicle_id'] = __('El vehiculo ya esta asignado a otro grupo en este turno con dias que se cruzan.');
                return $errors;
            }
        }
        return $errors;
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
        Flux::modal('staff-group-form')->show();
    }

    public function openEdit(int $id): void
    {
        $group = StaffGroup::findOrFail($id);
        $this->editingId = $group->id;
        $this->name = $group->name;
        $this->zone_id = $group->zone_id;
        $this->shift_id = $group->shift_id;
        $this->vehicle_id = $group->vehicle_id;
        $this->driver_id = $group->driver_id;
        $this->helper_one_id = $group->helper_one_id;
        $this->helper_two_id = $group->helper_two_id;
        $this->work_days = $group->work_days ?? [];
        $this->active = $group->active;
        $this->showModal = true;
        Flux::modal('staff-group-form')->show();
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->showModal = false;
        Flux::modal('staff-group-form')->close();
    }

    public function confirmDelete(int $id): void
    {
        $group = StaffGroup::findOrFail($id);
        $activeCount = $this->activeSchedulingCount($group);
        if ($activeCount > 0) {
            Flux::toast(
                variant: 'warning',
                text: __('No se puede eliminar este grupo porque tiene :count programacion(es) activa(s).', ['count' => $activeCount])
            );
            return;
        }
        $this->deletingId = $id;
        Flux::modal('confirm-delete')->show();
    }

    public function delete(): void
    {
        if (!$this->deletingId) return;

        $group = StaffGroup::findOrFail($this->deletingId);
        $activeCount = $this->activeSchedulingCount($group);
        if ($activeCount > 0) {
            Flux::toast(
                variant: 'warning',
                text: __('No se puede eliminar este grupo porque tiene :count programacion(es) activa(s).', ['count' => $activeCount])
            );
            $this->deletingId = null;
            Flux::modal('confirm-delete')->close();
            return;
        }
        $group->delete();
        Flux::toast(variant: 'success', text: __('Grupo eliminado.'));

        $this->deletingId = null;
        Flux::modal('confirm-delete')->close();
    }

    private function activeSchedulingCount(StaffGroup $group): int
    {
        $employeeIds = array_filter([
            $group->driver_id,
            $group->helper_one_id,
            $group->helper_two_id,
        ]);
        if (empty($employeeIds)) return 0;

        return Scheduling::whereDate('date', '>=', now())
            ->whereIn('status', ['Programado', 'En curso'])
            ->whereHas('groupDetails', function ($q) use ($employeeIds) {
                $q->whereIn('employee_id', $employeeIds);
            })
            ->count();
    }

    #[Computed]
    public function staffGroups()
    {
        return StaffGroup::query()
            ->with(['zone', 'shift', 'vehicle', 'driver', 'helperOne', 'helperTwo'])
            ->when($this->search !== '', function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhereHas('zone', function ($q) {
                        $q->where('name', 'like', '%'.$this->search.'%');
                    })
                    ->orWhereHas('shift', function ($q) {
                        $q->where('name', 'like', '%'.$this->search.'%');
                    })
                    ->orWhereHas('driver', function ($q) {
                        $q->where('first_name', 'like', '%'.$this->search.'%')
                            ->orWhere('last_name', 'like', '%'.$this->search.'%');
                    });
            })
            ->orderBy('name')
            ->paginate(10);
    }

    #[Computed]
    public function zones()
    {
        return Zone::orderBy('name')->get();
    }

    #[Computed]
    public function shifts()
    {
        return Shift::orderBy('name')->get();
    }

    #[Computed]
    public function vehicles()
    {
        return Vehicle::orderBy('name')->get();
    }

    #[Computed]
    public function drivers()
    {
        $driverType = EmployeeType::where('name', 'Conductor')->first();
        if (!$driverType) return collect();
        return Employee::where('employee_type_id', $driverType->id)
            ->where('active', true)
            ->whereHas('contracts', fn ($q) => $q->where('is_active', true))
            ->orderBy('first_name')
            ->get();
    }

    #[Computed]
    public function helpers()
    {
        $helperType = EmployeeType::where('name', 'Ayudante')->first();
        if (!$helperType) return collect();
        return Employee::where('employee_type_id', $helperType->id)
            ->where('active', true)
            ->whereHas('contracts', fn ($q) => $q->where('is_active', true))
            ->orderBy('first_name')
            ->get();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    private function resetForm(): void
    {
        $this->reset([
            'name', 'zone_id', 'shift_id', 'vehicle_id',
            'driver_id', 'helper_one_id', 'helper_two_id',
            'work_days', 'editingId',
        ]);
        $this->active = true;
        $this->resetErrorBag();
        $this->resetValidation();
    }
}; ?>

<div class="min-h-screen bg-white p-6 text-[#333333]">
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-[#2E8B57]">
                {{ __('Gestion de Grupos de Personal') }}
            </h1>
            <p class="text-sm text-[#333333] mt-1">
                {{ __('Administracion de grupos de personal para la recoleccion de basura.') }}
            </p>
        </div>

        <flux:button
            wire:click="openCreate"
            variant="primary"
            icon="plus-circle"
            class="bg-[#2E8B57]! text-white cursor-pointer hover:bg-[#257046]!"
        >
            {{ __('Nuevo Grupo') }}
        </flux:button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] p-5 mb-6">
        <label class="block text-sm font-medium text-[#333333] mb-2">
            {{ __('Buscar por nombre, zona, turno o nombre o apellido del conductor') }}
        </label>
        <div class="flex gap-3">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-[#333333]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Buscar...') }}"
                    class="w-full pl-10 pr-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                />
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-[#2E8B57] text-white text-xs font-bold uppercase tracking-wider">
                        <th class="px-4 py-4 text-left">{{ __('Nombre') }}</th>
                        <th class="px-4 py-4 text-left">{{ __('Zona') }}</th>
                        <th class="px-4 py-4 text-left">{{ __('Turno') }}</th>
                        <th class="px-4 py-4 text-left">{{ __('Vehiculo') }}</th>
                        <th class="px-4 py-4 text-left">{{ __('Conductor') }}</th>
                        <th class="px-4 py-4 text-left">{{ __('Ayudantes') }}</th>
                        <th class="px-4 py-4 text-center">{{ __('Dias') }}</th>
                        <th class="px-4 py-4 text-center">{{ __('Estado') }}</th>
                        <th class="px-4 py-4 text-right">{{ __('Acciones') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->staffGroups as $i => $group)
                        <tr wire:key="staff-group-{{ $group->id }}"
                            class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-[#A5D6A7]/20' }} border-b border-[#A5D6A7] hover:bg-[#A5D6A7]/30 transition">
                            <td class="px-4 py-4 text-sm font-bold text-[#333333] uppercase whitespace-nowrap">
                                {{ $group->name }}
                            </td>
                            <td class="px-4 py-4 text-sm text-[#333333] whitespace-nowrap">
                                {{ $group->zone?->name ?? '---' }}
                            </td>
                            <td class="px-4 py-4 text-sm text-[#333333] whitespace-nowrap">
                                {{ $group->shift?->name ?? '---' }}
                            </td>
                            <td class="px-4 py-4 text-sm text-[#333333] whitespace-nowrap">
                                {{ $group->vehicle?->name ?? '---' }}
                            </td>
                            <td class="px-4 py-4  text-sm text-[#333333] whitespace-nowrap">
                                <div>{{ $group->driver?->first_name ?? '' }} </div> <div> {{ $group->driver?->last_name ?? '---' }}</div>
                            </td>
                            <td class="px-4 py-4 space-y-1 text-sm text-[#333333]">
                                @if($group->helperOne)
                                    <div class="rounded bg-gray-100 px-2 py-1 text-xs" >{{ $group->helperOne->first_name }} {{ $group->helperOne->last_name }}</div>
                                @endif

                                @if($group->helperTwo)
                                    <div class="rounded bg-gray-100 px-2 py-1 text-xs" >{{ $group->helperTwo->first_name }} {{ $group->helperTwo->last_name }}</div>
                                @endif

                                @unless($group->helperOne || $group->helperTwo)
                                    ---
                                @endunless
                            </td>
                            <td class="px-4 py-4 text-center whitespace-nowrap">
                                <div class="grid grid-cols-4 space-x-1 space-y-1 gap-0.5 justify-items-center">
                                    @php
                                        $days = $group->work_days ?? [];
                                        $labels = [1 => 'L', 2 => 'M', 3 => 'X', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D'];
                                    @endphp
                                        @foreach ([1, 2, 3, 4, 5, 6, 7] as $day)
                                            <span class="inline-flex items-center justify-center w-5 h-5 text-xs rounded {{ in_array($day, $days) ? 'bg-[#2E8B57] text-white font-bold' : 'bg-gray-200 text-gray-400' }}">
                                                {{ $labels[$day] }}
                                            </span>
                                        @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-4 text-center whitespace-nowrap">
                                @if ($group->active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#2E8B57]/10 text-[#2E8B57]">
                                        {{ __('Activo') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#E53935]/10 text-[#E53935]">
                                        {{ __('Inactivo') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="openEdit({{ $group->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#F4C542] hover:bg-[#F4C542]/20 transition" title="Editar" aria-label="Editar">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 3.487a2.25 2.25 0 013.182 3.182l-9.193 9.193a4.5 4.5 0 01-1.897 1.13l-3.17 1.056 1.056-3.17a4.5 4.5 0 011.13-1.897l9.193-9.193z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 7.5L16.5 4.5" />
                                        </svg>
                                    </button>
                                    <button wire:click="confirmDelete({{ $group->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#E53935] hover:bg-[#E53935]/20 transition" title="Eliminar" aria-label="Eliminar">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2M7 7l1 12a2 2 0 002 2h4a2 2 0 002-2l1-12" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-10 text-center text-sm text-[#333333]">
                                {{ __('No hay grupos de personal registrados.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-[#A5D6A7]">
            {{ $this->staffGroups->links() }}
        </div>
    </div>

    <flux:modal name="staff-group-form" wire:close="closeModal" class="md:w-150">
        <form wire:submit="save" class="space-y-6" novalidate>
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Editar grupo de personal') : __('Nuevo grupo de personal') }}
                </flux:heading>
                <flux:text class="mt-2">
                    {{ __('Ingrese los datos del grupo de personal.') }}
                </flux:text>
            </div>

            <flux:input
                wire:model="name"
                :label="__('Nombre del grupo')"
                placeholder="{{ __('Ej: Grupo A') }}"
                required
            />

            <div class="grid gap-4 md:grid-cols-2">
                <flux:select wire:model="zone_id" :label="__('Zona')" required>
                    <option value="">{{ __('Seleccione una zona') }}</option>
                    @foreach ($this->zones as $zone)
                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="shift_id" :label="__('Turno')" required>
                    <option value="">{{ __('Seleccione un turno') }}</option>
                    @foreach ($this->shifts as $shift)
                        <option value="{{ $shift->id }}">{{ $shift->name }} ({{ substr($shift->hour_in, 0, 5) }} - {{ substr($shift->hour_out, 0, 5) }})</option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <flux:select wire:model="vehicle_id" :label="__('Vehiculo')" required>
                    <option value="">{{ __('Seleccione un vehiculo') }}</option>
                    @foreach ($this->vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}">{{ $vehicle->name }} - {{ $vehicle->plate }}</option>
                    @endforeach
                </flux:select>

                <div x-data="{
                    open: false,
                    search: '',
                    selectedId: @entangle('driver_id'),
                    items: @js($this->drivers->map(fn ($e) => ['id' => $e->id, 'name' => $e->first_name.' '.$e->last_name, 'dni' => $e->dni])->values()),
                    placeholder: '{{ __('Seleccione un conductor') }}',
                    get selectedName() {
                        const item = this.items.find(i => i.id === this.selectedId);
                        return item ? item.name : '';
                    },
                    get filtered() {
                        if (!this.search) return this.items;
                        const s = this.search.toLowerCase();
                        return this.items.filter(i => i.name.toLowerCase().includes(s) || i.dni.includes(s));
                    },
                    toggle() { this.open = !this.open; if (this.open) { this.$nextTick(() => this.$el.querySelector('input')?.focus()); } },
                    select(item) { this.selectedId = item.id; this.open = false; this.search = ''; },
                    close() { this.open = false; this.search = ''; },
                }" class="relative">
                    <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Conductor') }} <span class="text-red-500">*</span></label>
                    <button type="button" @click="toggle()"
                        class="w-full flex items-center justify-between px-3 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]">
                        <span x-text="selectedName || placeholder" :class="{'text-gray-400': !selectedName}"></span>
                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" @click.outside="close()" x-cloak
                        class="absolute z-50 mt-1 w-full bg-white border border-[#A5D6A7] rounded-lg shadow-lg">
                        <div class="p-2">
                            <input type="text" x-model="search" placeholder="{{ __('Buscar por nombre o DNI...') }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]">
                        </div>
                        <div class="max-h-48 overflow-y-auto">
                            <template x-for="item in filtered" :key="item.id">
                                <button type="button" @click="select(item)"
                                    class="w-full text-left px-3 py-2 text-sm hover:bg-[#A5D6A7]/20 transition flex items-center justify-between"
                                    :class="{'bg-[#A5D6A7]/30': item.id === selectedId}">
                                    <span x-text="item.name" class="font-medium"></span>
                                    <span x-text="item.dni" class="text-gray-500 text-xs ml-2"></span>
                                </button>
                            </template>
                            <div x-show="filtered.length === 0" class="px-3 py-4 text-sm text-gray-500 text-center">
                                {{ __('Sin resultados') }}
                            </div>
                        </div>
                    </div>
                    @error('driver_id') <span class="mt-1 block text-xs text-[#E53935]">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div x-data="{
                    open: false,
                    search: '',
                    selectedId: @entangle('helper_one_id'),
                    items: @js($this->helpers->map(fn ($e) => ['id' => $e->id, 'name' => $e->first_name.' '.$e->last_name, 'dni' => $e->dni])->values()),
                    placeholder: '{{ __('Seleccione un ayudante (opcional)') }}',
                    get selectedName() {
                        const item = this.items.find(i => i.id === this.selectedId);
                        return item ? item.name : '';
                    },
                    get filtered() {
                        if (!this.search) return this.items;
                        const s = this.search.toLowerCase();
                        return this.items.filter(i => i.name.toLowerCase().includes(s) || i.dni.includes(s));
                    },
                    toggle() { this.open = !this.open; if (this.open) { this.$nextTick(() => this.$el.querySelector('input')?.focus()); } },
                    select(item) { this.selectedId = item.id; this.open = false; this.search = ''; },
                    close() { this.open = false; this.search = ''; },
                }" class="relative">
                    <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Ayudante 1') }}</label>
                    <button type="button" @click="toggle()"
                        class="w-full flex items-center justify-between px-3 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]">
                        <span x-text="selectedName || placeholder" :class="{'text-gray-400': !selectedName}"></span>
                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" @click.outside="close()" x-cloak
                        class="absolute z-50 mt-1 w-full bg-white border border-[#A5D6A7] rounded-lg shadow-lg">
                        <div class="p-2">
                            <input type="text" x-model="search" placeholder="{{ __('Buscar por nombre o DNI...') }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]">
                        </div>
                        <div class="max-h-48 overflow-y-auto">
                            <template x-for="item in filtered" :key="item.id">
                                <button type="button" @click="select(item)"
                                    class="w-full text-left px-3 py-2 text-sm hover:bg-[#A5D6A7]/20 transition flex items-center justify-between"
                                    :class="{'bg-[#A5D6A7]/30': item.id === selectedId}">
                                    <span x-text="item.name" class="font-medium"></span>
                                    <span x-text="item.dni" class="text-gray-500 text-xs ml-2"></span>
                                </button>
                            </template>
                            <div x-show="filtered.length === 0" class="px-3 py-4 text-sm text-gray-500 text-center">
                                {{ __('Sin resultados') }}
                            </div>
                        </div>
                    </div>
                    @error('helper_one_id') <span class="mt-1 block text-xs text-[#E53935]">{{ $message }}</span> @enderror
                </div>

                <div x-data="{
                    open: false,
                    search: '',
                    selectedId: @entangle('helper_two_id'),
                    items: @js($this->helpers->map(fn ($e) => ['id' => $e->id, 'name' => $e->first_name.' '.$e->last_name, 'dni' => $e->dni])->values()),
                    placeholder: '{{ __('Seleccione un ayudante (opcional)') }}',
                    get selectedName() {
                        const item = this.items.find(i => i.id === this.selectedId);
                        return item ? item.name : '';
                    },
                    get filtered() {
                        if (!this.search) return this.items;
                        const s = this.search.toLowerCase();
                        return this.items.filter(i => i.name.toLowerCase().includes(s) || i.dni.includes(s));
                    },
                    toggle() { this.open = !this.open; if (this.open) { this.$nextTick(() => this.$el.querySelector('input')?.focus()); } },
                    select(item) { this.selectedId = item.id; this.open = false; this.search = ''; },
                    close() { this.open = false; this.search = ''; },
                }" class="relative">
                    <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Ayudante 2') }}</label>
                    <button type="button" @click="toggle()"
                        class="w-full flex items-center justify-between px-3 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]">
                        <span x-text="selectedName || placeholder" :class="{'text-gray-400': !selectedName}"></span>
                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" @click.outside="close()" x-cloak
                        class="absolute z-50 mt-1 w-full bg-white border border-[#A5D6A7] rounded-lg shadow-lg">
                        <div class="p-2">
                            <input type="text" x-model="search" placeholder="{{ __('Buscar por nombre o DNI...') }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]">
                        </div>
                        <div class="max-h-48 overflow-y-auto">
                            <template x-for="item in filtered" :key="item.id">
                                <button type="button" @click="select(item)"
                                    class="w-full text-left px-3 py-2 text-sm hover:bg-[#A5D6A7]/20 transition flex items-center justify-between"
                                    :class="{'bg-[#A5D6A7]/30': item.id === selectedId}">
                                    <span x-text="item.name" class="font-medium"></span>
                                    <span x-text="item.dni" class="text-gray-500 text-xs ml-2"></span>
                                </button>
                            </template>
                            <div x-show="filtered.length === 0" class="px-3 py-4 text-sm text-gray-500 text-center">
                                {{ __('Sin resultados') }}
                            </div>
                        </div>
                    </div>
                    @error('helper_two_id') <span class="mt-1 block text-xs text-[#E53935]">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-[#333333] mb-2">{{ __('Dias de trabajo *') }}</label>
                <div class="flex flex-wrap gap-4 text-sm">
                    @foreach ([1 => 'Lunes', 2 => 'Martes', 3 => 'Miercoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sabado', 7 => 'Domingo'] as $day => $label)
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                value="{{ $day }}"
                                wire:model="work_days"
                                class="rounded border-gray-300 text-[#2E8B57] focus:ring-[#2E8B57]"
                            >
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('work_days') <span class="mt-1 block text-xs text-[#E53935]">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center gap-3">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" wire:model="active" class="sr-only peer">
                    <div class="relative w-11 h-6 bg-[#CCCCCC] peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-[#2E8B57] rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:inset-s-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#2E8B57]"></div>
                    <span class="ms-3 text-sm font-medium text-[#333333]">{{ __('Activo') }}</span>
                </label>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost" wire:click="closeModal" class="text-[#333333]">
                        {{ __('Cancelar') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="bg-[#2E8B57] text-white hover:bg-[#257046]">
                    {{ $editingId ? __('Actualizar') : __('Guardar') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="confirm-delete" class="md:w-100">
        <div class="space-y-5">
            <div class="flex items-start gap-4 px-6 pt-4">
                <div class="shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="h-5 w-5 text-[#E53935]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <flux:heading size="lg" class="text-[#E53935]">{{ __('Confirmar eliminacion') }}</flux:heading>
                    <flux:text class="mt-1 text-sm text-[#666666]">{{ __('¿Esta seguro de que desea eliminar este grupo? Esta accion no se puede deshacer.') }}</flux:text>
                </div>
            </div>
            <div class="px-6 py-4 bg-[#F5F5F5] border-t border-[#E0E0E0] flex justify-end gap-3">
                <flux:button x-on:click="Flux.modal('confirm-delete').close()" type="button" variant="ghost" class="text-[#333333]">{{ __('Cancelar') }}</flux:button>
                <flux:button wire:click="delete" variant="danger" class="bg-[#E53935] text-white hover:bg-[#C62828]">{{ __('Eliminar') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
