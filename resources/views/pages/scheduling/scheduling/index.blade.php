<?php

use App\Models\Employee;
use App\Models\GroupDetail;
use App\Models\Scheduling;
use App\Models\SchedulingHistory;
use App\Models\Shift;
use App\Models\StaffGroup;
use App\Models\Vehicle;
use App\Models\Vacation;
use App\Models\Zone;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterStart = '';
    public string $filterEnd = '';
    public string $zoneFilter = '';
    public string $shiftFilter = '';

    public ?int $editingId = null;
    public ?int $deletingId = null;
    public ?int $historyId = null;

    public string $modalTitle = 'Nueva Programacion';
    public string $start_date = '';
    public string $end_date = '';
    public ?int $staff_group_id = null;
    public ?int $zone_id = null;
    public ?int $shift_id = null;
    public ?int $vehicle_id = null;
    public ?int $driver_id = null;
    public ?int $helper_one_id = null;
    public ?int $helper_two_id = null;
    public array $work_days = [];
    public string $notes = '';

    public bool $availabilityChecked = false;
    public bool $availabilityValid = false;
    public bool $formChangedAfterValidation = false;
    public array $availabilityErrors = [];
    public array $availabilitySuggestions = [];

    public function mount(): void
    {
        $this->filterStart = now()->startOfMonth()->format('Y-m-d');
        $this->filterEnd = now()->endOfMonth()->format('Y-m-d');
    }

    protected function rules(): array
    {
        $endDateRules = ['required', 'date', 'after_or_equal:start_date'];

        if ($this->editingId) {
            $endDateRules[] = 'same:start_date';
        }

        return [
            'start_date' => ['required', 'date'],
            'end_date' => $endDateRules,
            'staff_group_id' => ['required', 'exists:staff_groups,id'],
            'zone_id' => ['required', 'exists:zones,id'],
            'shift_id' => ['required', 'exists:shifts,id'],
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'driver_id' => ['required', 'exists:employees,id'],
            'helper_one_id' => ['nullable', 'exists:employees,id', 'different:driver_id'],
            'helper_two_id' => ['nullable', 'exists:employees,id', 'different:driver_id', 'different:helper_one_id'],
            'work_days' => ['required', 'array', 'min:1'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'start_date.required' => 'La fecha de inicio es obligatoria.',
            'end_date.required' => 'La fecha de fin es obligatoria.',
            'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'end_date.same' => 'Para modificar una programacion, la fecha de inicio y fin deben ser el mismo dia.',
            'staff_group_id.required' => 'Seleccione un grupo de personal.',
            'zone_id.required' => 'Seleccione una zona.',
            'shift_id.required' => 'Seleccione un turno.',
            'vehicle_id.required' => 'Seleccione un vehiculo.',
            'driver_id.required' => 'Seleccione un conductor.',
            'helper_one_id.different' => 'El ayudante 1 no puede ser el conductor.',
            'helper_two_id.different' => 'El ayudante 2 no puede repetir conductor ni ayudante 1.',
            'work_days.required' => 'Seleccione al menos un dia de trabajo.',
            'work_days.min' => 'Seleccione al menos un dia de trabajo.',
        ];
    }

    public function updatedStartDate(): void
    {
        if ($this->editingId) {
            $this->end_date = $this->start_date;
            $this->syncSingleDayWorkDay();
        }

        $this->markAvailabilityDirty();
    }

    public function updatedEndDate(): void
    {
        if ($this->editingId) {
            $this->start_date = $this->end_date;
            $this->syncSingleDayWorkDay();
        }

        $this->markAvailabilityDirty();
    }

    public function updated($property): void
    {
        if (in_array($property, [
            'start_date',
            'end_date',
            'staff_group_id',
            'zone_id',
            'shift_id',
            'vehicle_id',
            'driver_id',
            'helper_one_id',
            'helper_two_id',
            'work_days',
        ], true)) {
            $this->markAvailabilityDirty();
        }
    }

    public function updatedStaffGroupId($value): void
    {
        if (! $value) {
            return;
        }

        $group = StaffGroup::with(['zone', 'shift', 'vehicle', 'driver', 'helperOne', 'helperTwo'])->find($value);
        if (! $group) {
            return;
        }

        $this->zone_id = $group->zone_id;
        $this->shift_id = $group->shift_id;
        $this->vehicle_id = $group->vehicle_id;
        $this->driver_id = $group->driver_id;
        $this->helper_one_id = $group->helper_one_id;
        $this->helper_two_id = $group->helper_two_id;
        $this->work_days = $group->work_days ?? [];

        if ($this->editingId) {
            $this->syncSingleDayWorkDay();
        }

        $this->markAvailabilityDirty();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->modalTitle = 'Nueva Programacion';
        Flux::modal('scheduling-form')->show();
    }

    public function openMassive(): void
    {
        $this->resetForm();
        $this->modalTitle = 'Programacion Masiva';
        Flux::modal('scheduling-form')->show();
    }

    public function openEdit(int $id): void
    {
        $scheduling = Scheduling::with('groupDetails.employee')->findOrFail($id);
        if ($scheduling->status === 'Finalizado') {
            Flux::toast(variant: 'warning', text: 'No se puede modificar una programacion finalizada.');
            return;
        }

        $employees = $scheduling->groupDetails->pluck('employee_id')->values();
        $this->resetForm();
        $this->editingId = $scheduling->id;
        $this->modalTitle = 'Modificar Programacion';
        $this->start_date = $scheduling->date->format('Y-m-d');
        $this->end_date = $scheduling->date->format('Y-m-d');
        $this->zone_id = $scheduling->zone_id;
        $this->shift_id = $scheduling->shift_id;
        $this->vehicle_id = $scheduling->vehicle_id;
        $this->driver_id = $employees->get(0);
        $this->helper_one_id = $employees->get(1);
        $this->helper_two_id = $employees->get(2);
        $this->staff_group_id = $this->matchingStaffGroupId();
        $this->work_days = [$scheduling->date->dayOfWeekIso];
        $this->notes = $scheduling->notes ?? '';
        $this->markAvailabilityDirty();
        Flux::modal('scheduling-form')->show();
    }

    public function closeModal(): void
    {
        $this->resetForm();
        Flux::modal('scheduling-form')->close();
    }

    public function validateAvailability(): void
    {
        $this->validate();
        $this->availabilityErrors = [];
        $this->availabilitySuggestions = [];

        $dates = $this->selectedDates();
        if ($dates->isEmpty()) {
            $this->availabilityErrors[] = 'No hay fechas dentro del rango que coincidan con los dias de trabajo seleccionados.';
        }

        $this->validateDifferentPeople();
        $this->validateContractsAndVacations($dates);
        $this->validateSchedulingConflicts($dates);

        $this->availabilityChecked = true;
        $this->availabilityValid = empty($this->availabilityErrors);
        $this->formChangedAfterValidation = false;
    }

    public function save(): void
    {
        $this->validate();

        if (! $this->availabilityChecked || ! $this->availabilityValid || $this->formChangedAfterValidation) {
            Flux::toast(variant: 'warning', text: 'Primero valide la disponibilidad.');
            return;
        }

        $dates = $this->selectedDates();
        if ($dates->isEmpty()) {
            $this->addError('work_days', 'No hay fechas para generar.');
            return;
        }

        if ($this->editingId) {
            $scheduling = Scheduling::findOrFail($this->editingId);
            $before = $scheduling->only(['date', 'shift_id', 'vehicle_id', 'zone_id', 'status', 'notes']);
            $scheduling->update([
                'date' => $dates->first()->format('Y-m-d'),
                'shift_id' => $this->shift_id,
                'vehicle_id' => $this->vehicle_id,
                'zone_id' => $this->zone_id,
                'notes' => $this->notes,
            ]);
            $this->syncGroupDetails($scheduling);
            $this->writeHistory($scheduling->id, 'Actualizacion', 'Se modifico la programacion.', ['before' => $before, 'after' => $scheduling->fresh()->only(['date', 'shift_id', 'vehicle_id', 'zone_id', 'status', 'notes'])]);
            Flux::toast(variant: 'success', text: 'Programacion actualizada.');
        } else {
            foreach ($dates as $date) {
                $scheduling = Scheduling::create([
                    'date' => $date->format('Y-m-d'),
                    'shift_id' => $this->shift_id,
                    'vehicle_id' => $this->vehicle_id,
                    'zone_id' => $this->zone_id,
                    'status' => 'Programado',
                    'notes' => $this->notes,
                ]);
                $this->syncGroupDetails($scheduling);
                $this->writeHistory($scheduling->id, 'Creacion', 'Se genero la programacion desde el grupo de personal.');
            }
            Flux::toast(variant: 'success', text: 'Programacion generada.');
        }

        $this->resetForm();
        Flux::modal('scheduling-form')->close();
    }

    public function finish(int $id): void
    {
        $scheduling = Scheduling::findOrFail($id);
        $scheduling->update(['status' => 'Finalizado']);
        $this->writeHistory($scheduling->id, 'Finalizacion', 'Se finalizo la programacion.');
        Flux::toast(variant: 'success', text: 'Programacion finalizada.');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        Flux::modal('confirm-delete')->show();
    }

    public function delete(): void
    {
        if (! $this->deletingId) {
            return;
        }

        $scheduling = Scheduling::findOrFail($this->deletingId);
        if ($scheduling->status === 'Finalizado') {
            Flux::toast(variant: 'warning', text: 'No se puede eliminar una programacion finalizada.');
            $this->deletingId = null;
            Flux::modal('confirm-delete')->close();
            return;
        }

        $this->writeHistory($scheduling->id, 'Eliminacion', 'Se elimino la programacion.', $scheduling->only(['date', 'shift_id', 'vehicle_id', 'zone_id', 'status']));
        $scheduling->delete();
        $this->deletingId = null;
        Flux::modal('confirm-delete')->close();
        Flux::toast(variant: 'success', text: 'Programacion eliminada.');
    }

    public function openHistory(int $id): void
    {
        $this->historyId = $id;
        Flux::modal('history-modal')->show();
    }

    public function clearFilters(): void
    {
        $this->filterStart = now()->startOfMonth()->format('Y-m-d');
        $this->filterEnd = now()->endOfMonth()->format('Y-m-d');
        $this->zoneFilter = '';
        $this->shiftFilter = '';
        $this->search = '';
        $this->resetPage();
    }

    #[Computed]
    public function schedulings()
    {
        return Scheduling::query()
            ->with(['zone', 'shift', 'vehicle', 'groupDetails.employee'])
            ->when($this->filterStart !== '', fn ($query) => $query->whereDate('date', '>=', $this->filterStart))
            ->when($this->filterEnd !== '', fn ($query) => $query->whereDate('date', '<=', $this->filterEnd))
            ->when($this->zoneFilter !== '', fn ($query) => $query->where('zone_id', $this->zoneFilter))
            ->when($this->shiftFilter !== '', fn ($query) => $query->where('shift_id', $this->shiftFilter))
            ->when($this->search !== '', function ($query) {
                $query->where(function ($searchQuery) {
                    $searchQuery->whereHas('vehicle', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%')->orWhere('plate', 'like', '%'.$this->search.'%'))
                        ->orWhereHas('groupDetails.employee', fn ($q) => $q->where('first_name', 'like', '%'.$this->search.'%')->orWhere('last_name', 'like', '%'.$this->search.'%'));
                });
            })
            ->orderBy('date')
            ->orderBy('shift_id')
            ->paginate(10);
    }

    #[Computed]
    public function groups()
    {
        return StaffGroup::with(['zone', 'shift', 'vehicle'])->where('active', true)->orderBy('name')->get();
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
        return Vehicle::where('status', true)->orderBy('name')->get();
    }

    #[Computed]
    public function employees()
    {
        return Employee::where('active', true)->orderBy('last_name')->orderBy('first_name')->get();
    }

    #[Computed]
    public function histories()
    {
        if (! $this->historyId) {
            return collect();
        }

        return SchedulingHistory::with('user')
            ->where('scheduling_id', $this->historyId)
            ->latest()
            ->get();
    }

    private function markAvailabilityDirty(): void
    {
        if ($this->availabilityChecked) {
            $this->formChangedAfterValidation = true;
        }

        $this->availabilityValid = false;
    }

    private function selectedDates()
    {
        if (! $this->start_date || ! $this->end_date || empty($this->work_days)) {
            return collect();
        }

        return collect(CarbonPeriod::create($this->start_date, $this->end_date))
            ->filter(fn (Carbon $date) => in_array($date->dayOfWeekIso, array_map('intval', $this->work_days), true))
            ->values();
    }

    private function selectedEmployeeIds(): array
    {
        return collect([$this->driver_id, $this->helper_one_id, $this->helper_two_id])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function syncSingleDayWorkDay(): void
    {
        if (! $this->start_date) {
            return;
        }

        $this->work_days = [Carbon::parse($this->start_date)->dayOfWeekIso];
    }

    private function matchingStaffGroupId(): ?int
    {
        return StaffGroup::query()
            ->where('zone_id', $this->zone_id)
            ->where('shift_id', $this->shift_id)
            ->where('vehicle_id', $this->vehicle_id)
            ->where('driver_id', $this->driver_id)
            ->where('helper_one_id', $this->helper_one_id)
            ->where('helper_two_id', $this->helper_two_id)
            ->value('id');
    }

    private function validateDifferentPeople(): void
    {
        $selected = array_filter([$this->driver_id, $this->helper_one_id, $this->helper_two_id]);
        if (count($selected) !== count(array_unique($selected))) {
            $this->availabilityErrors[] = 'Un trabajador no puede ocupar mas de un rol en la misma programacion.';
        }
    }

    private function validateContractsAndVacations($dates): void
    {
        foreach ($this->selectedEmployeeIds() as $employeeId) {
            $employee = Employee::with('contracts')->find($employeeId);
            if (! $employee) {
                continue;
            }

            $employeeName = $this->employeeName($employee);
            $problems = [];
            $contractIssueDates = [];

            foreach ($dates as $date) {
                $hasContract = $employee->contracts()
                    ->where('is_active', true)
                    ->whereDate('start_date', '<=', $date->format('Y-m-d'))
                    ->where(function ($query) use ($date) {
                        $query->whereNull('end_date')->orWhereDate('end_date', '>=', $date->format('Y-m-d'));
                    })
                    ->exists();

                if (! $hasContract) {
                    $contractIssueDates[] = $date->copy();
                }
            }

            if (! empty($contractIssueDates)) {
                $problems[] = 'contrato no vigente en '.$this->summarizeDates($contractIssueDates);
            }

            $vacation = Vacation::where('employee_id', $employeeId)
                ->where('status', 'Aprobada')
                ->whereDate('start_date', '<=', $this->end_date)
                ->whereDate('end_date', '>=', $this->start_date)
                ->first();

            if ($vacation) {
                $problems[] = 'vacaciones aprobadas del '.$vacation->start_date->format('d/m/Y').' al '.$vacation->end_date->format('d/m/Y');
            }

            if (! empty($problems)) {
                $this->availabilityErrors[] = $employeeName.': '.implode('; ', $problems).'.';
                $this->suggestReplacement($employee);
            }
        }
    }

    private function validateSchedulingConflicts($dates): void
    {
        $vehicleConflictDates = [];
        $employeeConflictDates = [];

        foreach ($dates as $date) {
            $vehicleConflict = Scheduling::whereDate('date', $date->format('Y-m-d'))
                ->where('shift_id', $this->shift_id)
                ->where('vehicle_id', $this->vehicle_id)
                ->when($this->editingId, fn ($query) => $query->where('id', '!=', $this->editingId))
                ->exists();

            if ($vehicleConflict) {
                $vehicleConflictDates[] = $date->copy();
            }

            foreach ($this->selectedEmployeeIds() as $employeeId) {
                $employeeConflict = GroupDetail::where('employee_id', $employeeId)
                    ->whereHas('scheduling', function ($query) use ($date) {
                        $query->whereDate('date', $date->format('Y-m-d'))
                            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId));
                    })
                    ->exists();

                if ($employeeConflict) {
                    $employeeConflictDates[$employeeId][] = $date->copy();
                }
            }
        }

        if (! empty($vehicleConflictDates)) {
            $this->availabilityErrors[] = 'El vehiculo ya tiene programacion para el turno seleccionado en '.$this->summarizeDates($vehicleConflictDates).'.';
        }

        foreach ($employeeConflictDates as $employeeId => $conflictDates) {
            $employee = Employee::find($employeeId);
            $this->availabilityErrors[] = $this->employeeName($employee).': Ya tiene programacion asignada en '.$this->summarizeDates($conflictDates).'.';
            $this->suggestReplacement($employee);
        }
    }

    private function summarizeDates(array $dates): string
    {
        $dates = collect($dates)->sortBy(fn (Carbon $date) => $date->format('Y-m-d'))->values();

        if ($dates->isEmpty()) {
            return 'el rango seleccionado';
        }

        if ($dates->count() === 1) {
            return $dates->first()->format('d/m/Y');
        }

        return $dates->first()->format('d/m/Y').' al '.$dates->last()->format('d/m/Y').' ('.$dates->count().' dias)';
    }

    private function suggestReplacement(?Employee $employee): void
    {
        if (! $employee) {
            return;
        }

        $replacement = Employee::where('employee_type_id', $employee->employee_type_id)
            ->where('active', true)
            ->where('id', '!=', $employee->id)
            ->whereNotIn('id', $this->selectedEmployeeIds())
            ->first();

        if ($replacement) {
            $this->availabilitySuggestions[] = 'Sugerencia: Reemplazar a '.$this->employeeName($employee).' con '.$this->employeeName($replacement).'.';
        }
    }

    private function syncGroupDetails(Scheduling $scheduling): void
    {
        $scheduling->groupDetails()->delete();
        foreach ($this->selectedEmployeeIds() as $employeeId) {
            $scheduling->groupDetails()->create(['employee_id' => $employeeId]);
        }
    }

    private function writeHistory(?int $schedulingId, string $action, string $description, ?array $changes = null): void
    {
        SchedulingHistory::create([
            'scheduling_id' => $schedulingId,
            'action' => $action,
            'description' => $description,
            'changes' => $changes,
            'user_id' => auth()->id(),
        ]);
    }

    private function employeeName(?Employee $employee): string
    {
        if (! $employee) {
            return 'Empleado';
        }

        return trim($employee->first_name.' '.$employee->last_name);
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId',
            'start_date',
            'end_date',
            'staff_group_id',
            'zone_id',
            'shift_id',
            'vehicle_id',
            'driver_id',
            'helper_one_id',
            'helper_two_id',
            'work_days',
            'notes',
            'availabilityChecked',
            'availabilityValid',
            'formChangedAfterValidation',
            'availabilityErrors',
            'availabilitySuggestions',
        ]);

        $this->start_date = now()->format('Y-m-d');
        $this->end_date = now()->format('Y-m-d');
        $this->resetErrorBag();
        $this->resetValidation();
    }
}; ?>

