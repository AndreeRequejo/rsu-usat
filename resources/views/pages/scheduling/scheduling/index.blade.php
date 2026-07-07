<?php

use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\GroupDetail;
use App\Models\Holiday;
use App\Models\Scheduling;
use App\Models\SchedulingChange;
use App\Models\SchedulingChangeItem;
use App\Models\SchedulingHistory;
use App\Models\Shift;
use App\Models\StaffGroup;
use App\Models\Vacation;
use App\Models\Vehicle;
use App\Models\Zone;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
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

    public array $helper_ids = [];

    public array $work_days = [];

    public string $notes = '';

    public bool $availabilityChecked = false;

    public bool $availabilityValid = false;

    public bool $formChangedAfterValidation = false;

    public array $availabilityErrors = [];

    public array $availabilityWarnings = [];

    public array $availabilitySuggestions = [];

    public string $change_reason = '';

    public ?int $change_shift_id = null;

    public ?int $change_vehicle_id = null;

    public string $change_person_role = '';

    public ?int $change_person_id = null;

    public string $change_turn_reason_preset = '';

    public string $change_vehicle_reason_preset = '';

    public string $change_person_reason_preset = '';

    public string $change_turn_reason = '';

    public string $change_vehicle_reason = '';

    public string $change_person_reason = '';

    public array $registeredChanges = [];

    public string $turnChangeFeedback = '';

    public string $turnChangeFeedbackType = '';

    public string $vehicleChangeFeedback = '';

    public string $vehicleChangeFeedbackType = '';

    public string $personChangeFeedback = '';

    public string $personChangeFeedbackType = '';

    public string $massive_start_date = '';

    public string $massive_end_date = '';

    public string $massive_shift_filter = '';

    public array $massiveGroups = [];

    public array $massiveExcludedHolidayDates = [];

    public array $massiveValidation = [];

    public bool $massiveValidated = false;

    public function mount(): void
    {
        $today = now('America/Lima')->format('Y-m-d');

        $this->filterStart = $today;
        $this->filterEnd = $today;
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
            'staff_group_id' => [$this->editingId ? 'nullable' : 'required', 'exists:staff_groups,id'],
            'zone_id' => ['required', 'exists:zones,id'],
            'shift_id' => ['required', 'exists:shifts,id'],
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'driver_id' => ['required', 'exists:employees,id'],
            'helper_ids' => ['nullable', 'array'],
            'helper_ids.*' => [
                'nullable', 'integer', 'exists:employees,id',
                function ($attribute, $value, $fail) {
                    if (!$value) return;
                    if ((int) $value === (int) $this->driver_id) {
                        $fail('Un ayudante no puede ser el conductor.');
                    }
                    $count = collect($this->helper_ids)->filter(fn ($id) => (int) $id === (int) $value)->count();
                    if ($count > 1) {
                        $fail('Un empleado no puede ocupar mas de un puesto de ayudante.');
                    }
                },
            ],
            'work_days' => ['required', 'array', 'min:1'],
            'notes' => ['nullable', 'string'],
            'change_reason' => [$this->editingId ? 'required' : 'nullable', 'string'],
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
            'work_days.required' => 'Seleccione al menos un dia de trabajo.',
            'work_days.min' => 'Seleccione al menos un dia de trabajo.',
            'change_reason.required' => 'Ingrese el motivo del cambio.',
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
            'helper_ids',
            'work_days',
        ], true) || str_starts_with($property, 'helper_ids.')) {
            $this->markAvailabilityDirty();
        }
    }

    public function updatedStaffGroupId($value): void
    {
        if (! $this->syncStaffGroupValues($value)) {
            return;
        }

        if ($this->editingId) {
            $this->syncSingleDayWorkDay();
        }

        $this->markAvailabilityDirty();
    }

    public function updatedVehicleId(): void
    {
        $this->fillHelperSlots();
        $this->markAvailabilityDirty();
    }

    public function updatedChangeTurnReasonPreset($value): void
    {
        $this->change_turn_reason = $value ?: '';
    }

    public function updatedChangeVehicleReasonPreset($value): void
    {
        $this->change_vehicle_reason = $value ?: '';
    }

    public function updatedChangePersonReasonPreset($value): void
    {
        $this->change_person_reason = $value ?: '';
    }

    public function updatedMassiveStartDate(): void
    {
        $this->syncMassiveHolidaySelection();
        $this->massiveValidation = [];
        $this->massiveValidated = false;
    }

    public function updatedMassiveEndDate(): void
    {
        $this->syncMassiveHolidaySelection();
        $this->massiveValidation = [];
        $this->massiveValidated = false;
    }

    public function updatedMassiveExcludedHolidayDates(): void
    {
        $this->massiveValidation = [];
        $this->massiveValidated = false;
    }

    public function updatedMassiveGroups(): void
    {
        $this->massiveValidation = [];
        $this->massiveValidated = false;
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->modalTitle = 'Nueva Programacion';
        Flux::modal('scheduling-form')->show();
    }

    public function openMassive(): void
    {
        $this->resetMassiveForm();
        $this->loadMassiveGroups();
        Flux::modal('massive-scheduling-form')->show();
    }

    public function closeMassiveModal(): void
    {
        $this->resetMassiveForm();
        Flux::modal('massive-scheduling-form')->close();
    }

    public function setMassiveShiftFilter(string $shiftId = ''): void
    {
        $this->massive_shift_filter = $shiftId;
        $this->loadMassiveGroups();
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
        $this->helper_ids = $employees->slice(1)->values()->toArray();
        $this->fillHelperSlots();
        $this->staff_group_id = $this->matchingStaffGroupId();
        $this->work_days = [$scheduling->date->dayOfWeekIso];
        $this->notes = $scheduling->notes ?? '';
        $this->resetReprogrammingForm();
        $this->markAvailabilityDirty();
        Flux::modal('reprogramming-form')->show();
    }

    public function closeModal(): void
    {
        $this->resetForm();
        Flux::modal('scheduling-form')->close();
    }

    public function validateAvailability(): void
    {
        if (! $this->editingId) {
            $this->syncStaffGroupValues($this->staff_group_id);
        }

        $this->validate();
        $this->availabilityErrors = [];
        $this->availabilityWarnings = [];
        $this->availabilitySuggestions = [];

        $dates = $this->selectedDates();
        if ($dates->isEmpty()) {
            $this->availabilityErrors[] = 'No hay fechas dentro del rango que coincidan con los dias de trabajo seleccionados.';
        }

        $schedulableDates = $this->schedulableDates($dates);

        $this->validateDifferentPeople();
        $this->validateHolidays($dates, $schedulableDates);

        if ($dates->isNotEmpty() && $schedulableDates->isEmpty()) {
            $this->availabilityErrors[] = 'No hay fechas laborables para registrar despues de excluir los feriados.';
        }

        $this->validateContractsAndVacations($schedulableDates);
        $this->validateSchedulingConflicts($schedulableDates);

        $this->availabilityChecked = true;
        $this->availabilityValid = empty($this->availabilityErrors);
        $this->formChangedAfterValidation = false;
    }

    public function save(): void
    {
        if (! $this->editingId) {
            $this->syncStaffGroupValues($this->staff_group_id);
        }

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

        $schedulableDates = $this->schedulableDates($dates);
        if ($schedulableDates->isEmpty()) {
            $this->availabilityErrors = ['No hay fechas laborables para registrar despues de excluir los feriados.'];
            $this->availabilityChecked = true;
            $this->availabilityValid = false;
            Flux::toast(variant: 'warning', text: 'No hay fechas laborables para registrar.');

            return;
        }

        if ($this->editingId) {
            DB::transaction(function () use ($schedulableDates) {
                $scheduling = Scheduling::with('groupDetails')->findOrFail($this->editingId);
                $before = $this->schedulingSnapshot($scheduling);

                $scheduling->update([
                    'date' => $schedulableDates->first()->format('Y-m-d'),
                    'shift_id' => $this->shift_id,
                    'vehicle_id' => $this->vehicle_id,
                    'zone_id' => $this->zone_id,
                    'status' => 'Reprogramado',
                    'notes' => $this->notes,
                ]);
                $this->syncGroupDetails($scheduling);

                $after = $this->schedulingSnapshot($scheduling->fresh('groupDetails'));
                $this->writeHistory($scheduling->id, 'Reprogramacion', $this->change_reason, ['before' => $before, 'after' => $after]);
                $this->recordDetectedSchedulingChanges($scheduling, $before, $after, $this->change_reason);
            });

            Flux::toast(variant: 'success', text: 'Programacion actualizada.');
        } else {
            foreach ($schedulableDates as $date) {
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

    public function closeReprogrammingModal(): void
    {
        $this->resetForm();
        Flux::modal('reprogramming-form')->close();
    }

    public function addTurnChange(): void
    {
        $this->validate([
            'change_shift_id' => ['required', 'exists:shifts,id', 'different:shift_id'],
            'change_turn_reason' => ['required', 'string'],
        ], [
            'change_shift_id.required' => 'Seleccione un nuevo turno.',
            'change_shift_id.different' => 'Seleccione un turno diferente al actual.',
            'change_turn_reason.required' => 'Ingrese el motivo del cambio de turno.',
        ]);

        $errors = $this->validateReprogrammingState($this->reprogrammingState(['shift_id' => $this->change_shift_id]));
        if (! empty($errors)) {
            $this->turnChangeFeedback = implode(' ', $errors);
            $this->turnChangeFeedbackType = 'error';

            return;
        }

        $shift = Shift::find($this->change_shift_id);

        $this->upsertRegisteredChange([
            'type' => 'turn',
            'label' => 'Turno',
            'field' => 'shift_id',
            'old_id' => $this->shift_id,
            'new_id' => $this->change_shift_id,
            'old_value' => $this->shiftLabel($this->shift_id),
            'new_value' => $this->shiftLabel($shift?->id),
            'reason' => $this->change_turn_reason,
        ]);

        $this->reset(['change_shift_id', 'change_turn_reason_preset', 'change_turn_reason']);
        $this->turnChangeFeedback = 'Turno disponible para el cambio';
        $this->turnChangeFeedbackType = 'success';
    }

    public function addVehicleChange(): void
    {
        $this->validate([
            'change_vehicle_id' => ['required', 'exists:vehicles,id', 'different:vehicle_id'],
            'change_vehicle_reason' => ['required', 'string'],
        ], [
            'change_vehicle_id.required' => 'Seleccione un nuevo vehiculo.',
            'change_vehicle_id.different' => 'Seleccione un vehiculo diferente al actual.',
            'change_vehicle_reason.required' => 'Ingrese el motivo del cambio de vehiculo.',
        ]);

        $errors = $this->validateReprogrammingState($this->reprogrammingState(['vehicle_id' => $this->change_vehicle_id]));
        if (! empty($errors)) {
            $this->vehicleChangeFeedback = implode(' ', $errors);
            $this->vehicleChangeFeedbackType = 'error';

            return;
        }

        $vehicle = Vehicle::find($this->change_vehicle_id);

        $this->upsertRegisteredChange([
            'type' => 'vehicle',
            'label' => 'Vehiculo',
            'field' => 'vehicle_id',
            'old_id' => $this->vehicle_id,
            'new_id' => $this->change_vehicle_id,
            'old_value' => $this->vehicleLabel($this->vehicle_id),
            'new_value' => $this->vehicleLabel($vehicle?->id),
            'reason' => $this->change_vehicle_reason,
        ]);

        $this->reset(['change_vehicle_id', 'change_vehicle_reason_preset', 'change_vehicle_reason']);
        $this->vehicleChangeFeedback = 'Vehiculo disponible para el cambio';
        $this->vehicleChangeFeedbackType = 'success';
    }

    public function addPersonChange(): void
    {
        $currentPersonId = $this->personIdForRole($this->change_person_role);

        $this->validate([
            'change_person_role' => ['required', 'string'],
            'change_person_id' => ['required', 'exists:employees,id'],
            'change_person_reason' => ['required', 'string'],
        ], [
            'change_person_role.required' => 'Seleccione el personal actual.',
            'change_person_id.required' => 'Seleccione el nuevo personal.',
            'change_person_reason.required' => 'Ingrese el motivo del cambio de personal.',
        ]);

        if (! $this->validPersonRole($this->change_person_role)) {
            $this->addError('change_person_role', 'Rol de personal invalido.');
            return;
        }

        if ((int) $currentPersonId === (int) $this->change_person_id) {
            $this->addError('change_person_id', 'Seleccione un trabajador diferente al actual.');
            return;
        }

        $overrides = [];
        if ($this->change_person_role === 'driver_id') {
            $overrides = ['driver_id' => (int) $this->change_person_id];
        } elseif (str_starts_with($this->change_person_role, 'helper_ids.')) {
            $index = (int) str_replace('helper_ids.', '', $this->change_person_role);
            $tempHelperIds = $this->helper_ids;
            $tempHelperIds[$index] = (int) $this->change_person_id;
            $overrides = ['helper_ids' => $tempHelperIds];
        }

        $errors = $this->validateReprogrammingState($this->reprogrammingState($overrides));
        if (! empty($errors)) {
            $this->personChangeFeedback = implode(' ', $errors);
            $this->personChangeFeedbackType = 'error';
            return;
        }

        $this->upsertRegisteredChange([
            'type' => 'person',
            'label' => $this->roleLabel($this->change_person_role),
            'field' => $this->change_person_role,
            'old_id' => $currentPersonId,
            'new_id' => $this->change_person_id,
            'old_value' => $this->employeeName(Employee::find($currentPersonId)),
            'new_value' => $this->employeeName(Employee::find($this->change_person_id)),
            'reason' => $this->change_person_reason,
        ]);

        $this->reset(['change_person_role', 'change_person_id', 'change_person_reason_preset', 'change_person_reason']);
        $this->personChangeFeedback = 'Personal disponible para el cambio';
        $this->personChangeFeedbackType = 'success';
    }

    public function removeRegisteredChange(int $index): void
    {
        unset($this->registeredChanges[$index]);
        $this->registeredChanges = array_values($this->registeredChanges);
    }

    public function applyReprogramming(): void
    {
        if (! $this->editingId) {
            return;
        }

        if (empty($this->registeredChanges)) {
            Flux::toast(variant: 'warning', text: 'Agregue al menos un cambio.');

            return;
        }

        foreach ($this->registeredChanges as $change) {
            $this->applyRegisteredChangeToForm($change);
        }

        $this->availabilityErrors = $this->validateReprogrammingState($this->reprogrammingState());

        if (! empty($this->availabilityErrors)) {
            Flux::toast(variant: 'warning', text: 'Hay inconsistencias por corregir.');

            return;
        }

        DB::transaction(function () {
            $scheduling = Scheduling::with('groupDetails')->findOrFail($this->editingId);
            $before = $this->schedulingSnapshot($scheduling);

            $scheduling->update([
                'shift_id' => $this->shift_id,
                'vehicle_id' => $this->vehicle_id,
                'status' => 'Reprogramado',
                'notes' => $this->notes,
            ]);

            $this->syncGroupDetails($scheduling);
            $after = $this->schedulingSnapshot($scheduling->fresh('groupDetails'));

            foreach ($this->registeredChanges as $change) {
                $this->writeHistory($scheduling->id, 'Reprogramacion - '.$change['label'], $change['reason'], [
                    'before' => $change['old_value'],
                    'after' => $change['new_value'],
                    'snapshot_before' => $before,
                    'snapshot_after' => $after,
                ]);

                $this->recordSchedulingChange($scheduling, $change, $before, $after);
            }
        });

        Flux::toast(variant: 'success', text: 'Programacion reprogramada.');
        $this->resetForm();
        Flux::modal('reprogramming-form')->close();
    }

    public function validateMassiveAvailability(): void
    {
        $this->validate([
            'massive_start_date' => ['required', 'date'],
            'massive_end_date' => ['required', 'date', 'after_or_equal:massive_start_date'],
        ], [
            'massive_start_date.required' => 'La fecha de inicio es obligatoria.',
            'massive_end_date.required' => 'La fecha de fin es obligatoria.',
            'massive_end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ]);

        if (empty($this->massiveGroups)) {
            $this->loadMassiveGroups();
        }

        $this->massiveValidated = true;
        $this->massiveValidation = $this->buildMassiveValidation();
    }

    public function removeMassiveGroup(int $groupId): void
    {
        unset($this->massiveGroups[$groupId], $this->massiveValidation[$groupId]);
        $this->massiveGroups = array_filter($this->massiveGroups);
    }

    public function saveMassiveScheduling(): void
    {
        $this->validateMassiveAvailability();

        if ($this->massiveHasErrors()) {
            Flux::toast(variant: 'warning', text: 'Corrija las inconsistencias detectadas.');

            return;
        }

        $created = 0;

        foreach ($this->massiveGroups as $groupId => $groupData) {
            foreach ($this->massiveDatesForGroup($groupData) as $date) {
                $scheduling = Scheduling::create([
                    'date' => $date->format('Y-m-d'),
                    'shift_id' => $groupData['shift_id'],
                    'vehicle_id' => $groupData['vehicle_id'],
                    'zone_id' => $groupData['zone_id'],
                    'status' => 'Programado',
                    'notes' => 'Programacion masiva',
                ]);

                foreach ($this->massiveSelectedEmployeeIds($groupData) as $employeeId) {
                    $scheduling->groupDetails()->create(['employee_id' => $employeeId]);
                }

                $this->writeHistory($scheduling->id, 'Creacion masiva', 'Se genero la programacion masiva desde el grupo '.$groupData['name'].'.', [
                    'group_id' => $groupId,
                    'date' => $date->format('Y-m-d'),
                ]);

                $created++;
            }
        }

        Flux::toast(variant: 'success', text: 'Programacion masiva generada: '.$created.' registro(s).');
        $this->resetMassiveForm();
        Flux::modal('massive-scheduling-form')->close();
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
        $today = now('America/Lima')->format('Y-m-d');

        $this->filterStart = $today;
        $this->filterEnd = $today;
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
    public function maxHelpers(): int
    {
        if (!$this->vehicle_id) return 0;
        $vehicle = Vehicle::find($this->vehicle_id);
        if (!$vehicle || !$vehicle->occupant_capacity) return 0;
        return max(0, $vehicle->occupant_capacity - 1);
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
    public function helpersList()
    {
        $helperType = EmployeeType::where('name', 'Ayudante')->first();
        if (!$helperType) return collect();
        return Employee::where('employee_type_id', $helperType->id)
            ->where('active', true)
            ->whereHas('contracts', fn ($q) => $q->where('is_active', true))
            ->orderBy('first_name')
            ->get();
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

    #[Computed]
    public function historyScheduling()
    {
        if (! $this->historyId) {
            return null;
        }

        return Scheduling::with('groupDetails.employee')->find($this->historyId);
    }

    #[Computed]
    public function massiveHolidays()
    {
        if (! $this->massive_start_date || ! $this->massive_end_date) {
            return collect();
        }

        return Holiday::where('is_active', true)
            ->whereDate('date', '>=', $this->massive_start_date)
            ->whereDate('date', '<=', $this->massive_end_date)
            ->orderBy('date')
            ->get();
    }

    private function fillHelperSlots(): void
    {
        $max = $this->maxHelpers;
        $this->helper_ids = array_pad(
            array_slice($this->helper_ids, 0, $max),
            $max,
            null
        );
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
            ->filter(fn ($date) => in_array($date->dayOfWeekIso, array_map('intval', $this->work_days), true))
            ->values();
    }

    private function schedulableDates($dates)
    {
        $holidayDates = $this->holidaysForDates($dates)
            ->pluck('date')
            ->map(fn ($date) => $date->format('Y-m-d'))
            ->all();

        return $dates
            ->reject(fn ($date) => in_array($date->format('Y-m-d'), $holidayDates, true))
            ->values();
    }

    private function selectedEmployeeIds(): array
    {
        return collect(array_merge([$this->driver_id], $this->helper_ids))
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
        $helperIds = array_values(array_filter($this->helper_ids));

        return StaffGroup::query()
            ->where('zone_id', $this->zone_id)
            ->where('shift_id', $this->shift_id)
            ->where('vehicle_id', $this->vehicle_id)
            ->where('driver_id', $this->driver_id)
            ->get()
            ->first(fn (StaffGroup $group) => $group->helpers->pluck('id')->values()->toArray() === $helperIds)
            ?->id;
    }

    private function syncStaffGroupValues($staffGroupId): bool
    {
        if (! $staffGroupId) {
            return false;
        }

        $group = StaffGroup::find($staffGroupId);
        if (! $group) {
            return false;
        }

        $this->zone_id = $group->zone_id;
        $this->shift_id = $group->shift_id;
        $this->vehicle_id = $group->vehicle_id;
        $this->driver_id = $group->driver_id;
        $this->helper_ids = $group->helpers->pluck('id')->toArray();
        $this->fillHelperSlots();
        $this->work_days = $group->work_days ?? [];

        if ($this->editingId) {
            $this->syncSingleDayWorkDay();
        }

        return true;
    }

    private function resetReprogrammingForm(): void
    {
        $this->reset([
            'change_shift_id',
            'change_vehicle_id',
            'change_person_role',
            'change_person_id',
            'change_turn_reason_preset',
            'change_vehicle_reason_preset',
            'change_person_reason_preset',
            'change_turn_reason',
            'change_vehicle_reason',
            'change_person_reason',
            'registeredChanges',
            'availabilityErrors',
            'turnChangeFeedback',
            'turnChangeFeedbackType',
            'vehicleChangeFeedback',
            'vehicleChangeFeedbackType',
            'personChangeFeedback',
            'personChangeFeedbackType',
        ]);
    }

    private function resetMassiveForm(): void
    {
        $today = now('America/Lima')->format('Y-m-d');

        $this->massive_start_date = $today;
        $this->massive_end_date = $today;
        $this->massive_shift_filter = '';
        $this->massiveGroups = [];
        $this->massiveExcludedHolidayDates = [];
        $this->massiveValidation = [];
        $this->massiveValidated = false;
    }

    private function loadMassiveGroups(): void
    {
        $this->massiveGroups = StaffGroup::with(['zone', 'shift', 'vehicle', 'driver', 'helpers'])
            ->where('active', true)
            ->when($this->massive_shift_filter !== '', fn ($query) => $query->where('shift_id', $this->massive_shift_filter))
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function (StaffGroup $group) {
                return [
                    $group->id => [
                        'id' => $group->id,
                        'name' => $group->name,
                        'zone_id' => $group->zone_id,
                        'zone_name' => $group->zone?->name ?? '-',
                        'shift_id' => $group->shift_id,
                        'shift_name' => $group->shift?->name ?? '-',
                        'vehicle_id' => $group->vehicle_id,
                        'vehicle_label' => $this->vehicleLabel($group->vehicle_id),
                        'vehicle_capacity' => $group->vehicle?->occupant_capacity,
                        'driver_id' => $group->driver_id,
                        'helpers' => $group->helpers->pluck('id')->toArray(),
                        'work_days' => $group->work_days ?? [],
                    ],
                ];
            })
            ->all();

        $this->syncMassiveHolidaySelection();
        $this->massiveValidation = [];
        $this->massiveValidated = false;
    }

    private function syncMassiveHolidaySelection(): void
    {
        $holidayDates = $this->massiveHolidays
            ->map(fn (Holiday $holiday) => $holiday->date->format('Y-m-d'))
            ->all();

        $this->massiveExcludedHolidayDates = array_values(array_intersect($this->massiveExcludedHolidayDates, $holidayDates));

        if (empty($this->massiveExcludedHolidayDates)) {
            $this->massiveExcludedHolidayDates = $holidayDates;
        }
    }

    private function massiveRangeDates()
    {
        if (! $this->massive_start_date || ! $this->massive_end_date) {
            return collect();
        }

        return collect(CarbonPeriod::create($this->massive_start_date, $this->massive_end_date))->values();
    }

    private function massiveDatesForGroup(array $groupData)
    {
        $workDays = array_map('intval', $groupData['work_days'] ?? []);

        return $this->massiveRangeDates()
            ->filter(fn ($date) => in_array($date->dayOfWeekIso, $workDays, true))
            ->reject(fn ($date) => in_array($date->format('Y-m-d'), $this->massiveExcludedHolidayDates, true))
            ->values();
    }

    private function massiveUncoveredDates(array $groupData)
    {
        $workDays = array_map('intval', $groupData['work_days'] ?? []);

        return $this->massiveRangeDates()
            ->reject(fn ($date) => in_array($date->format('Y-m-d'), $this->massiveExcludedHolidayDates, true))
            ->reject(fn ($date) => in_array($date->dayOfWeekIso, $workDays, true))
            ->values();
    }

    private function massiveSelectedEmployeeIds(array $groupData): array
    {
        return collect(array_merge([$groupData['driver_id']], $groupData['helpers'] ?? []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function buildMassiveValidation(): array
    {
        $validation = [];
        $proposedVehicles = [];
        $proposedEmployees = [];

        foreach ($this->massiveGroups as $groupId => $groupData) {
            $errors = [];
            $warnings = [];
            $roleErrors = [];
            $roleWarnings = [];
            $dates = $this->massiveDatesForGroup($groupData);
            $uncoveredDates = $this->massiveUncoveredDates($groupData);
            $employeeIds = $this->massiveSelectedEmployeeIds($groupData);

            if ($dates->isEmpty()) {
                $errors[] = 'No hay dias laborables para programar en el rango seleccionado.';
            }

            if ($uncoveredDates->isNotEmpty()) {
                $warnings[] = 'Dias no cubiertos: '.$uncoveredDates->count().' dia(s) no cubierto(s) (el grupo solo trabaja: '.$this->workDaysLabel($groupData['work_days']).').';
            }

            if (count($employeeIds) !== count(array_unique($employeeIds))) {
                $errors[] = 'Personal duplicado dentro del grupo.';
                $roleErrors['driver_id'][] = 'Duplicado en la misma programacion.';
                foreach ($groupData['helpers'] ?? [] as $i => $helperId) {
                    if ($helperId) {
                        $roleErrors['helper_ids.'.$i][] = 'Duplicado en la misma programacion.';
                    }
                }
            }

            foreach ($dates as $date) {
                $dateKey = $date->format('Y-m-d');
                $vehicleKey = $dateKey.'|'.$groupData['shift_id'].'|'.$groupData['vehicle_id'];

                if (isset($proposedVehicles[$vehicleKey])) {
                    $errors[] = 'Vehiculo duplicado en '.$date->format('d/m/Y').' para el mismo turno.';
                }

                $proposedVehicles[$vehicleKey] = true;

                if (Scheduling::whereDate('date', $dateKey)->where('shift_id', $groupData['shift_id'])->where('vehicle_id', $groupData['vehicle_id'])->exists()) {
                    $errors[] = 'Vehiculo ya programado en '.$date->format('d/m/Y').' para el turno seleccionado.';
                    $warnings[] = 'Programaciones existentes: '.$date->format('d/m/Y').'.';
                }

                foreach (['driver_id' => 'Conductor'] as $field => $label) {
                    $employeeId = $groupData[$field] ?? null;
                    if (! $employeeId) continue;

                    $employeeKey = $dateKey.'|'.$groupData['shift_id'].'|'.$employeeId;
                    $employee = Employee::find($employeeId);

                    if (isset($proposedEmployees[$employeeKey])) {
                        $message = $label.' '.$this->employeeName($employee).': Duplicado en '.$date->format('d/m/Y').'.';
                        $errors[] = $message;
                        $roleErrors[$field][] = $message;
                    }

                    $proposedEmployees[$employeeKey] = true;

                    if (GroupDetail::where('employee_id', $employeeId)
                        ->whereHas('scheduling', fn ($query) => $query->whereDate('date', $dateKey)->where('shift_id', $groupData['shift_id']))
                        ->exists()) {
                        $message = $label.' '.$this->employeeName($employee).': Ya programado en '.$date->format('d/m/Y').'.';
                        $errors[] = $message;
                        $roleErrors[$field][] = $message;
                    }

                    $personProblems = $this->employeeDateProblems($employeeId, $date);
                    foreach ($personProblems as $problem) {
                        $message = $label.' '.$this->employeeName($employee).': '.$problem;
                        $errors[] = $message;
                        $roleErrors[$field][] = $message;
                    }
                }

                foreach ($groupData['helpers'] ?? [] as $i => $helperId) {
                    if (! $helperId) continue;

                    $field = 'helper_ids.'.$i;
                    $label = 'Ayudante '.($i + 1);
                    $employeeKey = $dateKey.'|'.$groupData['shift_id'].'|'.$helperId;
                    $employee = Employee::find($helperId);

                    if (isset($proposedEmployees[$employeeKey])) {
                        $message = $label.' '.$this->employeeName($employee).': Duplicado en '.$date->format('d/m/Y').'.';
                        $errors[] = $message;
                        $roleErrors[$field][] = $message;
                    }

                    $proposedEmployees[$employeeKey] = true;

                    if (GroupDetail::where('employee_id', $helperId)
                        ->whereHas('scheduling', fn ($query) => $query->whereDate('date', $dateKey)->where('shift_id', $groupData['shift_id']))
                        ->exists()) {
                        $message = $label.' '.$this->employeeName($employee).': Ya programado en '.$date->format('d/m/Y').'.';
                        $errors[] = $message;
                        $roleErrors[$field][] = $message;
                    }

                    $personProblems = $this->employeeDateProblems($helperId, $date);
                    foreach ($personProblems as $problem) {
                        $message = $label.' '.$this->employeeName($employee).': '.$problem;
                        $errors[] = $message;
                        $roleErrors[$field][] = $message;
                    }
                }
            }

            $roleFields = array_merge(['driver_id'], array_map(fn ($i) => 'helper_ids.'.$i, array_keys($groupData['helpers'] ?? [])));
            foreach ($roleFields as $field) {
                if (empty($roleErrors[$field])) {
                    $roleWarnings[$field][] = $this->massiveValidated ? 'Disponible para el rango validado.' : 'Seleccione fechas para validar';
                }
            }

            $validation[$groupId] = [
                'errors' => array_values(array_unique($errors)),
                'warnings' => array_values(array_unique($warnings)),
                'role_errors' => $roleErrors,
                'role_warnings' => $roleWarnings,
                'dates_count' => $dates->count(),
            ];
        }

        return $validation;
    }

    private function massiveHasErrors(): bool
    {
        return collect($this->massiveValidation)->contains(fn ($result) => ! empty($result['errors']));
    }

    private function employeeDateProblems(int $employeeId, $date): array
    {
        $employee = Employee::with('contracts')->find($employeeId);
        if (! $employee) {
            return ['Empleado no encontrado.'];
        }

        $problems = [];
        $dateString = $date->format('Y-m-d');

        $hasContract = $employee->contracts()
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $dateString)
            ->where(function ($query) use ($dateString) {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', $dateString);
            })
            ->exists();

        if (! $hasContract) {
            $problems[] = 'Contrato no vigente en '.$date->format('d/m/Y').'.';
        }

        $hasVacation = Vacation::where('employee_id', $employeeId)
            ->where('status', 'Aprobada')
            ->whereDate('start_date', '<=', $dateString)
            ->whereDate('end_date', '>=', $dateString)
            ->exists();

        if ($hasVacation) {
            $problems[] = 'Vacaciones aprobadas en '.$date->format('d/m/Y').'.';
        }

        return $problems;
    }

    public function workDaysLabel(array $workDays): string
    {
        $labels = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miercoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sabado', 7 => 'Domingo'];

        return collect($workDays)
            ->map(fn ($day) => $labels[(int) $day] ?? null)
            ->filter()
            ->implode(', ');
    }

    private function upsertRegisteredChange(array $change): void
    {
        $this->registeredChanges = collect($this->registeredChanges)
            ->reject(fn (array $registered) => $registered['field'] === $change['field'])
            ->push($change)
            ->values()
            ->all();
    }

    private function applyRegisteredChangeToForm(array $change): void
    {
        if ($change['field'] === 'shift_id') {
            $this->shift_id = (int) $change['new_id'];
        }

        if ($change['field'] === 'vehicle_id') {
            $this->vehicle_id = (int) $change['new_id'];
        }

        if ($change['field'] === 'driver_id') {
            $this->driver_id = (int) $change['new_id'];
        }

        if (str_starts_with($change['field'], 'helper_ids.')) {
            $index = (int) str_replace('helper_ids.', '', $change['field']);
            $this->helper_ids[$index] = (int) $change['new_id'];
        }
    }

    private function schedulingSnapshot(Scheduling $scheduling): array
    {
        return [
            'date' => $scheduling->date?->format('Y-m-d'),
            'shift_id' => $scheduling->shift_id,
            'vehicle_id' => $scheduling->vehicle_id,
            'zone_id' => $scheduling->zone_id,
            'status' => $scheduling->status,
            'notes' => $scheduling->notes,
            'employees' => $scheduling->groupDetails->pluck('employee_id')->values()->all(),
        ];
    }

    private function recordDetectedSchedulingChanges(Scheduling $scheduling, array $before, array $after, string $reason): void
    {
        if ((int) ($before['shift_id'] ?? 0) !== (int) ($after['shift_id'] ?? 0)) {
            $this->recordSchedulingChange($scheduling, [
                'type' => 'turn',
                'field' => 'shift_id',
                'old_id' => $before['shift_id'],
                'new_id' => $after['shift_id'],
                'reason' => $reason,
            ], $before, $after);
        }

        if ((int) ($before['vehicle_id'] ?? 0) !== (int) ($after['vehicle_id'] ?? 0)) {
            $this->recordSchedulingChange($scheduling, [
                'type' => 'vehicle',
                'field' => 'vehicle_id',
                'old_id' => $before['vehicle_id'],
                'new_id' => $after['vehicle_id'],
                'reason' => $reason,
            ], $before, $after);
        }

        $beforeEmployees = array_values($before['employees'] ?? []);
        $afterEmployees = array_values($after['employees'] ?? []);

        if ((int) ($beforeEmployees[0] ?? 0) !== (int) ($afterEmployees[0] ?? 0)) {
            $this->recordSchedulingChange($scheduling, [
                'type' => 'driver',
                'field' => 'driver_id',
                'old_id' => $beforeEmployees[0] ?? null,
                'new_id' => $afterEmployees[0] ?? null,
                'reason' => $reason,
            ], $before, $after);
        }

        $helperCount = max(count($beforeEmployees), count($afterEmployees));
        for ($index = 1; $index < $helperCount; $index++) {
            if ((int) ($beforeEmployees[$index] ?? 0) === (int) ($afterEmployees[$index] ?? 0)) {
                continue;
            }

            $this->recordSchedulingChange($scheduling, [
                'type' => 'helper',
                'field' => 'helper_ids.'.($index - 1),
                'old_id' => $beforeEmployees[$index] ?? null,
                'new_id' => $afterEmployees[$index] ?? null,
                'reason' => $reason,
            ], $before, $after);
        }
    }

    private function recordSchedulingChange(Scheduling $scheduling, array $change, array $before, array $after): void
    {
        $changeType = $this->schedulingChangeType($change);

        $record = SchedulingChange::create([
            'user_id' => auth()->id(),
            'change_type' => $changeType,
            'start_date' => $after['date'] ?? $before['date'],
            'end_date' => $after['date'] ?? $before['date'],
            'zone_id' => $after['zone_id'] ?? $before['zone_id'] ?? $scheduling->zone_id,
            'old_shift_id' => $changeType === 'turn' ? $change['old_id'] : null,
            'new_shift_id' => $changeType === 'turn' ? $change['new_id'] : null,
            'old_vehicle_id' => $changeType === 'vehicle' ? $change['old_id'] : null,
            'new_vehicle_id' => $changeType === 'vehicle' ? $change['new_id'] : null,
            'old_person_id' => in_array($changeType, ['driver', 'helper'], true) ? $change['old_id'] : null,
            'new_person_id' => in_array($changeType, ['driver', 'helper'], true) ? $change['new_id'] : null,
            'person_role' => in_array($changeType, ['driver', 'helper'], true) ? $changeType : null,
            'reason_preset' => $change['reason'] ?? null,
            'reason_detail' => null,
            'reason_full' => $change['reason'] ?? null,
            'affected_count' => 1,
        ]);

        SchedulingChangeItem::create([
            'scheduling_change_id' => $record->id,
            'scheduling_id' => $scheduling->id,
            'before' => $before,
            'after' => $after,
        ]);
    }

    private function schedulingChangeType(array $change): string
    {
        if (($change['type'] ?? null) === 'turn') {
            return 'turn';
        }

        if (($change['type'] ?? null) === 'vehicle') {
            return 'vehicle';
        }

        if (($change['field'] ?? null) === 'driver_id') {
            return 'driver';
        }

        return 'helper';
    }

    private function reprogrammingState(array $overrides = []): array
    {
        $state = [
            'shift_id' => $this->shift_id,
            'vehicle_id' => $this->vehicle_id,
            'driver_id' => $this->driver_id,
            'helper_ids' => $this->helper_ids,
        ];

        foreach ($this->registeredChanges as $change) {
            if (in_array($change['field'], ['shift_id', 'vehicle_id', 'driver_id'], true)) {
                $state[$change['field']] = (int) $change['new_id'];
            } elseif (str_starts_with($change['field'], 'helper_ids.')) {
                $index = (int) str_replace('helper_ids.', '', $change['field']);
                $state['helper_ids'][$index] = (int) $change['new_id'];
            }
        }

        return array_merge($state, $overrides);
    }

    private function validateReprogrammingState(array $state): array
    {
        $dates = $this->schedulableDates($this->selectedDates());
        $errors = [];

        if ($dates->isEmpty()) {
            return ['No hay fecha laborable para validar el cambio.'];
        }

        $selectedEmployees = collect(array_merge([$state['driver_id']], $state['helper_ids'] ?? []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($selectedEmployees->count() !== $selectedEmployees->unique()->count()) {
            $errors[] = 'Un trabajador no puede ocupar mas de un rol en la misma programacion.';
        }

        foreach ($dates as $date) {
            $vehicleConflict = Scheduling::whereDate('date', $date->format('Y-m-d'))
                ->where('shift_id', $state['shift_id'])
                ->where('vehicle_id', $state['vehicle_id'])
                ->when($this->editingId, fn ($query) => $query->where('id', '!=', $this->editingId))
                ->exists();

            if ($vehicleConflict) {
                $errors[] = 'El vehiculo ya tiene programacion en '.$date->format('d/m/Y').' para el turno seleccionado.';
            }

            foreach ($selectedEmployees as $employeeId) {
                $employeeConflict = GroupDetail::where('employee_id', $employeeId)
                    ->whereHas('scheduling', function ($query) use ($date, $state) {
                        $query->whereDate('date', $date->format('Y-m-d'))
                            ->where('shift_id', $state['shift_id'])
                            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId));
                    })
                    ->exists();

                if ($employeeConflict) {
                    $errors[] = $this->employeeName(Employee::find($employeeId)).' ya tiene programacion en '.$date->format('d/m/Y').' para el turno seleccionado.';
                }

                $employee = Employee::with('contracts')->find($employeeId);
                if (! $employee) {
                    continue;
                }

                $hasContract = $employee->contracts()
                    ->where('is_active', true)
                    ->whereDate('start_date', '<=', $date->format('Y-m-d'))
                    ->where(function ($query) use ($date) {
                        $query->whereNull('end_date')->orWhereDate('end_date', '>=', $date->format('Y-m-d'));
                    })
                    ->exists();

                if (! $hasContract) {
                    $errors[] = $this->employeeName($employee).' no tiene contrato vigente en '.$date->format('d/m/Y').'.';
                }

                $hasVacation = Vacation::where('employee_id', $employeeId)
                    ->where('status', 'Aprobada')
                    ->whereDate('start_date', '<=', $date->format('Y-m-d'))
                    ->whereDate('end_date', '>=', $date->format('Y-m-d'))
                    ->exists();

                if ($hasVacation) {
                    $errors[] = $this->employeeName($employee).' tiene vacaciones aprobadas en '.$date->format('d/m/Y').'.';
                }
            }
        }

        return array_values(array_unique($errors));
    }

    private function personIdForRole(string $role): ?int
    {
        if ($role === 'driver_id') return $this->driver_id;
        if (str_starts_with($role, 'helper_ids.')) {
            $index = (int) str_replace('helper_ids.', '', $role);
            return $this->helper_ids[$index] ?? null;
        }
        return null;
    }

    private function validPersonRole(string $role): bool
    {
        if ($role === 'driver_id') return true;
        if (str_starts_with($role, 'helper_ids.')) {
            $index = (int) str_replace('helper_ids.', '', $role);
            return $index >= 0 && $index < $this->maxHelpers;
        }
        return false;
    }

    public function roleLabel(string $role): string
    {
        if ($role === 'driver_id') return 'Conductor';
        if (str_starts_with($role, 'helper_ids.')) {
            $index = (int) str_replace('helper_ids.', '', $role) + 1;
            return 'Ayudante '.$index;
        }
        return 'Personal';
    }

    public function shiftLabel(?int $shiftId): string
    {
        $shift = $this->shifts->firstWhere('id', $shiftId);

        if (! $shift) {
            return '-';
        }

        return $shift->name.' ('.substr($shift->hour_in, 0, 5).' - '.substr($shift->hour_out, 0, 5).')';
    }

    public function vehicleLabel(?int $vehicleId): string
    {
        $vehicle = $this->vehicles->firstWhere('id', $vehicleId);

        if (! $vehicle) {
            return '-';
        }

        return $vehicle->name.' - '.$vehicle->plate;
    }

    public function historyTypeLabel(SchedulingHistory $history): string
    {
        if (str_contains($history->action, 'Turno')) {
            return 'Turno';
        }

        if (str_contains($history->action, 'Vehiculo')) {
            return 'Vehiculo';
        }

        if (str_contains($history->action, 'Conductor') || str_contains($history->action, 'Ayudante') || str_contains($history->action, 'Personal')) {
            return 'Personal';
        }

        return $history->action;
    }

    public function historyTypeClass(SchedulingHistory $history): string
    {
        return match ($this->historyTypeLabel($history)) {
            'Turno' => 'bg-[#facc15] text-[#333333]',
            'Vehiculo' => 'bg-[#075985] text-white',
            'Personal' => 'bg-[#22c55e] text-white',
            default => 'bg-gray-200 text-gray-700',
        };
    }

    public function historyDate(SchedulingHistory $history): string
    {
        return $history->created_at?->timezone('America/Lima')->format('d/m/Y') ?? '-';
    }

    public function historyTime(SchedulingHistory $history): string
    {
        return $history->created_at?->timezone('America/Lima')->format('H:i') ?? '';
    }

    public function historyBefore(SchedulingHistory $history): string
    {
        return $this->historyValue($history->changes['before'] ?? null);
    }

    public function historyAfter(SchedulingHistory $history): string
    {
        return $this->historyValue($history->changes['after'] ?? null);
    }

    private function historyValue($value): string
    {
        if (blank($value)) {
            return '-';
        }

        if (is_array($value)) {
            return collect($value)
                ->map(function ($item, $key) {
                    if (is_array($item)) {
                        $item = json_encode($item);
                    }

                    return is_string($key) ? $key.': '.$item : $item;
                })
                ->implode(', ');
        }

        return (string) $value;
    }

    private function validateDifferentPeople(): void
    {
        $selected = array_filter(array_merge([$this->driver_id], $this->helper_ids));
        if (count($selected) !== count(array_unique($selected))) {
            $this->availabilityErrors[] = 'Un trabajador no puede ocupar mas de un rol en la misma programacion.';
        }
    }

    private function validateHolidays($dates, $schedulableDates): void
    {
        $holidays = $this->holidaysForDates($dates);

        if ($holidays->isEmpty()) {
            return;
        }

        $message = 'Se omitiran los dias feriados: '.$this->summarizeHolidays($holidays).'.';

        if ($this->start_date === $this->end_date || $schedulableDates->isEmpty()) {
            $this->availabilityErrors[] = 'No se puede registrar programacion en dias feriados: '.$this->summarizeHolidays($holidays).'.';

            return;
        }

        $this->availabilityWarnings[] = $message;
    }

    private function holidaysForDates($dates)
    {
        $holidayDates = $dates
            ->map(fn ($date) => $date->format('Y-m-d'))
            ->values()
            ->all();

        if (empty($holidayDates)) {
            return collect();
        }

        return Holiday::whereIn('date', $holidayDates)->where('is_active', true)->orderBy('date')->get();
    }

    private function summarizeHolidays($holidays): string
    {
        return $holidays
            ->map(fn (Holiday $holiday) => $holiday->date->format('d/m/Y').' ('.$holiday->name.')')
            ->implode(', ');
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
                            ->where('shift_id', $this->shift_id)
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
        $dates = collect($dates)->sortBy(fn ($date) => $date->format('Y-m-d'))->values();

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

    public function employeeName(?Employee $employee): string
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
            'helper_ids',
            'work_days',
            'notes',
            'change_reason',
            'availabilityChecked',
            'availabilityValid',
            'formChangedAfterValidation',
            'availabilityErrors',
            'availabilityWarnings',
            'availabilitySuggestions',
        ]);

        $this->start_date = now('America/Lima')->format('Y-m-d');
        $this->end_date = now('America/Lima')->format('Y-m-d');
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
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $scheduling->status === 'Finalizado' ? 'bg-green-100 text-green-700 border border-green-300' : ($scheduling->status === 'Reprogramado' ? 'bg-amber-100 text-amber-700 border border-amber-300' : 'bg-cyan-100 text-cyan-700 border border-cyan-300') }}">
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
            <div class="px-6 py-4">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">{{ $modalTitle }}</flux:heading>
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

                <flux:select wire:model.live="staff_group_id" label="Grupo de Personal *" :disabled="filled($editingId)">
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

                @if ($availabilityChecked && ! $formChangedAfterValidation && ! empty($availabilityWarnings))
                    <div class="max-h-40 overflow-y-auto rounded-md border border-amber-300 bg-amber-50 px-5 py-4 text-sm font-semibold leading-relaxed text-amber-800">
                        <div class="mb-2 font-bold">{{ __('Avisos') }}</div>
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach ($availabilityWarnings as $warning)
                                <li>{{ $warning }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($availabilityChecked && ! $formChangedAfterValidation && ! $availabilityValid)
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
                @elseif ($availabilityChecked && ! $formChangedAfterValidation && $availabilityValid)
                    <div class="rounded-md bg-[#28a745] px-5 py-4 text-sm font-semibold text-white">
                        {{ __('Todo esta correcto. Puede guardar la programacion.') }}
                    </div>
                @endif

                <div class="grid gap-4 rounded-md border border-gray-200 bg-gray-50 p-4 md:grid-cols-4">
                    <flux:select wire:model.live="zone_id" label="Zona" disabled>
                        <option value="">{{ __('Seleccione') }}</option>
                        @foreach ($this->zones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="shift_id" label="Turno" :disabled="filled($staff_group_id) && ! filled($editingId)">
                        <option value="">{{ __('Seleccione') }}</option>
                        @foreach ($this->shifts as $shift)
                            <option value="{{ $shift->id }}">{{ $shift->name }} ({{ substr($shift->hour_in, 0, 5) }} - {{ substr($shift->hour_out, 0, 5) }})</option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="vehicle_id" label="Vehiculo" :disabled="filled($staff_group_id) && ! filled($editingId)">
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
                    <flux:select wire:model.live="driver_id" label="Conductor *" :disabled="! filled($editingId)">
                        <option value="">{{ __('Seleccione') }}</option>
                        @foreach ($this->drivers as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                        @endforeach
                    </flux:select>
                </div>

                @php $maxHelpers = $this->maxHelpers; @endphp
                @if ($maxHelpers > 0)
                <div class="grid gap-4 md:grid-cols-{{ min($maxHelpers, 4) }}">
                    @for ($i = 0; $i < $maxHelpers; $i++)
                        <flux:select wire:model.live="helper_ids.{{ $i }}" label="{{ __('Ayudante :num', ['num' => $i + 1]) }}" :disabled="filled($staff_group_id) && ! filled($editingId)">
                            <option value="">{{ __('Seleccione ayudante (opcional)') }}</option>
                            @foreach ($this->helpersList as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                            @endforeach
                        </flux:select>
                    @endfor
                </div>
                @elseif ($vehicle_id)
                    <div class="flex items-center justify-center p-4 text-sm text-gray-400 border border-dashed border-[#A5D6A7] rounded-lg">
                        {{ __('El vehiculo no tiene capacidad para ayudantes') }}
                    </div>
                @endif

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

                @if ($editingId)
                    <flux:textarea wire:model="change_reason" label="Motivo del cambio *" rows="3" placeholder="Ingrese el motivo de la reprogramacion..." />
                @endif

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

    <flux:modal name="massive-scheduling-form" wire:close="closeMassiveModal" class="w-[98vw]! md:w-[1400px]! max-w-none! max-h-[94vh] overflow-y-auto">
        <div class="space-y-5">
            <div class="bg-[#075985] px-5 py-4 text-white">
                <flux:heading size="lg" class="text-white">{{ __('Programacion Masiva') }}</flux:heading>
            </div>

            <div class="px-5 space-y-5">
                <div class="grid gap-4 md:grid-cols-[1fr_1fr_360px]">
                    <flux:input type="date" wire:model.live="massive_start_date" label="Fecha de inicio: *" />
                    <flux:input type="date" wire:model.live="massive_end_date" label="Fecha de fin: *" />
                    <div class="flex items-end">
                        <flux:button type="button" wire:click="validateMassiveAvailability" icon="check-circle" class="w-full border border-[#22c55e] bg-white text-[#22c55e] hover:bg-[#ecfdf5]">
                            {{ __('Validar Disponibilidad') }}
                        </flux:button>
                    </div>
                </div>

                <div>
                    <div class="mb-2 text-sm font-bold">{{ __('Filtrar por Turno:') }}</div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" wire:click="setMassiveShiftFilter('')" class="rounded-md border border-[#0ea5e9] px-3 py-2 text-sm font-semibold {{ $massive_shift_filter === '' ? 'bg-[#0ea5e9] text-white' : 'bg-white text-[#0ea5e9]' }}">
                            {{ __('Todos los Turnos') }}
                        </button>
                        @foreach ($this->shifts as $shift)
                            <button type="button" wire:click="setMassiveShiftFilter('{{ $shift->id }}')" class="rounded-md border border-[#0ea5e9] px-3 py-2 text-sm font-semibold {{ (string) $massive_shift_filter === (string) $shift->id ? 'bg-[#0ea5e9] text-white' : 'bg-white text-[#0ea5e9]' }}">
                                {{ $shift->name }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <div class="mb-2 text-sm font-bold">{{ __('Dias Feriados en el Rango Seleccionado:') }}</div>
                    <div class="rounded-md border border-gray-300 bg-gray-100 p-4">
                        <div class="rounded bg-white px-3 py-2 text-sm font-bold text-[#075985]">
                            {{ __('Feriados encontrados:') }}
                            <span class="ml-2 text-xs font-normal text-gray-500">{{ __('Seleccione los que NO desea programar') }}</span>
                        </div>
                        <div class="mt-3 min-h-20 rounded border border-gray-200 bg-white p-3 text-sm">
                            @forelse ($this->massiveHolidays as $holiday)
                                <label class="mb-2 flex items-center gap-2">
                                    <input type="checkbox" value="{{ $holiday->date->format('Y-m-d') }}" wire:model.live="massiveExcludedHolidayDates" class="rounded border-gray-300 text-[#0ea5e9] focus:ring-[#0ea5e9]">
                                    <span>{{ $holiday->date->format('d/m/Y') }} - {{ $holiday->name }}</span>
                                </label>
                            @empty
                                <div class="text-gray-500">{{ __('Seleccione un rango de fechas para ver los feriados') }}</div>
                            @endforelse
                        </div>
                        <div class="mt-2 text-xs font-semibold text-[#0ea5e9]">
                            {{ __('Los feriados seleccionados NO seran programados, incluso si el grupo trabaja ese dia.') }}
                        </div>
                    </div>
                </div>

                <div class="border-t pt-4">
                    <h2 class="mb-4 text-lg font-bold">{{ __('Grupos de Trabajo') }}</h2>
                    <div class="grid gap-4 xl:grid-cols-3">
                        @forelse ($massiveGroups as $groupId => $group)
                            @php
                                $result = $massiveValidation[$groupId] ?? ['errors' => [], 'warnings' => [], 'role_errors' => [], 'role_warnings' => [], 'dates_count' => 0];
                                $hasErrors = ! empty($result['errors']);
                            @endphp
                            <div class="rounded-md border {{ $hasErrors ? 'border-red-300 bg-red-50' : 'border-gray-200 bg-gray-50' }} p-4">
                                <div class="mb-3 flex items-start justify-between gap-3">
                                    <div class="text-sm font-bold uppercase">{{ $group['name'] }}</div>
                                    <button type="button" wire:click="removeMassiveGroup({{ $groupId }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-red-400 text-red-500 hover:bg-red-50" title="Quitar">
                                        x
                                    </button>
                                </div>

                                <div class="space-y-2 text-sm">
                                    <div><span class="font-bold">Zona:</span> {{ $group['zone_name'] }}</div>
                                    <div><span class="font-bold">Turno:</span> <span class="rounded bg-[#0ea5e9] px-2 py-1 text-xs font-bold text-white">{{ $group['shift_name'] }}</span></div>
                                    <div><span class="font-bold">Dias:</span> {{ $this->workDaysLabel($group['work_days']) }}</div>
                                    <div><span class="font-bold">Vehiculo:</span> <span class="rounded bg-[#0ea5e9] px-2 py-1 text-xs font-bold text-white">{{ $group['vehicle_label'] }}{{ $group['vehicle_capacity'] ? ' (Capacidad: '.$group['vehicle_capacity'].')' : '' }}</span></div>
                                </div>

                                <div class="mt-4 space-y-3">
                                    <div>
                                        <label class="mb-1 block text-sm font-bold">Conductor:</label>
                                        <select wire:model.live="massiveGroups.{{ $groupId }}.driver_id" class="w-full rounded-md border-gray-300 text-sm {{ ! empty($result['role_errors']['driver_id'] ?? []) ? 'border-red-400 bg-red-100' : '' }}">
                                            <option value="">{{ __('Seleccione') }}</option>
                                            @foreach ($this->drivers as $employee)
                                                <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                                            @endforeach
                                        </select>
                                        @if (! empty($result['role_errors']['driver_id'] ?? []))
                                            <div class="mt-1 rounded bg-cyan-100 px-2 py-2 text-xs font-semibold text-cyan-900">
                                                {{ collect($result['role_errors']['driver_id'])->first() }}
                                            </div>
                                        @else
                                            <div class="mt-1 rounded bg-cyan-100 px-2 py-2 text-xs font-semibold text-cyan-900">
                                                {{ collect($result['role_warnings']['driver_id'] ?? ['Seleccione fechas para validar'])->first() }}
                                            </div>
                                        @endif
                                    </div>
                                    @php
                                        $maxHelperSlots = $group['vehicle_capacity'] ? max(0, $group['vehicle_capacity'] - 1) : 0;
                                        $helperCount = max(count($group['helpers'] ?? []), $maxHelperSlots);
                                    @endphp
                                    @for ($h = 0; $h < $helperCount; $h++)
                                        @php
                                            $helperField = 'helper_ids.'.$h;
                                        @endphp
                                        <div>
                                            <label class="mb-1 block text-sm font-bold">{{ __('Ayudante :num', ['num' => $h + 1]) }}:</label>
                                            <select wire:model.live="massiveGroups.{{ $groupId }}.helpers.{{ $h }}" class="w-full rounded-md border-gray-300 text-sm {{ ! empty($result['role_errors'][$helperField] ?? []) ? 'border-red-400 bg-red-100' : '' }}">
                                                <option value="">{{ __('Seleccione') }}</option>
                            @foreach ($this->helpersList as $employee)
                                                    <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                                                @endforeach
                                            </select>
                                            @if (! empty($result['role_errors'][$helperField] ?? []))
                                                <div class="mt-1 rounded bg-cyan-100 px-2 py-2 text-xs font-semibold text-cyan-900">
                                                    {{ collect($result['role_errors'][$helperField])->first() }}
                                                </div>
                                            @else
                                                <div class="mt-1 rounded bg-cyan-100 px-2 py-2 text-xs font-semibold text-cyan-900">
                                                    {{ collect($result['role_warnings'][$helperField] ?? ['Seleccione fechas para validar'])->first() }}
                                                </div>
                                            @endif
                                        </div>
                                    @endfor
                                </div>
                            </div>
                        @empty
                            <div class="rounded-md border border-gray-200 p-8 text-center text-sm text-gray-500 xl:col-span-3">
                                {{ __('No hay grupos activos para el filtro seleccionado.') }}
                            </div>
                        @endforelse
                    </div>
                </div>

                @if ($massiveValidated)
                    <div class="space-y-3 pt-2">
                        <h3 class="text-base font-bold text-[#E53935]">{{ __('Resultado de Validacion General') }}</h3>
                        @foreach ($massiveValidation as $groupId => $result)
                            @php
                                $group = $massiveGroups[$groupId] ?? null;
                            @endphp
                            @if ($group)
                                <div class="overflow-hidden rounded-md border border-[#bfdbfe]">
                                    <div class="flex items-center justify-between bg-[#e0f2fe] px-4 py-3 text-sm font-semibold">
                                        <span>{{ $group['name'] }} - {{ $group['zone_name'] }} - {{ $group['shift_name'] }}</span>
                                        <span class="flex gap-2">
                                            @if (! empty($result['errors']))
                                                <span class="rounded bg-[#E53935] px-2 py-1 text-xs text-white">{{ __('Con Errores') }}</span>
                                            @endif
                                            @if (! empty($result['warnings']))
                                                <span class="rounded bg-[#facc15] px-2 py-1 text-xs text-[#333333]">{{ __('Con Advertencias') }}</span>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="{{ ! empty($result['errors']) ? 'bg-red-100 text-red-600' : 'bg-green-50 text-green-700' }} px-5 py-4 text-sm font-semibold">
                                        @if (! empty($result['warnings']))
                                            <div class="mb-2 text-[#0ea5e9]">
                                                {{ implode(' ', $result['warnings']) }}
                                            </div>
                                        @endif
                                        @if (! empty($result['errors']))
                                            <div class="mb-1 font-bold">{{ __('Errores:') }}</div>
                                            <ul class="list-disc pl-5">
                                                @foreach ($result['errors'] as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            {{ __('Sin errores. Se programaran :count dia(s).', ['count' => $result['dates_count']]) }}
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flex justify-center gap-3 border-t border-gray-200 px-5 py-4">
                <flux:button type="button" variant="danger" wire:click="closeMassiveModal" class="bg-[#E53935] text-white hover:bg-[#C62828]">
                    {{ __('Cancelar') }}
                </flux:button>
                <flux:button type="button" wire:click="saveMassiveScheduling" icon="calendar-days" class="bg-[#60a5fa] text-white hover:bg-[#3b82f6]">
                    {{ __('Guardar') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="reprogramming-form" wire:close="closeReprogrammingModal" class="w-[96vw]! md:w-[1180px]! max-w-none! max-h-[92vh] overflow-y-auto">
        <div class="space-y-5">
            <div class="bg-[#075985] px-6 py-4 text-white">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg" class="text-white">{{ __('Modificar Programacion') }}</flux:heading>
                </div>
            </div>

            @php
                $reasonOptions = [
                    'Imprevistos',
                    'Falta de disponibilidad',
                    'Mantenimiento',
                    'Solicitud operativa',
                    'Reasignacion de personal',
                ];
            @endphp

            <div class="grid gap-4 px-5 md:grid-cols-3">
                <div class="rounded-md border border-gray-200 bg-white shadow-sm">
                    <div class="rounded-t-md bg-[#0ea5e9] px-4 py-3 text-sm font-bold text-white">
                        {{ __('Cambio de turno') }}
                    </div>
                    <div class="space-y-3 p-4">
                        <div>
                            <label class="mb-1 block text-sm font-semibold">{{ __('Turno actual') }}</label>
                            <div class="rounded-md border border-gray-300 bg-gray-100 px-3 py-2 text-sm text-gray-700">
                                {{ $this->shiftLabel($shift_id) }}
                            </div>
                        </div>
                        <flux:select wire:model="change_shift_id" label="Nuevo Turno">
                            <option value="">{{ __('Seleccione un nuevo turno') }}</option>
                            @foreach ($this->shifts as $shift)
                                <option value="{{ $shift->id }}">{{ $shift->name }} ({{ substr($shift->hour_in, 0, 5) }} - {{ substr($shift->hour_out, 0, 5) }})</option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model.live="change_turn_reason_preset" label="Motivo predefinido">
                            <option value="">{{ __('Seleccione un motivo') }}</option>
                            @foreach ($reasonOptions as $reason)
                                <option value="{{ $reason }}">{{ $reason }}</option>
                            @endforeach
                        </flux:select>
                        <flux:textarea wire:model="change_turn_reason" label="Motivo del cambio" rows="2" placeholder="Ingrese el motivo del cambio de turno" />
                        <flux:button type="button" wire:click="addTurnChange" icon="plus" class="bg-[#0ea5e9] text-white hover:bg-[#0284c7]">
                            {{ __('Agregar cambio') }}
                        </flux:button>
                        @if ($turnChangeFeedback)
                            <div class="rounded-md px-4 py-3 text-sm font-bold {{ $turnChangeFeedbackType === 'success' ? 'bg-[#28a745] text-white' : 'bg-[#E53935] text-white' }}">
                                {{ $turnChangeFeedback }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="rounded-md border border-gray-200 bg-white shadow-sm">
                    <div class="rounded-t-md bg-[#facc15] px-4 py-3 text-sm font-bold text-[#333333]">
                        {{ __('Cambio de Vehiculo') }}
                    </div>
                    <div class="space-y-3 p-4">
                        <div>
                            <label class="mb-1 block text-sm font-semibold">{{ __('Vehiculo actual') }}</label>
                            <div class="rounded-md border border-gray-300 bg-gray-100 px-3 py-2 text-sm text-gray-700">
                                {{ $this->vehicleLabel($vehicle_id) }}
                            </div>
                        </div>
                        <flux:select wire:model="change_vehicle_id" label="Nuevo vehiculo">
                            <option value="">{{ __('Seleccione un nuevo vehiculo') }}</option>
                            @foreach ($this->vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}">{{ $vehicle->name }} - {{ $vehicle->plate }}</option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model.live="change_vehicle_reason_preset" label="Motivo predefinido">
                            <option value="">{{ __('Seleccione un motivo') }}</option>
                            @foreach ($reasonOptions as $reason)
                                <option value="{{ $reason }}">{{ $reason }}</option>
                            @endforeach
                        </flux:select>
                        <flux:textarea wire:model="change_vehicle_reason" label="Motivo del cambio" rows="2" placeholder="Ingrese el motivo del cambio de vehiculo" />
                        <flux:button type="button" wire:click="addVehicleChange" icon="plus" class="bg-[#0ea5e9] text-white hover:bg-[#0284c7]">
                            {{ __('Agregar cambio') }}
                        </flux:button>
                        @if ($vehicleChangeFeedback)
                            <div class="rounded-md px-4 py-3 text-sm font-bold {{ $vehicleChangeFeedbackType === 'success' ? 'bg-[#28a745] text-white' : 'bg-[#E53935] text-white' }}">
                                {{ $vehicleChangeFeedback }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="rounded-md border border-gray-200 bg-white shadow-sm">
                    <div class="rounded-t-md bg-[#22c55e] px-4 py-3 text-sm font-bold text-white">
                        {{ __('Cambio de Personal') }}
                    </div>
                    <div class="space-y-3 p-4">
                        <flux:select wire:model.live="change_person_role" label="Personal actual">
                            <option value="">{{ __('Seleccione un personal') }}</option>
                            <option value="driver_id">{{ __('Conductor') }} - {{ $this->employeeName(App\Models\Employee::find($driver_id)) }}</option>
                            @for ($h = 0; $h < $this->maxHelpers; $h++)
                                @php
                                    $helperPersonId = $this->helper_ids[$h] ?? null;
                                    $helperPerson = $helperPersonId ? App\Models\Employee::find($helperPersonId) : null;
                                @endphp
                                <option value="helper_ids.{{ $h }}">{{ __('Ayudante :num', ['num' => $h + 1]) }} - {{ $helperPerson ? $this->employeeName($helperPerson) : __('Vacio') }}</option>
                            @endfor
                        </flux:select>
                        <flux:select wire:model="change_person_id" label="Nuevo personal">
                            <option value="">{{ __('Buscar empleado disponible...') }}</option>
                            @php
                                $personnelList = $change_person_role === 'driver_id' ? $this->drivers : $this->helpersList;
                            @endphp
                            @foreach ($personnelList as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model.live="change_person_reason_preset" label="Motivo predefinido">
                            <option value="">{{ __('Seleccione un motivo') }}</option>
                            @foreach ($reasonOptions as $reason)
                                <option value="{{ $reason }}">{{ $reason }}</option>
                            @endforeach
                        </flux:select>
                        <flux:textarea wire:model="change_person_reason" label="Motivo del cambio" rows="2" placeholder="Ingrese el motivo del cambio de personal" />
                        <flux:button type="button" wire:click="addPersonChange" icon="plus" class="bg-[#0ea5e9] text-white hover:bg-[#0284c7]">
                            {{ __('Agregar cambio') }}
                        </flux:button>
                        @if ($personChangeFeedback)
                            <div class="rounded-md px-4 py-3 text-sm font-bold {{ $personChangeFeedbackType === 'success' ? 'bg-[#28a745] text-white' : 'bg-[#E53935] text-white' }}">
                                {{ $personChangeFeedback }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if (! empty($availabilityErrors))
                <div class="mx-5 rounded-md bg-[#E53935] px-5 py-4 text-sm font-semibold leading-relaxed text-white">
                    <div class="mb-2 font-bold">{{ __('Hay errores que corregir') }}</div>
                    <ul class="list-disc pl-5">
                        @foreach ($availabilityErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mx-5 overflow-hidden rounded-md border border-gray-200 bg-white shadow-sm">
                <div class="bg-[#0ea5e9] px-4 py-3 text-sm font-bold text-white">
                    {{ __('Cambios Registrados') }}
                </div>
                <div class="overflow-x-auto p-4">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-left text-xs font-bold uppercase text-gray-600">
                                <th class="border px-3 py-3">{{ __('Tipo de cambio') }}</th>
                                <th class="border px-3 py-3">{{ __('Valor anterior') }}</th>
                                <th class="border px-3 py-3">{{ __('Valor nuevo') }}</th>
                                <th class="border px-3 py-3">{{ __('Motivo') }}</th>
                                <th class="border px-3 py-3 text-center">{{ __('Accion') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($registeredChanges as $index => $change)
                                <tr>
                                    <td class="border px-3 py-3 font-semibold">{{ $change['label'] }}</td>
                                    <td class="border px-3 py-3">{{ $change['old_value'] }}</td>
                                    <td class="border px-3 py-3">{{ $change['new_value'] }}</td>
                                    <td class="border px-3 py-3">{{ $change['reason'] }}</td>
                                    <td class="border px-3 py-3 text-center">
                                        <button type="button" wire:click="removeRegisteredChange({{ $index }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-[#E53935] text-white hover:bg-[#C62828]" title="Quitar">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2M7 7l1 12a2 2 0 002 2h4a2 2 0 002-2l1-12"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="border px-6 py-10 text-center text-sm text-gray-500">
                                        {{ __('No hay cambios registrados. Agregue cambios usando los botones superiores.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-200 px-5 py-4">
                <flux:button type="button" variant="danger" wire:click="closeReprogrammingModal" class="bg-[#E53935] text-white hover:bg-[#C62828]">
                    {{ __('Cancelar') }}
                </flux:button>
                <flux:button type="button" wire:click="applyReprogramming" variant="primary" class="bg-[#0ea5e9] text-white hover:bg-[#0284c7]">
                    {{ __('Guardar') }}
                </flux:button>
            </div>
        </div>
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

    <flux:modal name="history-modal" class="w-[94vw]! md:w-[960px]! max-w-none! max-h-[92vh] overflow-y-auto">
        <div class="space-y-5">
            <div class="overflow-hidden rounded-md border border-gray-200 bg-white shadow-sm">
                <div class="bg-[#22c55e] px-5 py-4 text-sm font-bold text-white">
                    {{ __('Personal Asignado') }}
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-left text-xs font-bold uppercase text-gray-600">
                                <th class="px-5 py-4">{{ __('Rol') }}</th>
                                <th class="px-5 py-4">{{ __('Nombre') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $historyEmployees = $this->historyScheduling?->groupDetails->pluck('employee')->filter()->values() ?? collect();
                            @endphp
                            @foreach ($historyEmployees as $index => $employee)
                                <tr class="border-t">
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-md px-3 py-2 text-xs font-bold text-white {{ $index === 0 ? 'bg-[#0ea5e9]' : 'bg-[#22c55e]' }}">
                                            {{ $index === 0 ? 'Conductor' : __('Ayudante :num', ['num' => $index]) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 font-semibold">
                                        {{ $employee ? $this->employeeName($employee) : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="overflow-hidden rounded-md border border-gray-200 bg-white shadow-sm">
                <div class="bg-[#0ea5e9] px-5 py-4 text-sm font-bold text-white">
                    {{ __('Historial de Cambios') }}
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-left text-xs font-bold uppercase text-gray-600">
                                <th class="px-5 py-4">{{ __('Fecha') }}</th>
                                <th class="px-5 py-4">{{ __('Tipo') }}</th>
                                <th class="px-5 py-4">{{ __('Anterior') }}</th>
                                <th class="px-5 py-4">{{ __('Nuevo') }}</th>
                                <th class="px-5 py-4">{{ __('Motivo') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->histories as $history)
                                <tr class="border-t align-top">
                                    <td class="px-5 py-5 font-semibold text-[#075985]">
                                        <div>{{ $this->historyDate($history) }}</div>
                                        <div class="text-xs font-normal text-gray-500">{{ $this->historyTime($history) }}</div>
                                    </td>
                                    <td class="px-5 py-5">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $this->historyTypeClass($history) }}">
                                            {{ $this->historyTypeLabel($history) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-5">{{ $this->historyBefore($history) }}</td>
                                    <td class="px-5 py-5">{{ $this->historyAfter($history) }}</td>
                                    <td class="px-5 py-5 text-[#1f4e79]">{{ $history->description }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
                                        {{ __('Sin cambios registrados.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-center">
                <flux:button x-on:click="Flux.modal('history-modal').close()" type="button" variant="danger" class="bg-[#E53935] text-white hover:bg-[#C62828]">
                    {{ __('Cerrar') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
