<?php

use Livewire\Volt\Component;
use App\Models\Scheduling;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\GroupDetail;
use App\Models\SchedulingChange;
use App\Models\SchedulingChangeItem;
use App\Models\Shift;
use App\Models\Vehicle;
use App\Models\Vacation;
use App\Models\Zone;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Flux\Flux;

new class extends Component {

    public string $date = '';
    public ?int $shiftFilter = null;
    public ?int $zoneFilter  = null;

    public ?string $cardFilter = null;

    public array $stats = [];
    public array $zones = [];

    public ?int $editingId = null;

    public ?int $shift_id   = null;
    public ?int $vehicle_id = null;
    public ?int $driver_id  = null;
    public array $helper_ids = [];

    public array $teamAttendance = [];

    public array $change_new_ids = ['driver_id' => null, 'helper_ids' => []];

    public string $change_person_reason_preset = '';
    public string $change_person_reason        = '';

    public array  $registeredChanges   = [];
    public array  $availabilityErrors  = [];

    public string $personChangeFeedback     = '';
    public string $personChangeFeedbackType = '';

    // Zona seleccionada para el modal de "Ver información" (solo lectura).
    public array $viewingZone = [];

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
    public function zonesList()
    {
        return Zone::where('status', 'active')->orderBy('name')->get();
    }

    #[Computed]
    public function maxHelpers(): int
    {
        if (! $this->vehicle_id) return 0;
        $vehicle = Vehicle::find($this->vehicle_id);
        return $vehicle ? max(0, ($vehicle->occupant_capacity ?? 1) - 1) : 0;
    }

    #[Computed]
    public function filteredZones(): array
    {
        $zones = collect($this->zones);

        return match ($this->cardFilter) {
            'completadas' => $zones->where('completa', true)->values()->all(),
            'incompletas', 'faltantes' => $zones->where('completa', false)->values()->all(),
            default       => $zones->values()->all(),
        };
    }

    public function mount(): void
    {
        $this->date = now()->toDateString();
        $this->loadData();
    }

    public function updatedDate(): void        { $this->loadData(); }
    public function updatedShiftFilter(): void { $this->loadData(); }
    public function updatedZoneFilter(): void  { $this->loadData(); }
    public function buscar(): void             { $this->loadData(); }

    public function setCardFilter(string $filter): void
    {
        // Click de nuevo sobre la misma tarjeta quita el filtro.
        $this->cardFilter = $this->cardFilter === $filter ? null : $filter;
    }

    public function updatedChangePersonReasonPreset($v): void { $this->change_person_reason = $v ?: ''; }

    private function loadData(): void
    {
        $date  = Carbon::parse($this->date)->toDateString();
        $query = Scheduling::with(['zone', 'shift', 'vehicle', 'groupDetails.employee'])
            ->whereDate('date', $date);

        if ($this->shiftFilter) {
            $query->where('shift_id', $this->shiftFilter);
        }

        if ($this->zoneFilter) {
            $query->where('zone_id', $this->zoneFilter);
        }

        $schedulings   = $query->get();
        $schedulingIds = $schedulings->pluck('id');

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
                'vehiculo'       => $scheduling->vehicle?->plate ?? '—',
                'vehiculo_nombre' => $scheduling->vehicle?->name ?? '—',
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

        // Las incompletas primero.
        usort($zonesData, fn ($a, $b) => ($a['completa'] <=> $b['completa']));

        $this->stats = [
            'total'       => $total,
            'completadas' => $completadas,
            'incompletas' => $incompletas,
            'faltantes'   => $totalFaltantes,
        ];
        $this->zones = $zonesData;
    }

    public function openEditor(int $schedulingId): void
    {
        $scheduling = Scheduling::with('groupDetails.employee')->findOrFail($schedulingId);
        $employees  = $scheduling->groupDetails->pluck('employee_id')->values();

        $this->editingId   = $scheduling->id;
        $this->shift_id    = $scheduling->shift_id;
        $this->vehicle_id  = $scheduling->vehicle_id;
        $this->driver_id   = $employees->get(0);
        $this->helper_ids  = $employees->slice(1)->values()->toArray();
        $this->loadTeamAttendance();

        $this->resetEditorForm(keepBase: true);
        $this->change_new_ids = ['driver_id' => null, 'helper_ids' => array_fill(0, count($this->helper_ids), null)];
        Flux::modal('editor-personal')->show();
    }

    public function closeEditor(): void
    {
        $this->resetEditorForm();
        Flux::modal('editor-personal')->close();
        $this->loadData();
    }

    /**
     * Muestra la información de solo lectura de una programación completa.
     */
    public function openViewInfo(int $schedulingId): void
    {
        $zone = collect($this->zones)->firstWhere('id', $schedulingId);
        $this->viewingZone = $zone ?? [];
        Flux::modal('ver-info')->show();
    }

    public function closeViewInfo(): void
    {
        $this->viewingZone = [];
        Flux::modal('ver-info')->close();
    }

    /**
     * Calcula qué empleados del equipo actual (conductor + ayudantes) tienen
     * asistencia confirmada (ingreso/presente) para la fecha de la programación.
     */
    private function loadTeamAttendance(): void
    {
        $date = Carbon::parse($this->date)->toDateString();
        $ids  = collect(array_merge([$this->driver_id], $this->helper_ids))->filter()->unique();

        $presentIds = Attendance::whereIn('employee_id', $ids)
            ->whereDate('attendance_date', $date)
            ->where('type', 'ingreso')
            ->where('status', 'presente')
            ->pluck('employee_id')
            ->flip();

        $this->teamAttendance = $ids->mapWithKeys(fn ($id) => [$id => isset($presentIds[$id])])->all();
    }

    /**
     * Empleados candidatos para reemplazar un rol (Conductor / Ayudante).
     * Deben ser del tipo correcto, activos, con contrato vigente,
     * tener asistencia confirmada (ingreso/presente) el día seleccionado
     * y no estar ya asignados a ninguna programación de este mismo turno
     * (es decir, personal disponible para cubrir el turno actual).
     */
    public function eligibleEmployees(string $typeName): \Illuminate\Support\Collection
    {
        $type = EmployeeType::where('name', $typeName)->first();
        if (! $type || ! $this->shift_id) return collect();

        $date = Carbon::parse($this->date)->toDateString();

        $presentIds = Attendance::whereDate('attendance_date', $date)
            ->where('type', 'ingreso')
            ->where('status', 'presente')
            ->pluck('employee_id');

        $alreadyAssignedIds = GroupDetail::whereHas('scheduling', function ($q) use ($date) {
                $q->whereDate('date', $date)->where('shift_id', $this->shift_id);
            })
            ->pluck('employee_id');

        $currentTeam = collect(array_merge([$this->driver_id], $this->helper_ids))->filter();

        return Employee::where('employee_type_id', $type->id)
            ->where('active', true)
            ->whereHas('contracts', fn ($q) => $q->where('is_active', true))
            ->whereIn('id', $presentIds)
            ->whereNotIn('id', $alreadyAssignedIds)
            ->whereNotIn('id', $currentTeam)
            ->orderBy('first_name')
            ->get();
    }

    public function addPersonChange(): void
    {
        $this->validate([
            'change_person_reason' => ['required', 'string'],
        ], [
            'change_person_reason.required' => 'Ingrese el motivo del cambio.',
        ]);

        // Recolecta todas las filas donde se eligió un reemplazo.
        $roleUpdates = [];

        if (! empty($this->change_new_ids['driver_id'])) {
            $roleUpdates['driver_id'] = (int) $this->change_new_ids['driver_id'];
        }
        foreach ($this->change_new_ids['helper_ids'] ?? [] as $idx => $newId) {
            if (! empty($newId)) {
                $roleUpdates["helper_ids.$idx"] = (int) $newId;
            }
        }

        if (empty($roleUpdates)) {
            $this->personChangeFeedback     = 'Seleccione al menos un reemplazo de personal.';
            $this->personChangeFeedbackType = 'error';
            return;
        }

        foreach ($roleUpdates as $role => $newId) {
            $currentId = $this->personIdForRole($role);
            if ((int) $currentId === $newId) {
                $this->personChangeFeedback     = 'Seleccione un trabajador diferente al actual para ' . $this->roleLabel($role) . '.';
                $this->personChangeFeedbackType = 'error';
                return;
            }
        }

        $state = $this->currentState();
        foreach ($roleUpdates as $role => $newId) {
            if ($role === 'driver_id') {
                $state['driver_id'] = $newId;
            } elseif (str_starts_with($role, 'helper_ids.')) {
                $idx = (int) str_replace('helper_ids.', '', $role);
                $state['helper_ids'][$idx] = $newId;
            }
        }

        $errors = $this->validateState($state);
        if (! empty($errors)) {
            $this->personChangeFeedback     = implode(' ', $errors);
            $this->personChangeFeedbackType = 'error';
            return;
        }

        foreach ($roleUpdates as $role => $newId) {
            $currentId = $this->personIdForRole($role);
            $oldEmp    = Employee::find($currentId);
            $newEmp    = Employee::find($newId);

            $this->upsertChange([
                'type'      => 'person',
                'label'     => $this->roleLabel($role),
                'field'     => $role,
                'old_id'    => $currentId,
                'new_id'    => $newId,
                'old_value' => $this->employeeName($oldEmp),
                'new_value' => $this->employeeName($newEmp),
                'reason'    => $this->change_person_reason,
            ]);
        }

        $this->change_new_ids = ['driver_id' => null, 'helper_ids' => array_fill(0, count($this->helper_ids), null)];
        $this->reset(['change_person_reason_preset', 'change_person_reason']);
        $this->personChangeFeedback     = 'Personal disponible para el cambio';
        $this->personChangeFeedbackType = 'success';
    }

    public function removeChange(int $index): void
    {
        unset($this->registeredChanges[$index]);
        $this->registeredChanges = array_values($this->registeredChanges);
    }

    public function applyChanges(): void
    {
        if (! $this->editingId) return;

        if (empty($this->registeredChanges)) {
            Flux::toast(variant: 'warning', text: 'Agregue al menos un cambio.');
            return;
        }

        $scheduling = Scheduling::with('groupDetails')->findOrFail($this->editingId);
        $date       = $scheduling->date?->toDateString() ?? Carbon::parse($this->date)->toDateString();

        $beforeSnapshot = [
            'employees' => $scheduling->groupDetails->pluck('employee_id')->values()->all(),
        ];

        foreach ($this->registeredChanges as $change) {
            if ($change['field'] === 'driver_id') $this->driver_id = (int) $change['new_id'];
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

        $scheduling->update(['status' => 'Reprogramado']);

        $scheduling->groupDetails()->delete();
        $allIds = collect(array_merge([$this->driver_id], $this->helper_ids))
            ->filter()->unique()->values();
        foreach ($allIds as $empId) {
            $scheduling->groupDetails()->create(['employee_id' => $empId]);
        }

        foreach ($this->registeredChanges as $change) {
            $changeType = str_starts_with($change['field'], 'helper_ids.') ? 'helper' : 'driver';

            $record = SchedulingChange::create([
                'user_id'        => auth()->id(),
                'change_type'    => $changeType,
                'start_date'     => $date,
                'end_date'       => $date,
                'zone_id'        => $scheduling->zone_id,
                'old_shift_id'   => null,
                'new_shift_id'   => null,
                'old_vehicle_id' => null,
                'new_vehicle_id' => null,
                'old_person_id'  => (int) $change['old_id'],
                'new_person_id'  => (int) $change['new_id'],
                'person_role'    => $changeType,
                'reason_preset'  => $change['reason'] ?? null,
                'reason_detail'  => null,
                'reason_full'    => $change['reason'] ?? null,
                'affected_count' => 1,
            ]);

            SchedulingChangeItem::create([
                'scheduling_change_id' => $record->id,
                'scheduling_id'        => $scheduling->id,
                'before'               => $beforeSnapshot,
                'after'                => [
                    'employees' => $allIds->all(),
                ],
            ]);
        }

        Flux::toast(variant: 'success', text: 'Cambios aplicados correctamente.');
        $this->closeEditor();
    }

    private function currentState(array $overrides = []): array
    {
        $state = [
            'shift_id'   => $this->shift_id,
            'vehicle_id' => $this->vehicle_id,
            'driver_id'  => $this->driver_id,
            'helper_ids' => $this->helper_ids,
        ];

        foreach ($this->registeredChanges as $change) {
            if ($change['field'] === 'driver_id') {
                $state['driver_id'] = (int) $change['new_id'];
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
            $this->teamAttendance = [];
        }

        $this->change_new_ids = ['driver_id' => null, 'helper_ids' => array_fill(0, count($this->helper_ids), null)];

        $this->reset([
            'change_person_reason_preset', 'change_person_reason',
            'registeredChanges', 'availabilityErrors',
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

    {{-- ESTADÍSTICAS (clickeables como filtros) --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <button type="button" wire:click="setCardFilter('total')"
            class="text-left bg-white rounded-xl border-t-4 border-t-green-500 border border-green-100 shadow p-5 flex items-center gap-4 transition hover:shadow-md
                {{ $cardFilter === null ? 'ring-2 ring-green-400' : '' }}">
            <div class="bg-green-100 text-green-700 rounded-full p-3"><i class="fas fa-clipboard-list fa-lg"></i></div>
            <div>
                <p class="text-3xl font-bold text-gray-900">{{ $stats['total'] ?? 0 }}</p>
                <p class="text-sm text-gray-500">Total Programaciones</p>
            </div>
        </button>
        <button type="button" wire:click="setCardFilter('completadas')"
            class="text-left bg-white rounded-xl border-t-4 border-t-green-600 border border-green-100 shadow p-5 flex items-center gap-4 transition hover:shadow-md
                {{ $cardFilter === 'completadas' ? 'ring-2 ring-green-400' : '' }}">
            <div class="bg-green-100 text-green-600 rounded-full p-3"><i class="fas fa-check-circle fa-lg"></i></div>
            <div>
                <p class="text-3xl font-bold text-gray-900">{{ $stats['completadas'] ?? 0 }}</p>
                <p class="text-sm text-gray-500">Programaciones Completadas</p>
            </div>
        </button>
        <button type="button" wire:click="setCardFilter('incompletas')"
            class="text-left bg-white rounded-xl border-t-4 border-t-yellow-500 border border-yellow-100 shadow p-5 flex items-center gap-4 transition hover:shadow-md
                {{ $cardFilter === 'incompletas' ? 'ring-2 ring-yellow-400' : '' }}">
            <div class="bg-yellow-100 text-yellow-600 rounded-full p-3"><i class="fas fa-exclamation-triangle fa-lg"></i></div>
            <div>
                <p class="text-3xl font-bold text-gray-900">{{ $stats['incompletas'] ?? 0 }}</p>
                <p class="text-sm text-gray-500">Programaciones Incompletas</p>
            </div>
        </button>
        <button type="button" wire:click="setCardFilter('faltantes')"
            class="text-left bg-white rounded-xl border-t-4 border-t-red-500 border border-red-100 shadow p-5 flex items-center gap-4 transition hover:shadow-md
                {{ $cardFilter === 'faltantes' ? 'ring-2 ring-red-400' : '' }}">
            <div class="bg-red-100 text-red-600 rounded-full p-3"><i class="fas fa-user-times fa-lg"></i></div>
            <div>
                <p class="text-3xl font-bold text-gray-900">{{ $stats['faltantes'] ?? 0 }}</p>
                <p class="text-sm text-gray-500">Personal Faltante</p>
            </div>
        </button>
    </div>

    {{-- FILTROS --}}
    <div class="bg-white rounded-xl border border-green-100 shadow p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:flex-wrap">
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
            <div class="flex flex-col gap-1">
                <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">
                    <i class="fas fa-map-marker-alt mr-1"></i> Zona
                </label>
                <select wire:model.live="zoneFilter"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">Todas las zonas</option>
                    @foreach ($this->zonesList as $zoneOption)
                        <option value="{{ $zoneOption->id }}">{{ $zoneOption->name }}</option>
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
    @if (count($this->filteredZones) === 0)
        <div class="bg-white rounded-xl border border-green-100 shadow p-10 text-center text-gray-400">
            <i class="fas fa-calendar-times fa-2x mb-3"></i>
            <p class="text-sm">No hay programaciones para los filtros seleccionados.</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($this->filteredZones as $zone)
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
                        <div class="flex flex-col gap-0.5">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-truck text-green-500 w-4"></i>
                                <span class="font-medium">Vehículo: {{ $zone['vehiculo_nombre'] }}</span>
                            </div>
                            <span class="text-xs text-gray-400 pl-6">Placa: {{ $zone['vehiculo'] }}</span>
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

                    {{-- CAMBIO: si la programación está completa se muestra el botón
                         "Ver información" (solo lectura). Si está incompleta se mantiene
                         el botón "Cambiar personal" para abrir el editor. --}}
                    @if ($zone['completa'])
                        <button wire:click="openViewInfo({{ $zone['id'] }})"
                            class="w-full text-center text-sm font-medium text-white bg-sky-600 hover:bg-sky-700 rounded-lg py-2 transition">
                            <i class="fas fa-eye mr-1"></i> Ver información
                        </button>
                    @else
                        <button wire:click="openEditor({{ $zone['id'] }})"
                            class="w-full text-center text-sm font-medium text-white bg-green-700 hover:bg-green-800 rounded-lg py-2 transition">
                            <i class="fas fa-user-edit mr-1"></i> Cambiar personal
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- MODAL: EDITOR DE CAMBIO DE PERSONAL (programaciones incompletas) --}}
    <flux:modal name="editor-personal" wire:close="closeEditor"
        class="w-[96vw]! md:w-[700px]! max-w-none! max-h-[92vh] overflow-y-auto">

        <div class="space-y-0">

            {{-- Cabecera --}}
            <div class="bg-[#075985] px-6 py-4 text-white rounded-t-lg">
                <h2 class="text-lg font-bold flex items-center gap-2">
                    <i class="fas fa-user-edit"></i> Cambiar Personal
                </h2>
            </div>

            {{-- Contexto de solo lectura de la programación --}}
            <div class="grid grid-cols-2 gap-3 px-6 py-4 text-sm text-gray-600 bg-gray-50 border-b border-gray-200">
                <div class="flex items-center gap-2">
                    <i class="fas fa-clock text-gray-400"></i>
                    <span class="font-medium">{{ $this->shiftLabel($shift_id) }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-truck text-gray-400"></i>
                    <span>{{ $this->vehicleLabel($vehicle_id) }}</span>
                </div>
            </div>

            @php
                $reasonOptions = ['Imprevistos', 'Falta de disponibilidad', 'Mantenimiento', 'Solicitud operativa', 'Reasignación de personal'];
            @endphp

            <div class="p-5">
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
                    <div class="bg-[#0ea5e9] px-4 py-3 text-sm font-bold text-white flex items-center gap-2">
                        <i class="fas fa-users"></i> Cambio de Personal
                    </div>

                    {{-- Una fila por cada rol actual: conductor y cada ayudante --}}
                    <div class="divide-y divide-gray-100">
                        @php
                            $driverEmp     = App\Models\Employee::find($driver_id);
                            $driverPresent = $driverEmp ? ($teamAttendance[$driverEmp->id] ?? false) : false;
                        @endphp
                        <div class="grid gap-4 p-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-bold text-sky-700">Personal Actual</label>
                                <div class="flex items-center gap-2 rounded-md px-3 py-2 text-sm
                                    {{ $driverPresent ? 'bg-gray-100 text-gray-700' : 'bg-red-50 text-red-700 border border-red-200' }}">
                                    <i class="fas fa-user {{ $driverPresent ? 'text-sky-500' : 'text-red-400' }}"></i>
                                    <span>{{ $this->employeeName($driverEmp) }} (conductor)</span>
                                    <span class="ml-auto inline-flex items-center gap-1 text-xs font-semibold
                                        {{ $driverPresent ? 'text-green-600' : 'rounded-full bg-red-600 text-white px-2 py-0.5' }}">
                                        <i class="fas {{ $driverPresent ? 'fa-check-circle' : 'fa-exclamation-circle' }}"></i>
                                        {{ $driverPresent ? 'Presente' : 'Falta' }}
                                    </span>
                                </div>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-bold text-sky-700">Nuevo Personal</label>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-user-plus text-sky-500"></i>
                                    @php $candidatosConductor = $this->eligibleEmployees('Conductor'); @endphp
                                    {{-- CAMBIO: se bloquea el select si el conductor actual está presente,
                                         ya que no necesita reemplazo. --}}
                                    <select wire:model="change_new_ids.driver_id"
                                        @disabled($driverPresent)
                                        class="flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed">
                                        <option value="">Seleccione un nuevo personal</option>
                                        @foreach ($candidatosConductor as $emp)
                                            <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @if ($driverPresent)
                                    <p class="mt-1 text-xs text-gray-400">El trabajador está presente, no requiere reemplazo.</p>
                                @elseif ($candidatosConductor->isEmpty())
                                    <p class="mt-1 text-xs text-gray-400">Sin personal con asistencia confirmada disponible.</p>
                                @endif
                            </div>
                        </div>

                        @php $candidatosAyudante = $this->eligibleEmployees('Ayudante'); @endphp
                        {{-- CAMBIO: antes era "$h < $this->maxHelpers" (capacidad del vehículo).
                             Ahora itera solo sobre los ayudantes realmente asignados en $helper_ids. --}}
                        @for ($h = 0; $h < count($helper_ids); $h++)
                            @php
                                $hId  = $helper_ids[$h] ?? null;
                                $hEmp = $hId ? App\Models\Employee::find($hId) : null;
                                $hPresent = $hEmp ? ($teamAttendance[$hEmp->id] ?? false) : false;
                            @endphp
                            <div class="grid gap-4 p-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-sm font-bold text-sky-700">Personal Actual</label>
                                    <div class="flex items-center gap-2 rounded-md px-3 py-2 text-sm
                                        {{ $hPresent ? 'bg-gray-100 text-gray-700' : 'bg-red-50 text-red-700 border border-red-200' }}">
                                        <i class="fas fa-user {{ $hPresent ? 'text-sky-500' : 'text-red-400' }}"></i>
                                        <span>{{ $hEmp ? $this->employeeName($hEmp) : 'Vacío' }} (ayudante)</span>
                                        <span class="ml-auto inline-flex items-center gap-1 text-xs font-semibold
                                            {{ $hPresent ? 'text-green-600' : 'rounded-full bg-red-600 text-white px-2 py-0.5' }}">
                                            <i class="fas {{ $hPresent ? 'fa-check-circle' : 'fa-exclamation-circle' }}"></i>
                                            {{ $hPresent ? 'Presente' : 'Falta' }}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-bold text-sky-700">Nuevo Personal</label>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-user-plus text-sky-500"></i>
                                        {{-- CAMBIO: se bloquea el select si el ayudante actual está presente. --}}
                                        <select wire:model="change_new_ids.helper_ids.{{ $h }}"
                                            @disabled($hPresent)
                                            class="flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed">
                                            <option value="">Seleccione un nuevo personal</option>
                                            @foreach ($candidatosAyudante as $emp)
                                                <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @if ($hPresent)
                                        <p class="mt-1 text-xs text-gray-400">El trabajador está presente, no requiere reemplazo.</p>
                                    @endif
                                </div>
                            </div>
                        @endfor
                        {{-- CAMBIO: antes comparaba contra $this->maxHelpers (capacidad del vehículo).
                             Ahora se basa en la cantidad real de ayudantes asignados ($helper_ids). --}}
                        @if (count($helper_ids) === 0)
                            {{-- Esta programación no tiene ayudantes asignados. --}}
                        @elseif ($candidatosAyudante->isEmpty())
                            <p class="px-4 pb-2 -mt-2 text-xs text-gray-400">Sin ayudantes con asistencia confirmada disponibles.</p>
                        @endif
                    </div>

                    <div class="space-y-3 border-t border-gray-100 p-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600 uppercase">Motivo predefinido</label>
                            <select wire:model.live="change_person_reason_preset"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                                <option value="">Seleccione un motivo</option>
                                @foreach ($reasonOptions as $r)
                                    <option value="{{ $r }}">{{ $r }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-600 uppercase">Motivo del cambio</label>
                            <textarea wire:model="change_person_reason" rows="2" placeholder="Ingrese el motivo..."
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500"></textarea>
                            @error('change_person_reason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <button wire:click="addPersonChange"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-md bg-sky-500 hover:bg-sky-600 text-white text-sm font-semibold px-4 py-2 transition">
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

            <div class="mx-5 mb-5 rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="bg-[#075985] px-4 py-3 text-sm font-bold text-white flex items-center gap-2">
                    <i class="fas fa-list-alt"></i> Cambios Registrados
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-left text-xs font-bold uppercase text-gray-600">
                                <th class="border px-4 py-3">Cargo</th>
                                <th class="border px-4 py-3">Personal anterior</th>
                                <th class="border px-4 py-3">Personal nuevo</th>
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
                                        No hay cambios registrados. Agregue un cambio usando el panel superior.
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

    {{-- MODAL: VER INFORMACIÓN (solo lectura, programaciones completas) --}}
    <flux:modal name="ver-info" wire:close="closeViewInfo"
        class="w-[92vw]! md:w-[520px]! max-w-none!">
        <div class="space-y-0">
            <div class="bg-sky-600 px-6 py-4 text-white rounded-t-lg">
                <h2 class="text-lg font-bold flex items-center gap-2">
                    <i class="fas fa-eye"></i> Información de la Programación
                </h2>
            </div>

            <div class="p-5 space-y-4">
                <div class="grid grid-cols-2 gap-3 text-sm text-gray-700">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-map-marker-alt text-green-600"></i>
                        <span class="font-medium">{{ $viewingZone['zona'] ?? '—' }}</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-medium flex items-center gap-2">
                            <i class="fas fa-truck text-green-600"></i>
                            Vehículo: {{ $viewingZone['vehiculo_nombre'] ?? '—' }}
                        </span>
                        <span class="text-xs text-gray-400 pl-6">Placa: {{ $viewingZone['vehiculo'] ?? '—' }}</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-medium">{{ $viewingZone['turno'] ?? '—' }}</span>
                        <span class="text-xs text-gray-400">{{ $viewingZone['turno_horas'] ?? '' }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 bg-green-50 text-green-700 rounded-full px-2 py-0.5 text-xs font-semibold">
                            <i class="fas fa-user-check"></i> {{ $viewingZone['presentes'] ?? 0 }} presentes
                        </span>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 text-xs font-bold uppercase text-gray-600">
                        Personal asignado
                    </div>
                    <ul class="divide-y divide-gray-100">
                        @forelse (($viewingZone['employees'] ?? []) as $emp)
                            <li class="flex items-center justify-between px-4 py-2 text-sm">
                                <span>{{ $emp['nombre'] }}</span>
                                <span class="inline-flex items-center gap-1 text-xs font-semibold
                                    {{ $emp['presente'] ? 'text-green-600' : 'text-red-600' }}">
                                    <i class="fas {{ $emp['presente'] ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
                                    {{ $emp['presente'] ? 'Presente' : 'Falta' }}
                                </span>
                            </li>
                        @empty
                            <li class="px-4 py-3 text-center text-sm text-gray-400">Sin personal asignado.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-200 px-5 py-4">
                <button wire:click="closeViewInfo"
                    class="inline-flex items-center gap-2 rounded-md bg-gray-600 hover:bg-gray-700 text-white text-sm font-semibold px-5 py-2 transition">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </flux:modal>

</div>