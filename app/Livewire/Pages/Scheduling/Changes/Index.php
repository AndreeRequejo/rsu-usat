<?php

namespace App\Livewire\Pages\Scheduling\Changes;

use App\Models\ChangeReason;
use App\Models\Employee;
use App\Models\Scheduling;
use App\Models\SchedulingChange;
use App\Models\SchedulingChangeItem;
use App\Models\SchedulingHistory;
use App\Models\Shift;
use App\Models\Vehicle;
use App\Models\Zone;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use stdClass;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $typeFilter = '';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public int $perPage = 10;

    public ?string $viewingId = null;

    public ?int $deletingId = null;

    // Formulario de cambio masivo
    public string $massive_start_date = '';

    public string $massive_end_date = '';

    public ?int $massive_zone_id = null;

    public string $massive_change_type = '';

    public ?int $massive_old_resource_id = null;

    public ?int $massive_new_resource_id = null;

    public string $massive_reason_preset = '';

    public string $massive_reason_detail = '';

    public string $massive_reason_full = '';

    public bool $showConfirmModal = false;

    public array $previewResults = [];

    public int $previewAffectedCount = 0;

    protected function rules(): array
    {
        return [
            'massive_start_date' => ['required', 'date'],
            'massive_end_date' => ['required', 'date', 'after_or_equal:massive_start_date'],
            'massive_zone_id' => ['nullable', 'exists:zones,id'],
            'massive_change_type' => ['required', 'in:turn,vehicle,driver,helper'],
            'massive_old_resource_id' => ['required', 'integer', 'min:1'],
            'massive_new_resource_id' => ['required', 'integer', 'min:1', 'different:massive_old_resource_id'],
            'massive_reason_preset' => ['required', 'string', 'max:100'],
            'massive_reason_detail' => ['nullable', 'string', 'max:255'],
            'massive_reason_full' => ['required', 'string', 'min:1'],
        ];
    }

    protected function messages(): array
    {
        return [
            'massive_start_date.required' => 'La fecha de inicio es obligatoria.',
            'massive_start_date.date' => 'La fecha de inicio no es valida.',
            'massive_end_date.required' => 'La fecha de fin es obligatoria.',
            'massive_end_date.date' => 'La fecha de fin no es valida.',
            'massive_end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'massive_change_type.required' => 'Seleccione el tipo de cambio.',
            'massive_change_type.in' => 'El tipo de cambio seleccionado no es valido.',
            'massive_old_resource_id.required' => 'Seleccione el recurso a reemplazar.',
            'massive_old_resource_id.min' => 'Seleccione un recurso valido.',
            'massive_new_resource_id.required' => 'Seleccione el nuevo recurso.',
            'massive_new_resource_id.min' => 'Seleccione un recurso valido.',
            'massive_new_resource_id.different' => 'El nuevo recurso debe ser diferente al anterior.',
            'massive_reason_preset.required' => 'Seleccione un motivo predefinido.',
            'massive_reason_full.required' => 'La descripcion completa es obligatoria.',
        ];
    }

    public function updatedMassiveChangeType(): void
    {
        $this->massive_old_resource_id = null;
        $this->massive_new_resource_id = null;
    }

    public function updatedMassiveReasonPreset(): void
    {
        $this->buildReasonFull();
    }

    public function updatedMassiveReasonDetail(): void
    {
        $this->buildReasonFull();
    }

    private function buildReasonFull(): void
    {
        $parts = [];
        if ($this->massive_reason_preset) {
            $parts[] = $this->massive_reason_preset;
        }
        if ($this->massive_reason_detail) {
            $parts[] = $this->massive_reason_detail;
        }
        $this->massive_reason_full = implode(' - ', $parts);
    }

    public function openCreate(): void
    {
        $this->resetMassiveForm();
        $this->showConfirmModal = false;
        Flux::modal('change-form')->show();
    }

    public function closeFormModal(): void
    {
        $this->resetMassiveForm();
        $this->showConfirmModal = false;
        Flux::modal('change-form')->close();
    }

    public function previewChanges(): void
    {
        $this->buildReasonFull();
        $this->validate();
        $this->previewResults = $this->buildPreview();
        $this->previewAffectedCount = count($this->previewResults);

        if ($this->previewAffectedCount === 0) {
            Flux::toast(variant: 'warning', text: 'No se encontraron programaciones que coincidan con los criterios seleccionados. Verifique que existan programaciones en el rango de fechas con el recurso seleccionado.');

            return;
        }

        $this->showConfirmModal = true;
    }

    public function cancelConfirm(): void
    {
        $this->showConfirmModal = false;
    }

    public function applyMassiveChange(): void
    {
        if ($this->previewAffectedCount === 0) {
            Flux::toast(variant: 'warning', text: 'No hay programaciones afectadas para aplicar el cambio.');

            return;
        }

        $oldId = (int) $this->massive_old_resource_id;
        $newId = (int) $this->massive_new_resource_id;

        DB::transaction(function () use ($oldId, $newId) {
            $change = SchedulingChange::create([
                'user_id' => auth()->id(),
                'change_type' => $this->massive_change_type,
                'start_date' => $this->massive_start_date,
                'end_date' => $this->massive_end_date,
                'zone_id' => $this->massive_zone_id,
                'old_shift_id' => $this->massive_change_type === 'turn' ? $oldId : null,
                'new_shift_id' => $this->massive_change_type === 'turn' ? $newId : null,
                'old_vehicle_id' => $this->massive_change_type === 'vehicle' ? $oldId : null,
                'new_vehicle_id' => $this->massive_change_type === 'vehicle' ? $newId : null,
                'old_person_id' => in_array($this->massive_change_type, ['driver', 'helper']) ? $oldId : null,
                'new_person_id' => in_array($this->massive_change_type, ['driver', 'helper']) ? $newId : null,
                'person_role' => in_array($this->massive_change_type, ['driver', 'helper']) ? $this->massive_change_type : null,
                'reason_preset' => $this->massive_reason_preset,
                'reason_detail' => $this->massive_reason_detail,
                'reason_full' => $this->massive_reason_full,
                'affected_count' => $this->previewAffectedCount,
            ]);

            foreach ($this->previewResults as $preview) {
                $scheduling = Scheduling::find($preview['scheduling_id']);
                if (! $scheduling) {
                    continue;
                }

                $before = [
                    'shift_id' => $scheduling->shift_id,
                    'vehicle_id' => $scheduling->vehicle_id,
                    'employees' => $scheduling->groupDetails->pluck('employee_id')->values()->all(),
                ];

                if ($this->massive_change_type === 'turn') {
                    $scheduling->update(['shift_id' => $newId, 'status' => 'Reprogramado']);
                } elseif ($this->massive_change_type === 'vehicle') {
                    $scheduling->update(['vehicle_id' => $newId, 'status' => 'Reprogramado']);
                } elseif ($this->massive_change_type === 'driver') {
                    $this->swapGroupDetail($scheduling, $oldId, $newId);
                    $scheduling->update(['status' => 'Reprogramado']);
                } elseif ($this->massive_change_type === 'helper') {
                    $this->swapGroupDetail($scheduling, $oldId, $newId);
                    $scheduling->update(['status' => 'Reprogramado']);
                }

                $after = [
                    'shift_id' => $scheduling->fresh()->shift_id,
                    'vehicle_id' => $scheduling->fresh()->vehicle_id,
                    'employees' => $scheduling->fresh()->groupDetails->pluck('employee_id')->values()->all(),
                ];

                SchedulingChangeItem::create([
                    'scheduling_change_id' => $change->id,
                    'scheduling_id' => $scheduling->id,
                    'before' => $before,
                    'after' => $after,
                ]);
            }
        });

        Flux::toast(variant: 'success', text: 'Cambio masivo aplicado correctamente. Se afectaron '.$this->previewAffectedCount.' programacion(es).');
        $this->closeFormModal();
    }

    private function swapGroupDetail(Scheduling $scheduling, int $oldId, int $newId): void
    {
        $detail = $scheduling->groupDetails()->where('employee_id', $oldId)->first();
        if ($detail) {
            $detail->update(['employee_id' => $newId]);
        }
    }

    private function buildPreview(): array
    {
        $query = Scheduling::query()
            ->with(['shift', 'vehicle', 'zone', 'groupDetails.employee'])
            ->whereDate('date', '>=', $this->massive_start_date)
            ->whereDate('date', '<=', $this->massive_end_date);

        if ($this->massive_zone_id) {
            $query->where('zone_id', $this->massive_zone_id);
        }

        $oldResourceId = (int) $this->massive_old_resource_id;

        if ($this->massive_change_type === 'turn') {
            $query->where('shift_id', $oldResourceId);
        } elseif ($this->massive_change_type === 'vehicle') {
            $query->where('vehicle_id', $oldResourceId);
        } elseif (in_array($this->massive_change_type, ['driver', 'helper'])) {
            $query->whereHas('groupDetails', fn ($q) => $q->where('employee_id', $oldResourceId));
        }

        return $query->orderBy('date')->get()->map(fn ($s) => [
            'scheduling_id' => $s->id,
            'date' => $s->date->format('d/m/Y'),
            'zone' => $s->zone->name ?? '-',
            'shift' => $s->shift->name ?? '-',
            'vehicle' => ($s->vehicle->name ?? '').' ('.($s->vehicle->plate ?? '').')',
            'employees' => $s->groupDetails->map(fn ($gd) => ($gd->employee->first_name ?? '').' '.($gd->employee->last_name ?? ''))->implode(', '),
        ])->toArray();
    }

    public function openView(string $compositeId): void
    {
        $this->viewingId = $compositeId;
        Flux::modal('change-viewer')->show();
    }

    public function closeViewer(): void
    {
        $this->viewingId = null;
        Flux::modal('change-viewer')->close();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        Flux::modal('confirm-delete-change')->show();
    }

    public function delete(): void
    {
        if (! $this->deletingId) {
            return;
        }

        $change = SchedulingChange::findOrFail($this->deletingId);
        $change->delete();
        $this->deletingId = null;
        Flux::modal('confirm-delete-change')->close();
        Flux::toast(variant: 'success', text: 'Cambio eliminado correctamente.');
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'typeFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    private function resetMassiveForm(): void
    {
        $this->resetValidation();
        $this->massive_start_date = '';
        $this->massive_end_date = '';
        $this->massive_zone_id = null;
        $this->massive_change_type = '';
        $this->massive_old_resource_id = null;
        $this->massive_new_resource_id = null;
        $this->massive_reason_preset = '';
        $this->massive_reason_detail = '';
        $this->massive_reason_full = '';
        $this->showConfirmModal = false;
        $this->previewResults = [];
        $this->previewAffectedCount = 0;
    }

    #[Computed]
    public function changes()
    {
        $massive = $this->fetchMassiveChanges();
        $individual = $this->fetchIndividualChanges();

        $combined = $massive->merge($individual)
            ->sortByDesc('created_at')
            ->values();

        $page = $this->getPage();
        $perPage = $this->perPage;
        $total = $combined->count();
        $items = $combined->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page']
        );
    }

    private function fetchMassiveChanges()
    {
        return SchedulingChange::query()
            ->with(['user', 'zone', 'oldShift', 'newShift', 'oldVehicle', 'newVehicle', 'oldPerson', 'newPerson'])
            ->when($this->search !== '', function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->where('reason_full', 'like', '%'.$this->search.'%')
                        ->orWhere('reason_preset', 'like', '%'.$this->search.'%')
                        ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->when($this->typeFilter !== '', fn (Builder $q) => $q->where('change_type', $this->typeFilter))
            ->when($this->dateFrom, fn (Builder $q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($change) => $this->normalizeMassiveChange($change));
    }

    private function fetchIndividualChanges()
    {
        return SchedulingHistory::query()
            ->with(['user', 'scheduling.zone', 'scheduling.shift', 'scheduling.vehicle', 'scheduling.groupDetails.employee'])
            ->where(function (Builder $q) {
                $q->where('action', 'like', 'Reprogramacion%')
                    ->orWhere('action', 'like', 'Creacion%')
                    ->orWhere('action', 'like', 'Finalizacion%')
                    ->orWhere('action', 'like', 'Eliminacion%');
            })
            ->when($this->search !== '', function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->where('description', 'like', '%'.$this->search.'%')
                        ->orWhere('action', 'like', '%'.$this->search.'%')
                        ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->when($this->dateFrom, fn (Builder $q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($history) => $this->normalizeIndividualChange($history))
            ->filter()
            ->filter(fn ($item) => $this->typeFilter === '' || $item->change_type === $this->typeFilter);
    }

    private function normalizeMassiveChange(SchedulingChange $change): stdClass
    {
        $item = new stdClass;
        $item->id = $change->id;
        $item->composite_id = 'massive-'.$change->id;
        $item->type = 'massive';
        $item->type_label = __('Masivo');
        $item->change_type = $change->change_type;
        $item->change_label = $change->type_label;
        $item->badge_color = $change->type_badge_color;
        $item->created_at = $change->created_at;
        $item->start_date = $change->start_date;
        $item->end_date = $change->end_date;
        $item->zone = $change->zone;
        $item->zone_name = $change->zone?->name ?? __('Todas las zonas');
        $item->old_value = $this->massiveValue($change, 'old');
        $item->new_value = $this->massiveValue($change, 'new');
        $item->reason = $change->reason_full;
        $item->user = $change->user;
        $item->affected_count = $change->affected_count;
        $item->source = $change;

        return $item;
    }

    private function normalizeIndividualChange(SchedulingHistory $history): ?stdClass
    {
        $changeType = $this->individualChangeType($history->action);
        if ($changeType === null) {
            return null;
        }

        $item = new stdClass;
        $item->id = $history->id;
        $item->composite_id = 'individual-'.$history->id;
        $item->type = 'individual';
        $item->type_label = __('Individual');
        $item->change_type = $changeType;
        $item->change_label = $this->individualChangeLabel($changeType);
        $item->badge_color = $this->individualBadgeColor($changeType);
        $item->created_at = $history->created_at;
        $item->start_date = $history->scheduling?->date;
        $item->end_date = $history->scheduling?->date;
        $item->zone = $history->scheduling?->zone;
        $item->zone_name = $history->scheduling?->zone?->name ?? '-';
        $item->old_value = $this->individualValue($history, 'old');
        $item->new_value = $this->individualValue($history, 'new');
        $item->reason = $history->description;
        $item->user = $history->user;
        $item->affected_count = 1;
        $item->source = $history;

        return $item;
    }

    private function massiveValue(SchedulingChange $change, string $direction): string
    {
        if ($change->change_type === 'turn') {
            $model = $direction === 'old' ? $change->oldShift : $change->newShift;

            return $model ? $model->name.' ('.$model->hour_in.' - '.$model->hour_out.')' : '-';
        }

        if ($change->change_type === 'vehicle') {
            $model = $direction === 'old' ? $change->oldVehicle : $change->newVehicle;

            return $model ? $model->name.' ('.$model->plate.')' : '-';
        }

        if (in_array($change->change_type, ['driver', 'helper'])) {
            $model = $direction === 'old' ? $change->oldPerson : $change->newPerson;

            return $model ? $model->first_name.' '.$model->last_name : '-';
        }

        return '-';
    }

    private function individualChangeType(string $action): ?string
    {
        if (str_contains($action, 'Turno')) {
            return 'turn';
        }

        if (str_contains($action, 'Vehiculo')) {
            return 'vehicle';
        }

        if (str_contains($action, 'Conductor') || str_contains($action, 'Ayudante') || str_contains($action, 'Personal')) {
            return in_array($action, ['Conductor', 'Reprogramacion - Conductor']) ? 'driver' : 'helper';
        }

        return null;
    }

    private function individualChangeLabel(string $changeType): string
    {
        return match ($changeType) {
            'turn' => 'Turno',
            'vehicle' => 'Vehiculo',
            'driver' => 'Conductor',
            'helper' => 'Ocupante',
            default => ucfirst($changeType),
        };
    }

    private function individualBadgeColor(string $changeType): string
    {
        return match ($changeType) {
            'turn' => '#F4C542',
            'vehicle' => '#1976D2',
            'driver' => '#4CAF50',
            'helper' => '#00BCD4',
            default => '#999999',
        };
    }

    private function individualValue(SchedulingHistory $history, string $direction): string
    {
        $value = $history->changes[$direction] ?? null;
        if (blank($value)) {
            return '-';
        }

        if (is_array($value)) {
            return collect($value)
                ->map(fn ($item, $key) => is_string($key) ? $key.': '.$item : $item)
                ->implode(', ');
        }

        return (string) $value;
    }

    #[Computed]
    public function viewingChange()
    {
        if (! $this->viewingId) {
            return null;
        }

        if (str_starts_with($this->viewingId, 'massive-')) {
            $id = (int) str_replace('massive-', '', $this->viewingId);

            return SchedulingChange::with(['user', 'zone', 'oldShift', 'newShift', 'oldVehicle', 'newVehicle', 'oldPerson', 'newPerson', 'items.scheduling.zone', 'items.scheduling.shift', 'items.scheduling.vehicle', 'items.scheduling.groupDetails.employee'])
                ->find($id);
        }

        if (str_starts_with($this->viewingId, 'individual-')) {
            $id = (int) str_replace('individual-', '', $this->viewingId);

            return SchedulingHistory::with(['user', 'scheduling.zone', 'scheduling.shift', 'scheduling.vehicle', 'scheduling.groupDetails.employee'])
                ->find($id);
        }

        return null;
    }

    #[Computed]
    public function viewingData(): ?stdClass
    {
        $source = $this->viewingChange;
        if (! $source) {
            return null;
        }

        if ($source instanceof SchedulingChange) {
            return $this->normalizeMassiveChange($source);
        }

        if ($source instanceof SchedulingHistory) {
            return $this->normalizeIndividualChange($source);
        }

        return null;
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
    public function zones()
    {
        return Zone::where('status', 'active')->orderBy('name')->get();
    }

    #[Computed]
    public function employees()
    {
        return Employee::where('active', true)->orderBy('first_name')->get();
    }

    #[Computed]
    public function reasonPresets(): array
    {
        return ChangeReason::where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'name')
            ->toArray();
    }

    public function render()
    {
        return view('pages.scheduling.changes.index');
    }
}
