<?php

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Shift;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $date_from = '';
    public string $date_to = '';

    public bool $showModal = false;

    public ?int $editingId = null;
    public ?int $deletingId = null;

    public ?int $employee_id = null;
    public string $attendance_date = '';
    public string $attendance_time = '';
    public ?int $shift_id = null;
    public ?string $detectedShiftName = null;
    public ?string $detectedType = null;

    public string $status = 'Presente';
    public string $notes = '';

    public function mount(): void
    {
        $today = now()->timezone('America/Lima')->toDateString();

        $this->date_from = $today;
        $this->date_to = $today;
    }

    protected function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'attendance_date' => ['required', 'date'],
            'attendance_time' => ['required'],
            'status' => ['required', Rule::in(['Presente', 'Ausente'])],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'employee_id.required' => __('Debe seleccionar un empleado.'),
            'employee_id.exists' => __('El empleado seleccionado no existe.'),

            'attendance_date.required' => __('La fecha es obligatoria.'),
            'attendance_date.date' => __('La fecha no es válida.'),

            'attendance_time.required' => __('La hora es obligatoria.'),

            'status.required' => __('Debe seleccionar un estado.'),
        ];
    }

    #[Computed]
    public function employees()
    {
        return Employee::query()
            ->where('active', true)
            ->whereHas('contracts', function ($query) {
                $query->where('is_active', true)->where(function ($q) {
                    $q->whereNull('end_date')->orWhereDate('end_date', '>=', now()->toDateString());
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }
    #[Computed]
    public function shifts()
    {
        return Shift::orderBy('name')->get();
    }

    #[Computed]
    public function attendances()
    {
        return Attendance::query()
            ->with(['employee', 'shift'])
            ->when($this->search !== '', function ($query) {
                $query->whereHas('employee', function ($q) {
                    $q->where('first_name', 'like', '%' . $this->search . '%')
                        ->orWhere('last_name', 'like', '%' . $this->search . '%')
                        ->orWhere('dni', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->date_from !== '', function ($query) {
                $query->whereDate('attendance_date', '>=', $this->date_from);
            })
            ->when($this->date_to !== '', function ($query) {
                $query->whereDate('attendance_date', '<=', $this->date_to);
            })
            ->latest('attendance_date')
            ->latest('attendance_time')
            ->paginate(10);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function clearDateFilters(): void
    {
        $today = now()->timezone('America/Lima')->toDateString();

        $this->date_from = $today;
        $this->date_to = $today;

        $this->resetPage();
    }

private function detectShiftByHour(): ?int
{
    if (!$this->attendance_time) {
        return null;
    }

    $currentMinutes = $this->timeToMinutes($this->attendance_time);

    $tolerance = 10; // minutos

    foreach (Shift::all() as $shift) {

        $startMinutes = $this->timeToMinutes($shift->hour_in);
        $endMinutes   = $this->timeToMinutes($shift->hour_out);

        // ampliar ventana
        $startMinutes -= $tolerance;
        $endMinutes   += $tolerance;

        // turno normal
        if ($startMinutes <= $endMinutes) {

            if (
                $currentMinutes >= $startMinutes &&
                $currentMinutes <= $endMinutes
            ) {
                return $shift->id;
            }
        }

        // turno nocturno
        else {

            if (
                $currentMinutes >= $startMinutes ||
                $currentMinutes <= $endMinutes
            ) {
                return $shift->id;
            }
        }
    }

    return null;
}

private function determineAttendanceType(int $shiftId): string
{
    $count = Attendance::query()
        ->where('employee_id', $this->employee_id)
        ->whereDate('attendance_date', $this->attendance_date)
        ->where('shift_id', $shiftId)
        ->count();

    return $count === 0 ? 'Ingreso' : 'Salida';
}

    public function updateAttendanceInfo(): void
    {
        $shiftId = $this->detectShiftByHour();

        if (!$shiftId) {
            $this->detectedShiftName = null;
            $this->detectedType = null;
            return;
        }

        $shift = Shift::find($shiftId);

        $this->detectedShiftName = $shift?->name;

        if ($this->employee_id && $this->attendance_date) {
            $this->detectedType = $this->determineAttendanceType($shiftId);
        }
    }

    private function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = explode(':', substr($time, 0, 5));

        return (int) $hours * 60 + (int) $minutes;
    }

    public function openCreate(): void
    {
        $this->resetForm();

        $this->attendance_date = now()->timezone('America/Lima')->format('Y-m-d');
        $this->attendance_time = now()->timezone('America/Lima')->format('H:i');
        $this->updateAttendanceInfo();

        Flux::modal('attendance-form')->show();
    }

    public function openEdit(int $id): void
    {
        $attendance = Attendance::findOrFail($id);

        $this->editingId = $attendance->id;
        $this->employee_id = $attendance->employee_id;
        $this->attendance_date = $attendance->attendance_date;
        $this->attendance_time = substr($attendance->attendance_time, 0, 5);
        $this->status = $attendance->status;
        $this->notes = $attendance->notes ?? '';
        $this->detectedShiftName = $attendance->shift?->name;
        $this->detectedType = $attendance->type;

        Flux::modal('attendance-form')->show();
    }

public function save(): void
{
    $validated = $this->validate();

    // Detectar turno automáticamente según la hora
    $shiftId = $this->detectShiftByHour();

    if (!$shiftId) {
        Flux::toast(
            variant: 'warning',
            text: __('No existe un turno configurado para la hora seleccionada.')
        );

        return;
    }

    // Validar que el empleado tenga contrato activo
    $employee = Employee::query()
        ->where('id', $validated['employee_id'])
        ->whereHas('contracts', function ($query) {
            $query->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('end_date')
                        ->orWhereDate('end_date', '>=', now()->toDateString());
                });
        })
        ->first();

    if (!$employee) {
        Flux::toast(
            variant: 'warning',
            text: __('El empleado no posee un contrato activo.')
        );

        return;
    }

    // Registros existentes del empleado en ese turno y fecha
    $recordsInShift = Attendance::query()
        ->where('employee_id', $validated['employee_id'])
        ->whereDate('attendance_date', $validated['attendance_date'])
        ->where('shift_id', $shiftId)
        ->when($this->editingId, function ($query) {
            $query->where('id', '!=', $this->editingId);
        })
        ->orderBy('attendance_time')
        ->get();

    // Máximo: 1 ingreso y 1 salida
    if ($recordsInShift->count() >= 2) {
        Flux::toast(
            variant: 'warning',
            text: __('Ya existe un ingreso y una salida registrados para este turno.')
        );

        return;
    }

    // No permitir horas repetidas
    $existingAttendance = Attendance::query()
        ->where('employee_id', $validated['employee_id'])
        ->whereDate('attendance_date', $validated['attendance_date'])
        ->where('shift_id', $shiftId)
        ->where('attendance_time', $validated['attendance_time'])
        ->when($this->editingId, function ($query) {
            $query->where('id', '!=', $this->editingId);
        })
        ->exists();

    if ($existingAttendance) {
        Flux::toast(
            variant: 'warning',
            text: __('Ya existe una asistencia registrada con esa hora.')
        );

        return;
    }

    // Determinar automáticamente si es Ingreso o Salida
    $type = $recordsInShift->count() === 0
        ? 'Ingreso'
        : 'Salida';

    // Evitar dos ingresos o dos salidas seguidas
    $lastAttendance = $recordsInShift->last();

    if ($lastAttendance && $lastAttendance->type === $type) {
        Flux::toast(
            variant: 'warning',
            text: __('No puede registrar dos ' . strtolower($type) . ' consecutivos.')
        );

        return;
    }

    $payload = [
        'employee_id'      => $validated['employee_id'],
        'attendance_date'  => $validated['attendance_date'],
        'attendance_time'  => $validated['attendance_time'],
        'shift_id'         => $shiftId,
        'type'             => $type,
        'status'           => $validated['status'],
        'notes'            => $validated['notes'] ?: null,
    ];

    if ($this->editingId) {

        Attendance::findOrFail($this->editingId)->update($payload);

        Flux::toast(
            variant: 'success',
            text: __('Asistencia actualizada correctamente.')
        );

    } else {

        Attendance::create($payload);

        Flux::toast(
            variant: 'success',
            text: __('Asistencia registrada correctamente.')
        );
    }

    $this->resetForm();

    Flux::modal('attendance-form')->close();
}

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;

        Flux::modal('confirm-delete')->show();
    }

    public function delete(): void
    {
        if (!$this->deletingId) {
            return;
        }

        Attendance::findOrFail($this->deletingId)->delete();

        Flux::toast(variant: 'success', text: __('Asistencia eliminada correctamente.'));

        $this->deletingId = null;

        Flux::modal('confirm-delete')->close();
    }

    public function closeModal(): void
    {
        $this->resetForm();

        Flux::modal('attendance-form')->close();
    }

    private function resetForm(): void
    {
        $this->reset(['employee_id', 'attendance_date', 'attendance_time', 'status', 'notes', 'editingId']);

        $this->status = 'Presente';
        $this->notes = '';

        $this->resetValidation();
        $this->resetErrorBag();
    }
};

?>

<div class="min-h-screen bg-white p-6 text-[#333333]">

    <div class="flex items-start justify-between mb-6">

        <div>
            <h1 class="text-3xl font-bold text-[#2E8B57]">
                {{ __('Gestion de asistencias') }}
            </h1>

            <p class="text-sm text-[#333333] mt-1">
                {{ __('Administracion de asistencias del personal.') }}
            </p>
        </div>

        <flux:button wire:click="openCreate" variant="primary" icon="plus-circle"
            class="bg-[#2E8B57]! text-white cursor-pointer hover:bg-[#257046]!">
            {{ __('Nueva Asistencia') }}
        </flux:button>

    </div>
    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] p-5 mb-6">

        <div class="grid gap-4 lg:grid-cols-4">

            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-[#333333] mb-2">
                    {{ __('Buscar por empleado o DNI') }}
                </label>

                <div class="relative">

                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-[#333333]" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>

                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Buscar...') }}"
                        class="w-full pl-10 pr-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]" />

                </div>

            </div>

            <div>
                <label class="block text-sm font-medium text-[#333333] mb-2">
                    {{ __('Desde') }}
                </label>

                <input type="date" wire:model.live="date_from"
                    class="w-full px-3 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]" />
            </div>

            <div>
                <label class="block text-sm font-medium text-[#333333] mb-2">
                    {{ __('Hasta') }}
                </label>

                <div class="flex gap-2">
                    <input type="date" wire:model.live="date_to"
                        class="w-full px-3 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]" />

                    <flux:button type="button" wire:click="clearDateFilters" variant="ghost" class="whitespace-nowrap">
                        {{ __('Limpiar') }}
                    </flux:button>
                </div>

            </div>

        </div>

    </div>
    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] overflow-hidden">

        <div class="overflow-x-auto">

            <table class="w-full">

                <thead>

                    <tr class="bg-[#2E8B57] text-white text-xs font-bold uppercase tracking-wider">

                        <th class="px-4 py-3 text-left">
                            {{ __('Empleado') }}
                        </th>

                        <th class="px-4 py-3 text-left">
                            {{ __('DNI') }}
                        </th>

                        <th class="px-4 py-3 text-left">
                            {{ __('Fecha') }}
                        </th>

                        <th class="px-4 py-3 text-left">
                            {{ __('Hora') }}
                        </th>

                        <th class="px-4 py-3 text-left">
                            {{ __('Turno') }}
                        </th>

                        <th class="px-4 py-3 text-left">
                            {{ __('Tipo') }}
                        </th>

                        <th class="px-4 py-3 text-center">
                            {{ __('Estado') }}
                        </th>

                        <th class="px-4 py-3 text-right">
                            {{ __('Acciones') }}
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse ($this->attendances as $i => $attendance)
                        <tr wire:key="attendance-{{ $attendance->id }}"
                            class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-[#A5D6A7]/20' }} border-b border-[#A5D6A7] hover:bg-[#A5D6A7]/30 transition">

                            <td class="px-4 py-3">
                                <div class="text-sm font-semibold text-[#333333]">
                                    {{ $attendance->employee->last_name }},
                                    {{ $attendance->employee->first_name }}
                                </div>
                            </td>

                            <td class="px-4 py-3 text-sm text-[#333333]">
                                {{ $attendance->employee->dni }}
                            </td>

                            <td class="px-4 py-3 text-sm text-[#333333]">
                                {{ \Carbon\Carbon::parse($attendance->attendance_date)->format('d/m/Y') }}
                            </td>

                            <td class="px-4 py-3 text-sm text-[#333333]">
                                {{ substr($attendance->attendance_time, 0, 5) }}
                            </td>

                            <td class="px-4 py-3 text-sm text-[#333333]">
                                {{ $attendance->shift?->name ?: __('Sin turno') }}
                            </td>

                            <td class="px-4 py-3">

                                @if ($attendance->type === 'Ingreso')
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ __('Ingreso') }}
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                        {{ __('Salida') }}
                                    </span>
                                @endif

                            </td>

                            <td class="px-4 py-3 text-center">

                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                {{ $attendance->status === 'Presente' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $attendance->status }}
                                </span>

                            </td>

                            <td class="px-4 py-3">

                                <div class="flex justify-end gap-2">

                                    <button wire:click="openEdit({{ $attendance->id }})"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#F4C542] hover:bg-[#F4C542]/20 transition"
                                        title="{{ __('Editar') }}">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16.862 3.487a2.25 2.25 0 013.182 3.182l-9.193 9.193a4.5 4.5 0 01-1.897 1.13l-3.17 1.056 1.056-3.17a4.5 4.5 0 011.13-1.897l9.193-9.193z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19.5 7.5L16.5 4.5" />
                                        </svg>
                                    </button>

                                    <button wire:click="confirmDelete({{ $attendance->id }})"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#E53935] hover:bg-[#E53935]/20 transition"
                                        title="{{ __('Eliminar') }}">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2M7 7l1 12a2 2 0 002 2h4a2 2 0 002-2l1-12" />
                                        </svg>
                                    </button>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-sm text-[#333333]">
                                {{ __('No hay asistencias registradas.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>

        </div>

        <div class="px-4 py-3 border-t border-[#A5D6A7]">
            {{ $this->attendances->links() }}
        </div>

    </div>
    {{-- Modal Crear / Editar --}}
    <flux:modal name="attendance-form" wire:close="closeModal" class="md:w-[760px] max-h-[90vh] overflow-y-auto">
        <form wire:submit="save" class="space-y-5" novalidate>

            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Editar asistencia') : __('Nueva asistencia') }}
                </flux:heading>

                <flux:text class="mt-2">
                    {{ __('Complete la informacion de asistencia del personal.') }}
                </flux:text>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">

                <flux:select wire:model="employee_id" :label="__('Empleado')">
                    <option value="">
                        {{ __('Seleccionar...') }}
                    </option>

                    @foreach ($this->employees as $employee)
                        <option value="{{ $employee->id }}">
                            {{ $employee->last_name }},
                            {{ $employee->first_name }}
                            ({{ $employee->dni }})
                        </option>
                    @endforeach

                </flux:select>

                <flux:input type="date" wire:model="attendance_date" :label="__('Fecha')" required />

            </div>

            <div class="grid gap-4 sm:grid-cols-2">

                <flux:input type="time" wire:model="attendance_time" :label="__('Hora')" required />

                <flux:select wire:model="status" :label="__('Estado')">
                    <option value="Presente">
                        {{ __('Presente') }}
                    </option>

                    <option value="Ausente">
                        {{ __('Ausente') }}
                    </option>
                </flux:select>

            </div>

            <div class="grid gap-4 sm:grid-cols-2">

                {{--                 <flux:select wire:model.live="shift_id" :label="__('Turno')">
                    <option value="">
                        {{ __('Seleccionar...') }}
                    </option>

                    @foreach ($this->shifts as $shift)
                        <option value="{{ $shift->id }}">
                            {{ $shift->name }}
                            ({{ substr($shift->hour_in, 0, 5) }}
                            -
                            {{ substr($shift->hour_out, 0, 5) }})
                        </option>
                    @endforeach

                </flux:select>

                <flux:select wire:model="type" :label="__('Tipo de registro')">
                    <option value="">
                        {{ __('Seleccionar...') }}
                    </option>

                    <option value="Ingreso">
                        {{ __('Ingreso') }}
                    </option>

                    <option value="Salida">
                        {{ __('Salida') }}
                    </option>
                </flux:select> --}}

                <div class="rounded-lg border border-[#A5D6A7] p-4">

                    <div class="text-sm font-medium text-[#333333] mb-2">
                        {{ __('Registro automático') }}
                    </div>

                    <div class="text-sm text-[#666666]">
                        {{ __('El turno y el tipo de registro (Ingreso/Salida) serán detectados automáticamente al guardar la asistencia.') }}
                    </div>

                </div>
                <div class="grid gap-4 sm:grid-cols-2">

                    <div>
                        <label class="block text-sm font-medium text-[#333333] mb-2">
                            {{ __('Turno detectado') }}
                        </label>

                        <div class="w-full px-3 py-2.5 border border-[#A5D6A7] rounded-lg bg-gray-100 text-sm">
                            {{ $detectedShiftName ?: __('Sin turno asignado') }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-[#333333] mb-2">
                            {{ __('Tipo de registro') }}
                        </label>

                        <div class="w-full px-3 py-2.5 border border-[#A5D6A7] rounded-lg bg-gray-100 text-sm">
                            {{ $detectedType ?: __('No determinado') }}
                        </div>
                    </div>

                </div>
            </div>

            <flux:textarea wire:model="notes" :label="__('Observaciones')" rows="4"
                placeholder="{{ __('Observaciones opcionales de la asistencia') }}" />

            <div class="flex justify-end gap-2 pt-2">

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

    {{-- Modal eliminar --}}
    <flux:modal name="confirm-delete" class="md:w-[400px]">

        <div class="space-y-6">

            <div>

                <flux:heading size="lg" class="text-red-500">
                    {{ __('Confirmar eliminacion') }}
                </flux:heading>

                <flux:text class="mt-2 text-sm text-[#333333]">
                    {{ __('¿Estas seguro de que deseas eliminar esta asistencia? Esta accion no se puede deshacer.') }}
                </flux:text>

            </div>

            <div class="flex gap-3 justify-end pt-4 border-t border-[#E0E0E0]">

                <flux:button x-on:click="Flux.modal('confirm-delete').close()" type="button">
                    {{ __('Cancelar') }}
                </flux:button>

                <flux:button wire:click="delete" variant="danger" class="bg-[#E53935] text-white">
                    {{ __('Eliminar') }}
                </flux:button>

            </div>

        </div>

    </flux:modal>

</div>