<div class="min-h-screen bg-white p-6 text-[#333333]">
    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-[#2E8B57]">{{ __('Lista de Programaciones') }}</h1>
            <p class="text-sm text-[#333333] mt-1">{{ __('Gestion de programaciones operativas por grupo de personal.') }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button :href="route('dashboard')" wire:navigate variant="primary" icon="home" class="bg-[#2E8B57]! text-white hover:bg-[#257046]!">
                {{ __('Ir al Dashboard') }}
            </flux:button>
            <flux:button wire:click="openCreate" variant="primary" icon="plus" class="bg-[#007bff]! text-white hover:bg-[#0069d9]!">
                {{ __('Nueva Programacion') }}
            </flux:button>
            <flux:button wire:click="openMassive" variant="danger" icon="queue-list" class="bg-[#E53935]! text-white hover:bg-[#C62828]!">
                {{ __('Programacion Masiva') }}
            </flux:button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] p-5 mb-6">
        <div class="grid gap-4 md:grid-cols-5">
            <flux:input type="date" wire:model.live="filterStart" label="Fecha de inicio" />
            <flux:input type="date" wire:model.live="filterEnd" label="Fecha de fin" />
            <flux:select wire:model.live="zoneFilter" label="Zona">
                <option value="">{{ __('Todas las zonas') }}</option>
                @foreach ($this->zones as $zone)
                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="shiftFilter" label="Turno">
                <option value="">{{ __('Todos los turnos') }}</option>
                @foreach ($this->shifts as $shift)
                    <option value="{{ $shift->id }}">{{ $shift->name }}</option>
                @endforeach
            </flux:select>
            <div class="flex items-end">
                <flux:button wire:click="clearFilters" icon="x-mark" class="w-full">{{ __('Limpiar') }}</flux:button>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-[#A5D6A7] p-4 md:flex-row md:items-center md:justify-between">
            <div class="flex flex-wrap gap-2">
                <button class="inline-flex items-center gap-2 rounded-md bg-[#22c55e] px-3 py-2 text-sm font-semibold text-white">
                    <span>Excel</span>
                </button>
                <button class="inline-flex items-center gap-2 rounded-md bg-[#E53935] px-3 py-2 text-sm font-semibold text-white">
                    <span>PDF</span>
                </button>
                <button class="inline-flex items-center gap-2 rounded-md bg-[#334155] px-3 py-2 text-sm font-semibold text-white">
                    <span>Imprimir</span>
                </button>
            </div>
            <div class="w-full md:w-72">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar..." />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-[#2E8B57] text-white text-xs font-bold uppercase tracking-wider">
                        <th class="px-4 py-3 text-left">{{ __('Fecha') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Estado') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Zona') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Turno') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Vehiculo') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Conductor') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Ayudantes') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('Acciones') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->schedulings as $i => $scheduling)
                        @php
                            $employees = $scheduling->groupDetails->pluck('employee')->filter()->values();
                            $driver = $employees->get(0);
                            $helpers = $employees->slice(1);
                        @endphp
                        <tr wire:key="scheduling-{{ $scheduling->id }}" class="{{ $scheduling->status === 'Finalizado' ? 'bg-green-50' : ($i % 2 === 0 ? 'bg-white' : 'bg-[#A5D6A7]/20') }} border-b border-[#A5D6A7] hover:bg-[#A5D6A7]/30 transition">
                            <td class="px-4 py-3 font-medium">{{ $scheduling->date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $scheduling->status === 'Finalizado' ? 'bg-green-100 text-green-700 border border-green-300' : 'bg-cyan-100 text-cyan-700 border border-cyan-300' }}">
                                    {{ $scheduling->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ $scheduling->zone?->name }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-[#F4C542]/30 px-3 py-1 text-xs font-semibold text-[#856404]">
                                    {{ $scheduling->shift?->name }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="rounded-md border border-gray-200 bg-white px-3 py-2 text-center text-xs">
                                    <div class="font-bold">{{ $scheduling->vehicle?->name }}</div>
                                    <div>{{ $scheduling->vehicle?->plate }}</div>
                                </div>
                            </td>
                            <td class="px-4 py-3">{{ $driver ? trim($driver->first_name.' '.$driver->last_name) : '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="space-y-1">
                                    @forelse ($helpers as $helper)
                                        <div class="rounded bg-gray-100 px-2 py-1 text-xs">{{ trim($helper->first_name.' '.$helper->last_name) }}</div>
                                    @empty
                                        <span>-</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-center gap-1">
                                    @if ($scheduling->status !== 'Finalizado')
                                        <button wire:click="openEdit({{ $scheduling->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-[#F4C542] text-white hover:bg-[#d9aa1f]" title="Modificar">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 3.487a2.25 2.25 0 013.182 3.182l-9.193 9.193a4.5 4.5 0 01-1.897 1.13l-3.17 1.056 1.056-3.17a4.5 4.5 0 011.13-1.897l9.193-9.193z"/></svg>
                                        </button>
                                        <button wire:click="confirmDelete({{ $scheduling->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-[#E53935] text-white hover:bg-[#C62828]" title="Eliminar">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2M7 7l1 12a2 2 0 002 2h4a2 2 0 002-2l1-12"/></svg>
                                        </button>
                                        <button wire:click="finish({{ $scheduling->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-[#007bff] text-white hover:bg-[#0069d9]" title="Finalizar">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    @endif
                                    <button wire:click="openHistory({{ $scheduling->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-[#0ea5e9] text-white hover:bg-[#0284c7]" title="Historial">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-9-9"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-sm text-[#333333]">
                                {{ __('No hay programaciones registradas.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-[#A5D6A7]">
            {{ $this->schedulings->links() }}
        </div>
    </div>

    <flux:modal name="scheduling-form" wire:close="closeModal" class="w-[96vw]! md:w-[1320px]! max-w-none! max-h-[92vh] overflow-y-auto">
        <form wire:submit="save" class="space-y-5" novalidate>
            <div class="bg-[#2E8B57] px-6 py-4 text-white">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg" class="text-white">{{ $modalTitle }}</flux:heading>
                </div>
            </div>

            <div class="px-6 space-y-4">
                <div class="grid gap-4 md:grid-cols-[1fr_1fr_260px]">
                    <flux:input type="date" wire:model.live="start_date" label="Fecha de inicio *" />
                    <flux:input type="date" wire:model.live="end_date" label="Fecha de fin *" />
                    <div class="flex items-end">
                        <flux:button type="button" wire:click="validateAvailability" icon="check" class="w-full bg-[#0ea5e9] text-white hover:bg-[#0284c7]">
                            {{ __('Validar disponibilidad') }}
                        </flux:button>
                    </div>
                </div>

                <flux:select wire:model.live="staff_group_id" label="Grupo de Personal *">
                    <option value="">{{ __('Seleccione un grupo') }}</option>
                    @foreach ($this->groups as $group)
                        <option value="{{ $group->id }}">{{ $group->name }} - {{ $group->zone?->name }} - {{ $group->shift?->name }}</option>
                    @endforeach
                </flux:select>
                <p class="text-xs text-[#666666]">{{ __('Busque por nombre, zona o turno') }}</p>

                @if ($formChangedAfterValidation)
                    <div class="rounded-md border border-cyan-400 bg-cyan-50 px-4 py-3 text-sm font-semibold text-cyan-700">
                        {{ __('Los datos han cambiado. Valide la disponibilidad nuevamente.') }}
                    </div>
                @endif

                @if ($availabilityChecked && ! $availabilityValid)
                    <div class="max-h-48 overflow-y-auto rounded-md bg-[#E53935] px-5 py-4 text-sm font-semibold leading-relaxed text-white">
                        <div class="mb-2 font-bold">{{ __('Hay errores que corregir') }}</div>
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach ($availabilityErrors as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        @if (! empty($availabilitySuggestions))
                            <div class="mt-3 font-bold">{{ __('Sugerencias:') }}</div>
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach (array_unique($availabilitySuggestions) as $suggestion)
                                    <li>{{ $suggestion }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @elseif ($availabilityChecked && $availabilityValid)
                    <div class="rounded-md bg-[#28a745] px-5 py-4 text-sm font-semibold text-white">
                        {{ __('Todo esta correcto. Puede guardar la programacion.') }}
                    </div>
                @endif

                <div class="grid gap-4 rounded-md border border-gray-200 bg-gray-50 p-4 md:grid-cols-4">
                    <flux:select wire:model.live="zone_id" label="Zona" :disabled="filled($staff_group_id) || filled($editingId)">
                        <option value="">{{ __('Seleccione') }}</option>
                        @foreach ($this->zones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="shift_id" label="Turno" :disabled="filled($staff_group_id) || filled($editingId)">
                        <option value="">{{ __('Seleccione') }}</option>
                        @foreach ($this->shifts as $shift)
                            <option value="{{ $shift->id }}">{{ $shift->name }} ({{ substr($shift->hour_in, 0, 5) }} - {{ substr($shift->hour_out, 0, 5) }})</option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="vehicle_id" label="Vehiculo" :disabled="filled($staff_group_id) || filled($editingId)">
                        <option value="">{{ __('Seleccione') }}</option>
                        @foreach ($this->vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}">{{ $vehicle->name }} - {{ $vehicle->plate }}</option>
                        @endforeach
                    </flux:select>
                    <div>
                        <label class="block text-sm font-medium text-[#333333] mb-2">{{ __('Grupo') }}</label>
                        <div class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold">
                            {{ optional($this->groups->firstWhere('id', $staff_group_id))->name ?? '-' }}
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <flux:select wire:model.live="driver_id" label="Conductor *" :disabled="filled($staff_group_id) || filled($editingId)">
                        <option value="">{{ __('Seleccione') }}</option>
                        @foreach ($this->employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="helper_one_id" label="Ayudante 1" :disabled="filled($staff_group_id) || filled($editingId)">
                        <option value="">{{ filled($staff_group_id) ? __('Vehiculo sin capacidad para un ayudante 1') : __('Seleccione') }}</option>
                        @foreach ($this->employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="helper_two_id" label="Ayudante 2" :disabled="filled($staff_group_id) || filled($editingId)">
                        <option value="">{{ filled($staff_group_id) ? __('Vehiculo sin capacidad para un ayudante 2') : __('Seleccione') }}</option>
                        @foreach ($this->employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[#333333] mb-2">{{ __('Dias de trabajo *') }}</label>
                    <div class="flex flex-wrap gap-4 text-sm">
                        @foreach ([1 => 'Lunes', 2 => 'Martes', 3 => 'Miercoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sabado', 7 => 'Domingo'] as $day => $label)
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" value="{{ $day }}" wire:model.live="work_days" disabled class="rounded border-gray-300 text-[#2E8B57] focus:ring-[#2E8B57] disabled:opacity-80">
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('work_days') <span class="mt-1 block text-xs text-[#E53935]">{{ $message }}</span> @enderror
                </div>

                <flux:textarea wire:model="notes" label="Observaciones" rows="3" placeholder="Observaciones adicionales..." />
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4">
                <flux:button type="button" variant="danger" wire:click="closeModal" class="bg-[#E53935] text-white hover:bg-[#C62828]">
                    {{ __('Cancelar') }}
                </flux:button>
                <flux:button type="submit" variant="primary" :disabled="! $availabilityChecked || ! $availabilityValid || $formChangedAfterValidation" class="bg-[#007bff] text-white hover:bg-[#0069d9] disabled:opacity-50">
                    {{ __('Guardar') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="confirm-delete" class="md:w-100">
        <div class="space-y-5">
            <flux:heading size="lg" class="text-[#E53935]">{{ __('Confirmar eliminacion') }}</flux:heading>
            <flux:text>{{ __('Estas seguro de eliminar esta programacion?') }}</flux:text>
            <div class="flex justify-end gap-3">
                <flux:button x-on:click="Flux.modal('confirm-delete').close()" type="button">{{ __('Cancelar') }}</flux:button>
                <flux:button wire:click="delete" variant="danger" class="bg-[#E53935] text-white hover:bg-[#C62828]">{{ __('Eliminar') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="history-modal" class="md:w-[640px]">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Historial de cambios') }}</flux:heading>
            <div class="max-h-96 space-y-3 overflow-y-auto">
                @forelse ($this->histories as $history)
                    <div class="rounded-lg border border-gray-200 p-3 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-[#2E8B57]">{{ $history->action }}</span>
                            <span class="text-xs text-gray-500">{{ $history->created_at?->format('d/m/Y H:i') }}</span>
                        </div>
                        <p class="mt-1 text-gray-700">{{ $history->description }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $history->user?->name ?? 'Sistema' }}</p>
                    </div>
                @empty
                    <div class="rounded-lg border border-gray-200 p-6 text-center text-sm text-gray-500">
                        {{ __('Sin cambios registrados.') }}
                    </div>
                @endforelse
            </div>
            <div class="flex justify-end">
                <flux:button x-on:click="Flux.modal('history-modal').close()" type="button">{{ __('Cerrar') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
