<?php

use Livewire\Volt\Component;
use App\Models\Scheduling;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\GroupDetail;
use App\Models\SchedulingHistory;
use App\Models\Shift;
use App\Models\Vehicle;
use App\Models\Vacation;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Flux\Flux;

new class extends Component {

    // ── Filtros del dashboard ──────────────────────────────────────────────
    public string $date = '';
    public ?int $shiftFilter = null;

    // ── Estadísticas y cards ──────────────────────────────────────────────
    public array $stats = [];
    public array $zones = [];

    // ── Estado del modal de cambios ───────────────────────────────────────
    public ?int $editingId = null;

    public ?int $shift_id        = null;
    public ?int $vehicle_id      = null;
    public ?int $driver_id       = null;
    public array $helper_ids     = [];

    public ?int    $change_shift_id   = null;
    public ?int    $change_vehicle_id = null;
    public string  $change_person_role = '';
    public ?int    $change_person_id   = null;

    public string $change_turn_reason_preset    = '';
    public string $change_vehicle_reason_preset = '';
    public string $change_person_reason_preset  = '';

    public string $change_turn_reason    = '';
    public string $change_vehicle_reason = '';
    public string $change_person_reason  = '';

    public array  $registeredChanges = [];
    public array  $availabilityErrors = [];

    public string $turnChangeFeedback        = '';
    public string $turnChangeFeedbackType    = '';
    public string $vehicleChangeFeedback     = '';
    public string $vehicleChangeFeedbackType = '';
    public string $personChangeFeedback      = '';
    public string $personChangeFeedbackType  = '';

    // ── Computed ──────────────────────────────────────────────────────────

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
    public function drivers()
    {
        $type = EmployeeType::where('name', 'Conductor')->first();
        if (! $type) return collect();
        return Employee::where('employee_type_id', $type->id)
            ->where('active', true)
            ->whereHas('contracts', fn ($q) => $q->where('is_active', true))
            ->orderBy('first_name')->get();
    }

    #[Computed]
    public function helpersList()
    {
        $type = EmployeeType::where('name', 'Ayudante')->first();
        if (! $type) return collect();
        return Employee::where('employee_type_id', $type->id)
            ->where('active', true)
            ->whereHas('contracts', fn ($q) => $q->where('is_active', true))
            ->orderBy('first_name')->get();
    }

    #[Computed]
    public function maxHelpers(): int
    {
        if (! $this->vehicle_id) return 0;
        $vehicle = Vehicle::find($this->vehicle_id);
        return $vehicle ? max(0, ($vehicle->occupant_capacity ?? 1) - 1) : 0;
    }

    // ── Lifecycle ─────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->date = now()->toDateString();
        $this->loadData();
    }

    public function updatedDate(): void        { $this->loadData(); }
    public function updatedShiftFilter(): void { $this->loadData(); }
    public function buscar(): void             { $this->loadData(); }

    public function updatedChangeTurnReasonPreset($v): void    { $this->change_turn_reason    = $v ?: ''; }
    public function updatedChangeVehicleReasonPreset($v): void { $this->change_vehicle_reason = $v ?: ''; }
    public function updatedChangePersonReasonPreset($v): void  { $this->change_person_reason  = $v ?: ''; }

    // ── Carga del dashboard ───────────────────────────────────────────────

    private function loadData(): void
    {
        $date  = Carbon::parse($this->date)->toDateString();
        $query = Scheduling::with(['zone', 'shift', 'vehicle', 'groupDetails.employee'])
            ->whereDate('date', $date);

        if ($this->shiftFilter) {
            $query->where('shift_id', $this->shiftFilter);
        }

        $schedulings    = $query->get();
        $schedulingIds  = $schedulings->pluck('id');

        $attendances = Attendance::whereIn('employee_id', function ($q) use ($schedulingIds) {
                $q->select('employee_id')->from('group_details')
                  ->whereIn('scheduling_id', $schedulingIds);
            })
            ->whereDate('attendance_date', $date)
            ->where('type', 'ingreso')
            ->where('status', 'presente')
            ->pluck('employee_id')
            ->flip();

        $total = $completadas = $incompletas = $totalFaltantes = 0;
        $zonesData = [];

        foreach ($schedulings as $scheduling) {
            $employees  = $scheduling->groupDetails;
            $cnt        = $employees->count();
            $presentes  = $employees->filter(fn ($gd) => isset($attendances[$gd->employee_id]))->count();
            $faltantes  = $cnt - $presentes;
            $totalFaltantes += $faltantes;
            $completa   = $faltantes === 0 && $cnt > 0;
            $completa ? $completadas++ : $incompletas++;
            $total++;

            $zonesData[] = [
                'id'          => $scheduling->id,
                'zona'        => $scheduling->zone?->name  ?? '—',
                'turno'       => $scheduling->shift?->name ?? '—',
                'turno_horas' => $scheduling->shift
                    ? substr($scheduling->shift->hour_in,  0, 5) . ' - ' . substr($scheduling->shift->hour_out, 0, 5)
                    : '—',
                'vehiculo'    => $scheduling->vehicle?->plate ?? '—',
                'completa'    => $completa,
                'presentes'   => $presentes,
                'faltantes'   => $faltantes,
                'total'       => $cnt,
                'employees'   => $employees->map(fn ($gd) => [
                    'id'       => $gd->employee_id,
                    'nombre'   => $gd->employee
                        ? $gd->employee->first_name . ' ' . $gd->employee->last_name
                        : '—',
                    'presente' => isset($attendances[$gd->employee_id]),
                ])->toArray(),
            ];
        }

        $this->stats = [
            'total'       => $total,
            'completadas' => $completadas,
            'incompletas' => $incompletas,
            'faltantes'   => $totalFaltantes,
        ];
        $this->zones = $zonesData;
    }

    // ── Abrir modal de cambios ────────────────────────────────────────────

    public function openEditor(int $schedulingId): void
    {
        $scheduling = Scheduling::with('groupDetails.employee')->findOrFail($schedulingId);
        $employees  = $scheduling->groupDetails->pluck('employee_id')->values();

        $this->editingId   = $scheduling->id;
        $this->shift_id    = $scheduling->shift_id;
        $this->vehicle_id  = $scheduling->vehicle_id;
        $this->driver_id   = $employees->get(0);
        $this->helper_ids  = $employees->slice(1)->values()->toArray();

        $this->resetEditorForm(keepBase: true);
        Flux::modal('editor-cambios')->show();
    }

    public function closeEditor(): void
    {
        $this->resetEditorForm();
        Flux::modal('editor-cambios')->close();
        $this->loadData();
    }

    // ── Agregar cambios ───────────────────────────────────────────────────

    public function addTurnChange(): void
    {
        $this->validate([
            'change_shift_id'    => ['required', 'exists:shifts,id'],
            'change_turn_reason' => ['required', 'string'],
        ], [
            'change_shift_id.required'    => 'Seleccione un nuevo turno.',
            'change_turn_reason.required' => 'Ingrese el motivo del cambio de turno.',
        ]);

        if ((int) $this->change_shift_id === (int) $this->shift_id) {
            $this->addError('change_shift_id', 'Seleccione un turno diferente al actual.');
            return;
        }

        $errors = $this->validateState($this->currentState(['shift_id' => $this->change_shift_id]));
        if (! empty($errors)) {
            $this->turnChangeFeedback     = implode(' ', $errors);
            $this->turnChangeFeedbackType = 'error';
            return;
        }

        $this->upsertChange([
            'type'      => 'turn',
            'label'     => 'Turno',
            'field'     => 'shift_id',
            'old_id'    => $this->shift_id,
            'new_id'    => $this->change_shift_id,
            'old_value' => $this->shiftLabel($this->shift_id),
            'new_value' => $this->shiftLabel($this->change_shift_id),
            'reason'    => $this->change_turn_reason,
        ]);

        $this->reset(['change_shift_id', 'change_turn_reason_preset', 'change_turn_reason']);
        $this->turnChangeFeedback     = 'Turno disponible para el cambio';
        $this->turnChangeFeedbackType = 'success';
    }

    public function addVehicleChange(): void
    {
        $this->validate([
            'change_vehicle_id'     => ['required', 'exists:vehicles,id'],
            'change_vehicle_reason' => ['required', 'string'],
        ], [
            'change_vehicle_id.required'     => 'Seleccione un nuevo vehículo.',
            'change_vehicle_reason.required' => 'Ingrese el motivo del cambio de vehículo.',
        ]);

        if ((int) $this->change_vehicle_id === (int) $this->vehicle_id) {
            $this->addError('change_vehicle_id', 'Seleccione un vehículo diferente al actual.');
            return;
        }

        $errors = $this->validateState($this->currentState(['vehicle_id' => $this->change_vehicle_id]));
        if (! empty($errors)) {
            $this->vehicleChangeFeedback     = implode(' ', $errors);
            $this->vehicleChangeFeedbackType = 'error';
            return;
        }

        $this->upsertChange([
            'type'      => 'vehicle',
            'label'     => 'Vehículo',
            'field'     => 'vehicle_id',
            'old_id'    => $this->vehicle_id,
            'new_id'    => $this->change_vehicle_id,
            'old_value' => $this->vehicleLabel($this->vehicle_id),
            'new_value' => $this->vehicleLabel($this->change_vehicle_id),
            'reason'    => $this->change_vehicle_reason,
        ]);

        $this->reset(['change_vehicle_id', 'change_vehicle_reason_preset', 'change_vehicle_reason']);
        $this->vehicleChangeFeedback     = 'Vehículo disponible para el cambio';
        $this->vehicleChangeFeedbackType = 'success';
    }

    public function addPersonChange(): void
    {
        $this->validate([
            'change_person_role'   => ['required', 'string'],
            'change_person_id'     => ['required', 'exists:employees,id'],
            'change_person_reason' => ['required', 'string'],
        ], [
            'change_person_role.required'   => 'Seleccione el personal actual.',
            'change_person_id.required'     => 'Seleccione el nuevo personal.',
            'change_person_reason.required' => 'Ingrese el motivo del cambio.',
        ]);

        $currentId = $this->personIdForRole($this->change_person_role);
        if ((int) $currentId === (int) $this->change_person_id) {
            $this->addError('change_person_id', 'Seleccione un trabajador diferente al actual.');
            return;
        }

        $overrides = [];
        if ($this->change_person_role === 'driver_id') {
            $overrides = ['driver_id' => (int) $this->change_person_id];
        } elseif (str_starts_with($this->change_person_role, 'helper_ids.')) {
            $idx = (int) str_replace('helper_ids.', '', $this->change_person_role);
            $tmp = $this->helper_ids;
            $tmp[$idx] = (int) $this->change_person_id;
            $overrides = ['helper_ids' => $tmp];
        }

        $errors = $this->validateState($this->currentState($overrides));
        if (! empty($errors)) {
            $this->personChangeFeedback     = implode(' ', $errors);
            $this->personChangeFeedbackType = 'error';
            return;
        }

        $oldEmp = Employee::find($currentId);
        $newEmp = Employee::find($this->change_person_id);

        $this->upsertChange([
            'type'      => 'person',
            'label'     => $this->roleLabel($this->change_person_role),
            'field'     => $this->change_person_role,
            'old_id'    => $currentId,
            'new_id'    => $this->change_person_id,
            'old_value' => $this->employeeName($oldEmp),
            'new_value' => $this->employeeName($newEmp),
            'reason'    => $this->change_person_reason,
        ]);

        $this->reset(['change_person_role', 'change_person_id', 'change_person_reason_preset', 'change_person_reason']);
        $this->personChangeFeedback     = 'Personal disponible para el cambio';
        $this->personChangeFeedbackType = 'success';
    }

    public function removeChange(int $index): void
    {
        unset($this->registeredChanges[$index]);
        $this->registeredChanges = array_values($this->registeredChanges);
    }

    // ── Aplicar cambios ───────────────────────────────────────────────────

    public function applyChanges(): void
    {
        if (! $this->editingId) return;

        if (empty($this->registeredChanges)) {
            Flux::toast(variant: 'warning', text: 'Agregue al menos un cambio.');
            return;
        }

        $scheduling = Scheduling::with('groupDetails')->findOrFail($this->editingId);
        $before = [
            'shift_id'   => $scheduling->shift_id,
            'vehicle_id' => $scheduling->vehicle_id,
            'employees'  => $scheduling->groupDetails->pluck('employee_id')->values()->all(),
        ];

        foreach ($this->registeredChanges as $change) {
            if ($change['field'] === 'shift_id')   $this->shift_id   = (int) $change['new_id'];
            if ($change['field'] === 'vehicle_id')  $this->vehicle_id = (int) $change['new_id'];
            if ($change['field'] === 'driver_id')   $this->driver_id  = (int) $change['new_id'];
            if (str_starts_with($change['field'], 'helper_ids.')) {
                $idx = (int) str_replace('helper_ids.', '', $change['field']);
                $this->helper_ids[$idx] = (int) $change['new_id'];
            }
        }

        $this->availabilityErrors = $this->validateState($this->currentState());
        if (! empty($this->availabilityErrors)) {
            Flux::toast(variant: 'warning', text: 'Hay inconsistencias por corregir.');
            return;
        }

        $scheduling->update([
            'shift_id'   => $this->shift_id,
            'vehicle_id' => $this->vehicle_id,
            'status'     => 'Reprogramado',
        ]);

        // Sync group details
        $scheduling->groupDetails()->delete();
        $allIds = collect(array_merge([$this->driver_id], $this->helper_ids))->filter()->unique()->values();
        foreach ($allIds as $empId) {
            $scheduling->groupDetails()->create(['employee_id' => $empId]);
        }

        foreach ($this->registeredChanges as $change) {
            SchedulingHistory::create([
                'scheduling_id' => $scheduling->id,
                'action'        => 'Reprogramacion - ' . $change['label'],
                'description'   => $change['reason'],
                'changes'       => ['before' => $change['old_value'], 'after' => $change['new_value'], 'snapshot_before' => $before],
                'user_id'       => auth()->id(),
            ]);
        }

        Flux::toast(variant: 'success', text: 'Cambios aplicados correctamente.');
        $this->closeEditor();
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function currentState(array $overrides = []): array
    {
        $state = [
            'shift_id'   => $this->shift_id,
            'vehicle_id' => $this->vehicle_id,
            'driver_id'  => $this->driver_id,
            'helper_ids' => $this->helper_ids,
        ];

        foreach ($this->registeredChanges as $change) {
            if (in_array($change['field'], ['shift_id', 'vehicle_id', 'driver_id'], true)) {
                $state[$change['field']] = (int) $change['new_id'];
            } elseif (str_starts_with($change['field'], 'helper_ids.')) {
                $idx = (int) str_replace('helper_ids.', '', $change['field']);
                $state['helper_ids'][$idx] = (int) $change['new_id'];
            }
        }

        return array_merge($state, $overrides);
    }

    private function validateState(array $state): array
    {
        $errors = [];
        $date   = Carbon::parse($this->date)->toDateString();

        $allIds = collect(array_merge([$state['driver_id']], $state['helper_ids'] ?? []))->filter()->map(fn ($id) => (int) $id)->values();

        if ($allIds->count() !== $allIds->unique()->count()) {
            $errors[] = 'Un trabajador no puede ocupar más de un rol.';
        }

        $vehicleConflict = Scheduling::whereDate('date', $date)
            ->where('shift_id', $state['shift_id'])
            ->where('vehicle_id', $state['vehicle_id'])
            ->where('id', '!=', $this->editingId)
            ->exists();

        if ($vehicleConflict) {
            $errors[] = 'El vehículo ya tiene programación en esa fecha y turno.';
        }

        foreach ($allIds as $empId) {
            $conflict = GroupDetail::where('employee_id', $empId)
                ->whereHas('scheduling', fn ($q) => $q->whereDate('date', $date)
                    ->where('shift_id', $state['shift_id'])
                    ->where('id', '!=', $this->editingId))
                ->exists();

            if ($conflict) {
                $emp = Employee::find($empId);
                $errors[] = $this->employeeName($emp) . ' ya tiene programación en ese turno.';
            }

            $vacation = Vacation::where('employee_id', $empId)
                ->where('status', 'Aprobada')
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->exists();

            if ($vacation) {
                $emp = Employee::find($empId);
                $errors[] = $this->employeeName($emp) . ' tiene vacaciones aprobadas.';
            }
        }

        return array_values(array_unique($errors));
    }

    private function upsertChange(array $change): void
    {
        $this->registeredChanges = collect($this->registeredChanges)
            ->reject(fn ($c) => $c['field'] === $change['field'])
            ->push($change)
            ->values()
            ->all();
    }

    private function personIdForRole(string $role): ?int
    {
        if ($role === 'driver_id') return $this->driver_id;
        if (str_starts_with($role, 'helper_ids.')) {
            $idx = (int) str_replace('helper_ids.', '', $role);
            return $this->helper_ids[$idx] ?? null;
        }
        return null;
    }

    public function roleLabel(string $role): string
    {
        if ($role === 'driver_id') return 'Conductor';
        if (str_starts_with($role, 'helper_ids.')) {
            return 'Ayudante ' . ((int) str_replace('helper_ids.', '', $role) + 1);
        }
        return 'Personal';
    }

    public function shiftLabel(?int $id): string
    {
        $s = $this->shifts->firstWhere('id', $id);
        return $s ? $s->name . ' (' . substr($s->hour_in, 0, 5) . ' - ' . substr($s->hour_out, 0, 5) . ')' : '—';
    }

    public function vehicleLabel(?int $id): string
    {
        $v = $this->vehicles->firstWhere('id', $id);
        return $v ? ($v->name ?? '') . ' - ' . ($v->plate ?? '') : '—';
    }

    public function employeeName(?Employee $emp): string
    {
        return $emp ? trim($emp->first_name . ' ' . $emp->last_name) : 'Empleado';
    }

    private function resetEditorForm(bool $keepBase = false): void
    {
        if (! $keepBase) {
            $this->editingId  = null;
            $this->shift_id   = null;
            $this->vehicle_id = null;
            $this->driver_id  = null;
            $this->helper_ids = [];
        }

        $this->reset([
            'change_shift_id', 'change_vehicle_id', 'change_person_role', 'change_person_id',
            'change_turn_reason_preset', 'change_vehicle_reason_preset', 'change_person_reason_preset',
            'change_turn_reason', 'change_vehicle_reason', 'change_person_reason',
            'registeredChanges', 'availabilityErrors',
            'turnChangeFeedback', 'turnChangeFeedbackType',
            'vehicleChangeFeedback', 'vehicleChangeFeedbackType',
            'personChangeFeedback', 'personChangeFeedbackType',
        ]);

        $this->resetErrorBag();
    }
}; ?>

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Dashboard General</h1>
            <p class="text-sm text-gray-500 mt-1">Monitoreo y gestión de programaciones en tiempo real</p>
        </div>
        <a href="{{ route('scheduling.scheduling.index') }}"
           class="inline-flex items-center gap-2 bg-green-700 hover:bg-green-800 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            <i class="fas fa-calendar-alt"></i> Ir al Módulo de Programación
        </a>
    </div>

    {{-- ESTADÍSTICAS --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="bg-white rounded-xl border-t-4 border-t-green-500 border border-green-100 shadow p-5 flex items-center gap-4">
            <div class="bg-green-100 text-green-700 rounded-full p-3"><i class="fas fa-clipboard-list fa-lg"></i></div>
            <div>
                <p class="text-3xl font-bold text-gray-900">{{ $stats['total'] ?? 0 }}</p>
                <p class="text-sm text-gray-500">Total Programaciones</p>
            </div>
        </div>
        <div class="bg-white rounded-xl border-t-4 border-t-green-600 border border-green-100 shadow p-5 flex items-center gap-4">
            <div class="bg-green-100 text-green-600 rounded-full p-3"><i class="fas fa-check-circle fa-lg"></i></div>
            <div>
                <p class="text-3xl font-bold text-gray-900">{{ $stats['completadas'] ?? 0 }}</p>
                <p class="text-sm text-gray-500">Programaciones Completadas</p>
            </div>
        </div>
        <div class="bg-white rounded-xl border-t-4 border-t-yellow-500 border border-yellow-100 shadow p-5 flex items-center gap-4">
            <div class="bg-yellow-100 text-yellow-600 rounded-full p-3"><i class="fas fa-exclamation-triangle fa-lg"></i></div>
            <div>
                <p class="text-3xl font-bold text-gray-900">{{ $stats['incompletas'] ?? 0 }}</p>
                <p class="text-sm text-gray-500">Programaciones Incompletas</p>
            </div>
        </div>
        <div class="bg-white rounded-xl border-t-4 border-t-red-500 border border-red-100 shadow p-5 flex items-center gap-4">
            <div class="bg-red-100 text-red-600 rounded-full p-3"><i class="fas fa-user-times fa-lg"></i></div>
            <div>
                <p class="text-3xl font-bold text-gray-900">{{ $stats['faltantes'] ?? 0 }}</p>
                <p class="text-sm text-gray-500">Personal Faltante</p>
            </div>
        </div>
    </div>

    {{-- FILTROS --}}
    <div class="bg-white rounded-xl border border-green-100 shadow p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex flex-col gap-1">
                <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">
                    <i class="fas fa-calendar-day mr-1"></i> Fecha
                </label>
                <input type="date" wire:model.live="date"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">
                    <i class="fas fa-clock mr-1"></i> Turno
                </label>
                <select wire:model.live="shiftFilter"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">Todos los turnos</option>
                    @foreach ($this->shifts as $shift)
                        <option value="{{ $shift->id }}">{{ $shift->name }}</option>
                    @endforeach
                </select>
            </div>
            <button wire:click="buscar"
                class="inline-flex items-center gap-2 bg-green-700 hover:bg-green-800 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                <i class="fas fa-search"></i> Buscar
            </button>
        </div>
    </div>

    {{-- CARDS POR ZONA --}}
    @if (count($zones) === 0)
        <div class="bg-white rounded-xl border border-green-100 shadow p-10 text-center text-gray-400">
            <i class="fas fa-calendar-times fa-2x mb-3"></i>
            <p class="text-sm">No hay programaciones para la fecha seleccionada.</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($zones as $zone)
                <div class="bg-white rounded-xl border shadow p-5 flex flex-col gap-3
                    {{ $zone['completa'] ? 'border-green-200 border-t-4 border-t-green-500' : 'border-red-200 border-t-4 border-t-red-500' }}">

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-map-marker-alt text-green-600"></i>
                            <span class="font-semibold text-gray-900">{{ $zone['zona'] }}</span>
                        </div>
                        @if ($zone['completa'])
                            <span class="text-xs font-semibold bg-green-100 text-green-700 px-2 py-1 rounded-full">✓ Completo</span>
                        @else
                            <span class="text-xs font-semibold bg-red-100 text-red-700 px-2 py-1 rounded-full">! Incompleto</span>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-sm text-gray-600">
                        <div class="flex flex-col gap-0.5">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-clock text-green-500 w-4"></i>
                                <span class="font-medium">{{ $zone['turno'] }}</span>
                            </div>
                            <span class="text-xs text-gray-400 pl-6">{{ $zone['turno_horas'] }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-truck text-green-500 w-4"></i>
                            <span>{{ $zone['vehiculo'] }}</span>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <div class="flex items-center gap-2 bg-green-50 text-green-700 rounded-lg px-3 py-1.5 text-sm font-medium">
                            <i class="fas fa-user-check"></i>
                            <span>{{ $zone['presentes'] }} Presentes</span>
                        </div>
                        <div class="flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium
                            {{ $zone['faltantes'] > 0 ? 'bg-red-50 text-red-700' : 'bg-gray-50 text-gray-400' }}">
                            <i class="fas fa-user-times"></i>
                            <span>{{ $zone['faltantes'] }} Faltantes</span>
                        </div>
                    </div>

                    @if (!$zone['completa'])
                        <button wire:click="openEditor({{ $zone['id'] }})"
                            class="w-full text-center text-sm font-medium text-white bg-green-700 hover:bg-green-800 rounded-lg py-2 transition">
                            <i class="fas fa-edit mr-1"></i> Ver Detalles / Cambiar Personal
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════
         MODAL EDITOR DE CAMBIOS
    ════════════════════════════════════════════════════════════════════ --}}
    <flux:modal name="editor-cambios" wire:close="closeEditor"
        class="w-[96vw]! md:w-[1100px]! max-w-none! max-h-[92vh] overflow-y-auto">

        <div class="space-y-0">

            {{-- Cabecera --}}
            <div class="bg-[#075985] px-6 py-4 text-white rounded-t-lg">
                <h2 class="text-lg font-bold flex items-center gap-2">
                    <i class="fas fa-edit"></i> Editor de Programación
                </h2>
            </div>

            @php
                $reasonOptions = ['Imprevistos', 'Falta de disponibilidad', 'Mantenimiento', 'Solicitud operativa', 'Reasignación de personal'];
            @endphp

            {{-- Tres columnas de cambio --}}
            <div class="grid gap-4 p-5 md:grid-cols-3">

                {{-- Cambio de Turno --}}
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
                    <div class="bg-[#0ea5e9] px-4 py-3 text-sm font-bold text-white flex items-center gap-2">
                        <i class="fas fa-clock"></i> Cambio de Turno
                    </div>
                    <div class="space-y-3 p-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600 uppercase">Turno Actual</label>
                            <div class="flex items-center gap-2 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                                <i class="fas fa-history text-gray-400"></i>
                                {{ $this->shiftLabel($shift_id) }}
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600 uppercase">Nuevo Turno</label>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-sync text-[#0ea5e9]"></i>
                                <select wire:model="change_shift_id"
                                    class="flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0ea5e9]">
                                    <option value="">Seleccione un turno</option>
                                    @foreach ($this->shifts as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }} ({{ substr($s->hour_in,0,5) }} - {{ substr($s->hour_out,0,5) }})</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('change_shift_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600 uppercase">Motivo predefinido</label>
                            <select wire:model.live="change_turn_reason_preset"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0ea5e9]">
                                <option value="">Seleccione un motivo</option>
                                @foreach ($reasonOptions as $r)
                                    <option value="{{ $r }}">{{ $r }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600 uppercase">Motivo del cambio</label>
                            <textarea wire:model="change_turn_reason" rows="2" placeholder="Ingrese el motivo..."
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0ea5e9]"></textarea>
                            @error('change_turn_reason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button wire:click="addTurnChange"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-md bg-[#0ea5e9] hover:bg-[#0284c7] text-white text-sm font-semibold px-4 py-2 transition">
                            <i class="fas fa-plus"></i> Agregar cambio
                        </button>
                        @if ($turnChangeFeedback)
                            <div class="rounded-md px-3 py-2 text-sm font-semibold
                                {{ $turnChangeFeedbackType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $turnChangeFeedback }}
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Cambio de Vehículo --}}
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
                    <div class="bg-[#22c55e] px-4 py-3 text-sm font-bold text-white flex items-center gap-2">
                        <i class="fas fa-truck"></i> Cambio de Vehículo
                    </div>
                    <div class="space-y-3 p-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600 uppercase">Vehículo Actual</label>
                            <div class="flex items-center gap-2 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                                <i class="fas fa-history text-gray-400"></i>
                                {{ $this->vehicleLabel($vehicle_id) }}
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600 uppercase">Nuevo Vehículo</label>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-sync text-[#22c55e]"></i>
                                <select wire:model="change_vehicle_id"
                                    class="flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#22c55e]">
                                    <option value="">Seleccione un vehículo</option>
                                    @foreach ($this->vehicles as $v)
                                        <option value="{{ $v->id }}">{{ $v->name ?? '' }} - {{ $v->plate ?? '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('change_vehicle_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600 uppercase">Motivo predefinido</label>
                            <select wire:model.live="change_vehicle_reason_preset"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#22c55e]">
                                <option value="">Seleccione un motivo</option>
                                @foreach ($reasonOptions as $r)
                                    <option value="{{ $r }}">{{ $r }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600 uppercase">Motivo del cambio</label>
                            <textarea wire:model="change_vehicle_reason" rows="2" placeholder="Ingrese el motivo..."
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#22c55e]"></textarea>
                            @error('change_vehicle_reason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button wire:click="addVehicleChange"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-md bg-[#22c55e] hover:bg-[#16a34a] text-white text-sm font-semibold px-4 py-2 transition">
                            <i class="fas fa-plus"></i> Agregar cambio
                        </button>
                        @if ($vehicleChangeFeedback)
                            <div class="rounded-md px-3 py-2 text-sm font-semibold
                                {{ $vehicleChangeFeedbackType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $vehicleChangeFeedback }}
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Cambio de Personal --}}
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
                    <div class="bg-[#f59e0b] px-4 py-3 text-sm font-bold text-white flex items-center gap-2">
                        <i class="fas fa-users"></i> Cambio de Personal
                    </div>
                    <div class="space-y-3 p-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600 uppercase">Personal Actual</label>
                            <select wire:model.live="change_person_role"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#f59e0b]">
                                <option value="">Seleccione el personal a cambiar</option>
                                <option value="driver_id">
                                    Conductor — {{ $this->employeeName(App\Models\Employee::find($driver_id)) }}
                                </option>
                                @for ($h = 0; $h < $this->maxHelpers; $h++)
                                    @php
                                        $hId  = $helper_ids[$h] ?? null;
                                        $hEmp = $hId ? App\Models\Employee::find($hId) : null;
                                    @endphp
                                    <option value="helper_ids.{{ $h }}">
                                        Ayudante {{ $h + 1 }} — {{ $hEmp ? $this->employeeName($hEmp) : 'Vacío' }}
                                    </option>
                                @endfor
                            </select>
                            @error('change_person_role') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600 uppercase">Nuevo Personal</label>
                            <select wire:model="change_person_id"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#f59e0b]">
                                <option value="">Seleccione un nuevo personal</option>
                                @php
                                    $list = $change_person_role === 'driver_id' ? $this->drivers : $this->helpersList;
                                @endphp
                                @foreach ($list as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }}</option>
                                @endforeach
                            </select>
                            @error('change_person_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600 uppercase">Motivo predefinido</label>
                            <select wire:model.live="change_person_reason_preset"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#f59e0b]">
                                <option value="">Seleccione un motivo</option>
                                @foreach ($reasonOptions as $r)
                                    <option value="{{ $r }}">{{ $r }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600 uppercase">Motivo del cambio</label>
                            <textarea wire:model="change_person_reason" rows="2" placeholder="Ingrese el motivo..."
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#f59e0b]"></textarea>
                            @error('change_person_reason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button wire:click="addPersonChange"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-md bg-[#f59e0b] hover:bg-[#d97706] text-white text-sm font-semibold px-4 py-2 transition">
                            <i class="fas fa-plus"></i> Agregar cambio
                        </button>
                        @if ($personChangeFeedback)
                            <div class="rounded-md px-3 py-2 text-sm font-semibold
                                {{ $personChangeFeedbackType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $personChangeFeedback }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Errores globales --}}
            @if (! empty($availabilityErrors))
                <div class="mx-5 rounded-md bg-red-600 px-5 py-4 text-sm font-semibold text-white space-y-1">
                    <p class="font-bold">Hay errores que corregir:</p>
                    <ul class="list-disc pl-5">
                        @foreach ($availabilityErrors as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Tabla de cambios registrados --}}
            <div class="mx-5 mb-5 rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="bg-[#075985] px-4 py-3 text-sm font-bold text-white flex items-center gap-2">
                    <i class="fas fa-list-alt"></i> Cambios Registrados
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-left text-xs font-bold uppercase text-gray-600">
                                <th class="border px-4 py-3">Tipo de cambio</th>
                                <th class="border px-4 py-3">Valor anterior</th>
                                <th class="border px-4 py-3">Valor nuevo</th>
                                <th class="border px-4 py-3">Motivo</th>
                                <th class="border px-4 py-3 text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($registeredChanges as $idx => $change)
                                <tr class="border-t">
                                    <td class="border px-4 py-3 font-semibold">{{ $change['label'] }}</td>
                                    <td class="border px-4 py-3 text-gray-500">{{ $change['old_value'] }}</td>
                                    <td class="border px-4 py-3 font-medium text-green-700">{{ $change['new_value'] }}</td>
                                    <td class="border px-4 py-3 text-gray-600">{{ $change['reason'] }}</td>
                                    <td class="border px-4 py-3 text-center">
                                        <button wire:click="removeChange({{ $idx }})"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-red-500 hover:bg-red-700 text-white"
                                            title="Quitar">
                                            <i class="fas fa-trash fa-xs"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-400">
                                        No hay cambios registrados. Agregue cambios usando los paneles superiores.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Botones --}}
            <div class="flex justify-end gap-3 border-t border-gray-200 px-5 py-4">
                <button wire:click="closeEditor"
                    class="inline-flex items-center gap-2 rounded-md bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-5 py-2 transition">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button wire:click="applyChanges"
                    class="inline-flex items-center gap-2 rounded-md bg-[#075985] hover:bg-[#0c4a6e] text-white text-sm font-semibold px-5 py-2 transition">
                    <i class="fas fa-save"></i> Guardar cambios
                </button>
            </div>

        </div>
    </flux:modal>

</div>