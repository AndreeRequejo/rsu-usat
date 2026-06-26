<?php

namespace App\Livewire\Pages\Scheduling\Changes;

use App\Models\Employee;
use App\Models\Scheduling;
use App\Models\SchedulingChange;
use App\Models\SchedulingChangeItem;
use App\Models\Shift;
use App\Models\Vehicle;
use App\Models\Zone;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $typeFilter = '';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public int $perPage = 10;

    public ?int $viewingId = null;
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
    public array $previewChanges = [];
    public int $previewAffectedCount = 0;

    protected function rules(): array
    {
        return [
            'massive_start_date' => ['required', 'date'],
            'massive_end_date' => ['required', 'date', 'after_or_equal:massive_start_date'],
            'massive_zone_id' => ['nullable', 'exists:zones,id'],
            'massive_change_type' => ['required', 'in:turn,vehicle,driver,helper'],
            'massive_old_resource_id' => ['required', 'integer'],
            'massive_new_resource_id' => ['required', 'integer', 'different:massive_old_resource_id'],
            'massive_reason_preset' => ['required', 'string', 'max:100'],
            'massive_reason_detail' => ['nullable', 'string', 'max:255'],
            'massive_reason_full' => ['required', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'massive_start_date.required' => 'La fecha de inicio es obligatoria.',
            'massive_end_date.required' => 'La fecha de fin es obligatoria.',
            'massive_end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'massive_change_type.required' => 'Seleccione el tipo de cambio.',
            'massive_old_resource_id.required' => 'Seleccione el recurso a reemplazar.',
            'massive_new_resource_id.required' => 'Seleccione el nuevo recurso.',
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
        try {
            $this->validate();
            $this->previewChanges = $this->buildPreview();
            $this->previewAffectedCount = count($this->previewChanges);
            $this->showConfirmModal = true;
        } catch (\Illuminate\Validation\ValidationException $e) {
            Flux::toast(variant: 'warning', text: 'Por favor complete todos los campos requeridos.');
            throw $e;
        } catch (\Throwable $e) {
            Flux::toast(variant: 'danger', text: 'Error al procesar: ' . $e->getMessage());
        }
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

        DB::transaction(function () {
            $change = SchedulingChange::create([
                'user_id' => auth()->id(),
                'change_type' => $this->massive_change_type,
                'start_date' => $this->massive_start_date,
                'end_date' => $this->massive_end_date,
                'zone_id' => $this->massive_zone_id,
                'old_shift_id' => $this->massive_change_type === 'turn' ? $this->massive_old_resource_id : null,
                'new_shift_id' => $this->massive_change_type === 'turn' ? $this->massive_new_resource_id : null,
                'old_vehicle_id' => $this->massive_change_type === 'vehicle' ? $this->massive_old_resource_id : null,
                'new_vehicle_id' => $this->massive_change_type === 'vehicle' ? $this->massive_new_resource_id : null,
                'old_person_id' => in_array($this->massive_change_type, ['driver', 'helper']) ? $this->massive_old_resource_id : null,
                'new_person_id' => in_array($this->massive_change_type, ['driver', 'helper']) ? $this->massive_new_resource_id : null,
                'person_role' => in_array($this->massive_change_type, ['driver', 'helper']) ? $this->massive_change_type : null,
                'reason_preset' => $this->massive_reason_preset,
                'reason_detail' => $this->massive_reason_detail,
                'reason_full' => $this->massive_reason_full,
                'affected_count' => $this->previewAffectedCount,
            ]);

            foreach ($this->previewChanges as $preview) {
                $scheduling = Scheduling::find($preview['scheduling_id']);
                if (! $scheduling) continue;

                $before = [
                    'shift_id' => $scheduling->shift_id,
                    'vehicle_id' => $scheduling->vehicle_id,
                    'employees' => $scheduling->groupDetails->pluck('employee_id')->values()->all(),
                ];

                if ($this->massive_change_type === 'turn') {
                    $scheduling->update(['shift_id' => $this->massive_new_resource_id, 'status' => 'Reprogramado']);
                } elseif ($this->massive_change_type === 'vehicle') {
                    $scheduling->update(['vehicle_id' => $this->massive_new_resource_id, 'status' => 'Reprogramado']);
                } elseif ($this->massive_change_type === 'driver') {
                    $this->swapGroupDetail($scheduling, $this->massive_old_resource_id, $this->massive_new_resource_id);
                    $scheduling->update(['status' => 'Reprogramado']);
                } elseif ($this->massive_change_type === 'helper') {
                    $this->swapGroupDetail($scheduling, $this->massive_old_resource_id, $this->massive_new_resource_id);
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

    private function swapGroupDetail(Scheduling $scheduling, ?int $oldId, ?int $newId): void
    {
        if ($oldId === null || $newId === null) {
            return;
        }
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

        if ($this->massive_change_type === 'turn') {
            $query->where('shift_id', $this->massive_old_resource_id);
        } elseif ($this->massive_change_type === 'vehicle') {
            $query->where('vehicle_id', $this->massive_old_resource_id);
        } elseif (in_array($this->massive_change_type, ['driver', 'helper'])) {
            $query->whereHas('groupDetails', fn ($q) => $q->where('employee_id', $this->massive_old_resource_id));
        }

        return $query->orderBy('date')->get()->map(fn ($s) => [
            'scheduling_id' => $s->id,
            'date' => $s->date->format('d/m/Y'),
            'zone' => $s->zone->name ?? '-',
            'shift' => $s->shift->name ?? '-',
            'vehicle' => ($s->vehicle->name ?? '') . ' (' . ($s->vehicle->plate ?? '') . ')',
            'employees' => $s->groupDetails->map(fn ($gd) => ($gd->employee->first_name ?? '') . ' ' . ($gd->employee->last_name ?? ''))->implode(', '),
        ])->toArray();
    }

    public function openView(int $id): void
    {
        $this->viewingId = $id;
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
        if (! $this->deletingId) return;

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
        $this->reset([
            'massive_start_date',
            'massive_end_date',
            'massive_zone_id',
            'massive_change_type',
            'massive_old_resource_id',
            'massive_new_resource_id',
            'massive_reason_preset',
            'massive_reason_detail',
            'massive_reason_full',
            'showConfirmModal',
            'previewChanges',
            'previewAffectedCount',
        ]);
    }

    #[Computed]
    public function changes()
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
            ->paginate($this->perPage);
    }

    #[Computed]
    public function viewingChange()
    {
        if (! $this->viewingId) return null;
        return SchedulingChange::with(['user', 'zone', 'oldShift', 'newShift', 'oldVehicle', 'newVehicle', 'oldPerson', 'newPerson', 'items.scheduling.zone', 'items.scheduling.shift', 'items.scheduling.vehicle', 'items.scheduling.groupDetails.employee'])
            ->find($this->viewingId);
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
        return \App\Models\ChangeReason::where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'name')
            ->toArray();
    }

    public function render()
    {
        return view('pages.scheduling.changes.index');
    }
}
