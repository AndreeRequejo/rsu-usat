<?php

use App\Models\Vacation;
use App\Models\Employee;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Flux\Flux;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $employeeSearch = '';
    public ?int $employee_id = null;
    public string|int|null $requested_days = null;
    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?string $notes = '';

    protected function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'requested_days' => ['required', 'integer', 'min:1'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'employee_id.required' => __('Debe seleccionar un empleado.'),
            'requested_days.required' => __('Debe ingresar los días solicitados.'),
            'requested_days.min' => __('Debe solicitar al menos 1 día.'),
            'start_date.required' => __('La fecha de inicio es requerida.'),
            'end_date.required' => __('La fecha de fin es requerida.'),
            'end_date.after_or_equal' => __('La fecha de fin debe ser igual o posterior a la de inicio.'),
        ];
    }

    public function updatedStartDate($value)
    {
        $this->calculateEndDate();
    }

    public function updatedRequestedDays($value)
    {
        $this->calculateEndDate();
    }

    public function calculateEndDate()
    {
        if ($this->start_date && $this->requested_days && $this->requested_days > 0) {
            $this->end_date = Carbon::parse($this->start_date)
                ->addDays((int) $this->requested_days - 1)
                ->format('Y-m-d');
        }
    }

    public function updatedEmployeeSearch(): void
    {
        if ($this->employeeSearch === '') {
            $this->employee_id = null;
        }
    }

    public function selectEmployee(int $employeeId): void
    {
        $employee = Employee::find($employeeId);
        if (!$employee) return;

        $this->employee_id = $employee->id;
        $this->employeeSearch = $employee->last_name . ' ' . $employee->first_name . ' - ' . $employee->dni;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $hasOverlap = Vacation::where('employee_id', $this->employee_id)
            ->whereIn('status', ['Pendiente', 'Aprobada'])
            ->where(function($q) use ($validated) {
                $q->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                  ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                  ->orWhere(function($q2) use ($validated) {
                      $q2->where('start_date', '<=', $validated['start_date'])
                         ->where('end_date', '>=', $validated['end_date']);
                  });
            })
            ->when($this->editingId, function($q) {
                $q->where('id', '!=', $this->editingId);
            })
            ->exists();

        if ($hasOverlap) {
            Flux::toast(variant: 'danger', text: __('Las fechas coinciden con otra solicitud aprobada o pendiente.'));
            return;
        }

        if ($this->editingId) {
            $vacation = Vacation::findOrFail($this->editingId);
            $vacation->update([
                'employee_id' => $validated['employee_id'],
                'requested_days' => $validated['requested_days'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'notes' => $validated['notes'],
            ]);
            Flux::toast(variant: 'success', text: __('Solicitud actualizada.'));
        } else {
            Vacation::create([
                'employee_id' => $validated['employee_id'],
                'request_date' => now()->format('Y-m-d'),
                'requested_days' => $validated['requested_days'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'notes' => $validated['notes'] ?? '',
                'status' => 'Pendiente',
            ]);
            Flux::toast(variant: 'success', text: __('Solicitud creada exitosamente.'));
        }

        $this->resetForm();
        $this->showModal = false;
        Flux::modal('vacation-form')->close();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
        Flux::modal('vacation-form')->show();
    }

    public function openEdit(int $id): void
    {
        $vacation = Vacation::findOrFail($id);
        
        if ($vacation->status !== 'Pendiente') {
            Flux::toast(variant: 'danger', text: __('Solo se pueden editar solicitudes pendientes.'));
            return;
        }

        $this->editingId = $vacation->id;
        $this->employee_id = $vacation->employee_id;
        if ($vacation->employee) {
            $this->employeeSearch = $vacation->employee->last_name . ' ' . $vacation->employee->first_name . ' - ' . $vacation->employee->dni;
        }
        $this->requested_days = $vacation->requested_days;
        $this->start_date = $vacation->start_date->format('Y-m-d');
        $this->end_date = $vacation->end_date->format('Y-m-d');
        $this->notes = $vacation->notes ?? '';
        $this->showModal = true;
        Flux::modal('vacation-form')->show();
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->showModal = false;
        Flux::modal('vacation-form')->close();
    }

    public function changeStatus(int $id, string $status): void
    {
        $vacation = Vacation::findOrFail($id);
        
        if ($status === 'Aprobada') {
            $employee = $vacation->employee;
            $available = $this->getAvailableDays($employee);
            if ($vacation->requested_days > $available) {
                Flux::toast(variant: 'danger', text: __('No cuenta con días suficientes.'));
                return;
            }
        }

        $vacation->update(['status' => $status]);
        Flux::toast(variant: 'success', text: __('Estado cambiado a ' . $status . '.'));
    }

    public ?int $deletingId = null;

    public function confirmDelete(int $id): void
    {
        $vacation = Vacation::findOrFail($id);
        if ($vacation->status !== 'Pendiente') {
            Flux::toast(variant: 'danger', text: __('Solo se pueden eliminar solicitudes pendientes.'));
            return;
        }

        $this->deletingId = $id;
        Flux::modal('confirm-delete')->show();
    }

    public function delete(): void
    {
        if (!$this->deletingId) return;

        Vacation::findOrFail($this->deletingId)->delete();
        Flux::toast(variant: 'success', text: __('Solicitud eliminada.'));

        if ($this->editingId === $this->deletingId) {
            $this->resetForm();
        }
        $this->deletingId = null;
        Flux::modal('confirm-delete')->close();
    }

    #[Computed]
    public function eligibleEmployees()
    {
        if (mb_strlen($this->employeeSearch) < 2 && !$this->employee_id) {
            return collect();
        }

        $query = Employee::with('employeeType')
            ->whereHas('contracts', function($q) {
                $q->where('is_active', true)
                  ->whereIn('contract_type', ['Nombrado', 'Permanente']);
            });

        if (mb_strlen($this->employeeSearch) >= 2) {
            $query->where(function ($sub) {
                $sub->where('dni', 'like', '%' . $this->employeeSearch . '%')
                    ->orWhere('first_name', 'like', '%' . $this->employeeSearch . '%')
                    ->orWhere('last_name', 'like', '%' . $this->employeeSearch . '%');
            });
        } elseif ($this->employee_id) {
            $query->where('id', $this->employee_id);
        }

        return $query->orderBy('last_name')->orderBy('first_name')->take(5)->get();
    }
    
    public function getAvailableDays(Employee $employee)
    {
        $contract = $employee->contracts()->where('is_active', true)->first();
        if (!$contract) return 0;
        
        $yearlyDays = $contract->vacation_days_per_year;
        $currentYear = now()->year;
        
        $usedDays = $employee->vacations()
            ->where('status', 'Aprobada')
            ->whereYear('request_date', $currentYear)
            ->sum('requested_days');
            
        return max(0, $yearlyDays - $usedDays);
    }

    #[Computed]
    public function vacations()
    {
        return Vacation::query()
            ->with(['employee'])
            ->when($this->search !== '', function ($query) {
                $query->whereHas('employee', function ($q) {
                    $q->where('first_name', 'like', '%'.$this->search.'%')
                      ->orWhere('last_name', 'like', '%'.$this->search.'%')
                      ->orWhere('dni', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    private function resetForm(): void
    {
        $this->reset(['employeeSearch', 'employee_id', 'requested_days', 'start_date', 'end_date', 'notes', 'editingId']);
        $this->resetErrorBag();
        $this->resetValidation();
    }
}; ?>

<div class="min-h-screen bg-white p-6 text-[#333333]">
    {{-- Header --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-[#2E8B57]">
                {{ __('Lista de Vacaciones') }}
            </h1>
            <p class="text-sm text-[#333333] mt-1">
                {{ __('Gestión de solicitudes de vacaciones del personal.') }}
            </p>
        </div>

        <flux:button
            wire:click="openCreate"
            variant="primary"
            icon="plus-circle"
            class="bg-[#333333]! text-white cursor-pointer hover:bg-gray-800!"
        >
            {{ __('Nueva Solicitud') }}
        </flux:button>
    </div>

    {{-- Search card --}}
    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] p-5 mb-6 flex justify-between items-center">
        <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-[#333333]">
                {{ __('Mostrar') }}
            </label>
            <select class="border border-[#A5D6A7] rounded text-sm py-1 bg-white focus:outline-none focus:ring-1 focus:ring-[#2E8B57]">
                <option>10</option>
            </select>
            <label class="text-sm font-medium text-[#333333]">
                {{ __('registros') }}
            </label>
        </div>
        <div class="flex gap-3 items-center">
            <label class="text-sm font-medium text-[#333333]">{{ __('Buscar:') }}</label>
            <div class="relative w-64">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('DNI, nombre...') }}"
                    class="w-full px-3 py-1.5 border border-[#A5D6A7] rounded bg-white text-sm focus:outline-none focus:ring-1 focus:ring-[#2E8B57]"
                />
            </div>
        </div>
    </div>

    {{-- Table card --}}
    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-[#2E8B57] text-white text-xs font-bold uppercase tracking-wider text-center">
                        <th class="px-4 py-3">{{ __('DNI') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Empleado') }}</th>
                        <th class="px-4 py-3">{{ __('Fecha solicitud') }}</th>
                        <th class="px-4 py-3">{{ __('Fecha de inicio') }}</th>
                        <th class="px-4 py-3">{{ __('Fecha de término') }}</th>
                        <th class="px-4 py-3">{{ __('Días S.') }}</th>
                        <th class="px-4 py-3">{{ __('Estado') }}</th>
                        <th class="px-4 py-3">{{ __('Días R.') }}</th>
                        <th class="px-4 py-3">{{ __('Notas') }}</th>
                        <th class="px-4 py-3">{{ __('Acciones') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->vacations as $i => $vacation)
                        <tr wire:key="vac-{{ $vacation->id }}" class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-[#A5D6A7]/20' }} border-b border-[#A5D6A7] hover:bg-[#A5D6A7]/30 transition text-center text-gray-700">
                            <td class="px-4 py-3 font-medium">{{ $vacation->employee->dni }}</td>
                            <td class="px-4 py-3 text-left font-medium">{{ $vacation->employee->first_name }} {{ $vacation->employee->last_name }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $vacation->request_date ? clone($vacation->request_date)->format('d/m/Y') : '' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $vacation->start_date ? clone($vacation->start_date)->format('d/m/Y') : '' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $vacation->end_date ? clone($vacation->end_date)->format('d/m/Y') : '' }}</td>
                            <td class="px-4 py-3 font-semibold text-[#007bff]">{{ $vacation->requested_days }}</td>
                            <td class="px-4 py-3">
                                @if($vacation->status === 'Aprobada')
                                    <span class="inline-flex px-2 py-1 bg-green-100 border border-green-300 text-green-700 text-xs font-semibold">Aprobada</span>
                                @elseif($vacation->status === 'Rechazada')
                                    <span class="inline-flex px-2 py-1 bg-[#f8d7da] border border-[#f5c6cb] text-[#721c24] text-xs font-semibold">Rechazada</span>
                                @elseif($vacation->status === 'Cancelada')
                                    <span class="inline-flex px-2 py-1 bg-gray-200 border border-gray-300 text-gray-700 text-xs font-semibold">Cancelada</span>
                                @else
                                    <span class="inline-flex px-2 py-1 bg-[#fff3cd] border border-[#ffeeba] text-[#856404] text-xs font-semibold">Pendiente</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-semibold text-gray-700 border-l border-r border-[#A5D6A7]/50">{{ $this->getAvailableDays($vacation->employee) }}</td>
                            <td class="px-4 py-3 text-xs text-gray-500 max-w-[120px] truncate" title="{{ $vacation->notes }}">{{ $vacation->notes ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-center gap-1">
                                    @if($vacation->status === 'Pendiente')
                                        <button wire:click="changeStatus({{ $vacation->id }}, 'Aprobada')" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#2E8B57] hover:bg-[#2E8B57]/20 transition" title="{{ __('Aprobar') }}">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                        <button wire:click="changeStatus({{ $vacation->id }}, 'Rechazada')" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#E53935] hover:bg-[#E53935]/20 transition" title="{{ __('Rechazar') }}">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                        <button wire:click="openEdit({{ $vacation->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#007bff] hover:bg-[#007bff]/20 transition" title="{{ __('Editar') }}">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        </button>
                                        <button wire:click="confirmDelete({{ $vacation->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#E53935] hover:bg-[#E53935]/20 transition" title="{{ __('Eliminar') }}">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-10 text-center text-sm text-[#333333]">
                                {{ __('No hay solicitudes registradas.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-[#A5D6A7]">
            {{ $this->vacations->links() }}
        </div>
    </div>

    {{-- Modal crear/editar --}}
    <flux:modal name="vacation-form" wire:close="closeModal" class="md:w-[600px] !p-0">
        <form wire:submit="save">
            <!-- Header Modal -->
            <div class="bg-[#0b5394] text-white px-4 py-3 rounded-t-xl flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
                    <h2 class="font-semibold text-lg">
                        {{ $editingId ? __('Editar Solicitud de Vacaciones') : __('Nueva Solicitud de Vacaciones') }}
                    </h2>
                </div>
                <button type="button" wire:click="closeModal" class="text-white hover:text-gray-300">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Body Modal -->
            <div class="p-6 space-y-5 bg-white">
                
                <div class="relative">
                    <label class="block text-sm font-medium text-[#333333] mb-2">Personal *</label>
                    <input
                        wire:model.live.debounce.300ms="employeeSearch"
                        type="text"
                        placeholder="Buscar por DNI o nombre"
                        class="w-full rounded-lg border border-[#A5D6A7] bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                        @if ($editingId) readonly @else required @endif
                    />

                    @if (!$editingId && mb_strlen($employeeSearch) >= 2 && !$employee_id)
                        <div class="mt-2 max-h-48 overflow-y-auto rounded-lg border border-[#A5D6A7] bg-white absolute z-50 w-full shadow-lg">
                            @forelse ($this->eligibleEmployees as $employee)
                                <button
                                    type="button"
                                    wire:click="selectEmployee({{ $employee->id }})"
                                    class="flex w-full items-center justify-between px-3 py-2 text-left text-sm text-[#333333] hover:bg-[#A5D6A7]/30"
                                >
                                    <span>{{ $employee->last_name }} {{ $employee->first_name }} - {{ $employee->dni }}</span>
                                    <span class="text-xs text-[#666666]">{{ $employee->employeeType?->name }}</span>
                                </button>
                            @empty
                                <div class="px-3 py-2 text-sm text-[#666666]">Sin resultados.</div>
                            @endforelse
                        </div>
                    @endif
                    <p class="text-xs text-[#666666] mt-1">Escriba al menos 2 letras para buscar empleados.</p>
                    @if ($employee_id)
                        <p class="text-xs text-[#2E8B57]">Seleccionado: {{ $employeeSearch }}</p>
                    @endif
                    @error('employee_id') <span class="text-xs text-[#E53935] mt-1 block">{{ $message }}</span> @enderror
                </div>

                <flux:input wire:model.live="requested_days" type="number" min="1" :label="__('Días Solicitados *')" placeholder="{{ __('Número de días') }}" />

                <flux:input wire:model.live="start_date" type="date" :label="__('Fecha de Inicio *')" />
                
                <flux:input wire:model="end_date" type="date" :label="__('Fecha de Fin *')" placeholder="dd/mm/aaaa" readonly class="bg-gray-100" />

                <flux:textarea wire:model="notes" :label="__('Notas')" placeholder="{{ __('Observaciones o comentarios sobre la solicitud...') }}" rows="3" />

                <!-- Leyenda Importante -->
                <div class="bg-[#fff3cd] border-l-4 border-[#ffc107] text-[#856404] p-3 text-xs rounded shadow-sm shadow-[#ffc107]/20 mt-2">
                    <p class="font-bold flex items-center mb-1">
                        <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        Importante:
                    </p>
                    <ul class="list-disc pl-5 space-y-1">
                        <li>Solo personal <strong>nombrado</strong> y contrato permanente puede solicitar vacaciones</li>
                        <li>No se pueden solicitar vacaciones en fechas que coincidan con otras solicitudes aprobadas o pendientes</li>
                        <li>Las solicitudes pendientes pueden ser editadas o eliminadas</li>
                    </ul>
                </div>
            </div>

            <!-- Footer Modal -->
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3 rounded-b-xl bg-gray-50">
                <button type="button" wire:click="closeModal" class="bg-[#dc3545] text-white px-4 py-2 rounded text-sm font-medium hover:bg-[#c82333] transition flex items-center gap-1">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Cancelar
                </button>
                <button type="submit" class="bg-[#007bff] text-white px-4 py-2 rounded text-sm font-medium hover:bg-[#0069d9] transition flex items-center gap-1">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    Guardar
                </button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal confirmar eliminación --}}
    <flux:modal name="confirm-delete" class="md:w-[400px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg" class="text-red-600">
                    {{ __('Confirmar eliminación') }}
                </flux:heading>
                <flux:text class="mt-2 text-sm text-[#333333]">
                    {{ __('¿Estás seguro de que deseas eliminar esta solicitud de vacaciones? Esta acción no se puede deshacer.') }}
                </flux:text>
            </div>

            <div class="flex gap-3 justify-end pt-4 border-t border-gray-200">
                <flux:button x-on:click="Flux.modal('confirm-delete').close()" type="button">
                    {{ __('Cancelar') }}
                </flux:button>
                <button wire:click="delete" class="bg-[#dc3545] text-white px-4 py-2 rounded text-sm font-medium hover:bg-[#c82333] transition">
                    {{ __('Eliminar') }}
                </button>
            </div>
        </div>
    </flux:modal>
</div>
