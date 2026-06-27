<?php

namespace App\Livewire\Pages\Maintenance;

use App\Models\Employee;
use App\Models\Maintenance;
use App\Models\MaintenanceDetail;
use App\Models\MaintenanceSchedule;
use App\Models\Vehicle;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ScheduleIndex extends Component
{
    use WithPagination, WithFileUploads;

    public int $maintenanceId;
    public string $search = '';
    public ?int $editingId = null;
    public ?int $deletingId = null;

    public ?int $vehicle_id = null;
    public ?int $responsible_id = null;
    public string $maintenance_type = '';
    public string $day_of_week = '';
    public ?string $start_time = null;
    public ?string $end_time = null;
    public int $perPage = 10;

    // Detail modal
    public bool $showDetailModal = false;
    public ?int $detailScheduleId = null;
    public ?int $detailEditingId = null;
    public ?string $detail_observation = null;
    public $detail_image = null;
    public ?bool $detail_completed = null;

    // Image view modal
    public bool $showImageModal = false;
    public ?string $viewImagePath = null;

    public function mount(int $maintenanceId): void
    {
        $this->maintenanceId = $maintenanceId;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        Flux::modal('schedule-form')->show();
    }

    public function openEdit(int $id): void
    {
        $schedule = MaintenanceSchedule::findOrFail($id);
        $this->editingId = $schedule->id;
        $this->vehicle_id = $schedule->vehicle_id;
        $this->responsible_id = $schedule->responsible_id;
        $this->maintenance_type = $schedule->maintenance_type;
        $this->day_of_week = $schedule->day_of_week;
        $this->start_time = $schedule->start_time;
        $this->end_time = $schedule->end_time;
        Flux::modal('schedule-form')->show();
    }

    public function closeFormModal(): void
    {
        $this->resetForm();
        Flux::modal('schedule-form')->close();
    }

    public function save(): void
    {
        $rules = [
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'responsible_id' => ['required', 'exists:employees,id'],
            'maintenance_type' => ['required', 'in:preventive,cleaning,repair'],
            'day_of_week' => ['required', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'start_time' => ['required'],
            'end_time' => ['required', 'after:start_time'],
        ];

        $messages = [
            'vehicle_id.required' => 'El vehículo es obligatorio.',
            'responsible_id.required' => 'El responsable es obligatorio.',
            'maintenance_type.required' => 'El tipo de mantenimiento es obligatorio.',
            'day_of_week.required' => 'El día de la semana es obligatorio.',
            'start_time.required' => 'La hora de inicio es obligatoria.',
            'end_time.required' => 'La hora de fin es obligatoria.',
            'end_time.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ];

        $validated = $this->validate($rules, $messages);

        $overlap = MaintenanceSchedule::query()
            ->where(function ($q) use ($validated) {
                $q->where('vehicle_id', $validated['vehicle_id'])
                  ->orWhere('responsible_id', $validated['responsible_id']);
            })
            ->where('day_of_week', $validated['day_of_week'])
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->where('start_time', '<', $validated['end_time'])
            ->where('end_time', '>', $validated['start_time'])
            ->exists();

        if ($overlap) {
            $this->addError('start_time', 'Este vehículo o responsable ya tiene un horario que se solapa en este día y hora.');
            return;
        }

        $isEditing = $this->editingId;

        DB::transaction(function () use ($validated, $isEditing) {
            if ($isEditing) {
                $schedule = MaintenanceSchedule::findOrFail($this->editingId);
                $oldDay = $schedule->day_of_week;
                $schedule->update($validated);

                if ($oldDay !== $validated['day_of_week']) {
                    $schedule->details()->delete();
                    $this->generateDetails($schedule);
                }
            } else {
                $validated['maintenance_id'] = $this->maintenanceId;
                $schedule = MaintenanceSchedule::create($validated);
                $this->generateDetails($schedule);
            }
        });

        $this->resetForm();
        Flux::modal('schedule-form')->close();
        Flux::toast(
            variant: 'success',
            text: $isEditing ? 'Horario actualizado correctamente.' : 'Horario registrado correctamente.'
        );
    }

    private function generateDetails(MaintenanceSchedule $schedule): void
    {
        $maintenance = Maintenance::find($schedule->maintenance_id);
        $dayMap = [
            'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
            'thursday' => 4, 'friday' => 5, 'saturday' => 6, 'sunday' => 7,
        ];
        $targetDow = $dayMap[$schedule->day_of_week];

        $start = \Carbon\Carbon::parse($maintenance->start_date)->startOfDay();
        while ((int) $start->format('N') !== $targetDow) {
            $start->addDay();
        }
        $end = \Carbon\Carbon::parse($maintenance->end_date)->endOfDay();

        $dates = [];
        while ($start->lte($end)) {
            $dates[] = [
                'maintenance_schedule_id' => $schedule->id,
                'date' => $start->format('Y-m-d'),
                'completed' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $start->addWeek();
        }

        if (! empty($dates)) {
            \DB::table('maintenance_details')->insert($dates);
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        Flux::modal('confirm-delete-schedule')->show();
    }

    public function delete(): void
    {
        if (! $this->deletingId) return;

        $deletingId = $this->deletingId;

        DB::transaction(function () use ($deletingId) {
            $schedule = MaintenanceSchedule::findOrFail($deletingId);
            $schedule->details()->delete();
            $schedule->delete();
        });

        $this->deletingId = null;
        Flux::modal('confirm-delete-schedule')->close();
        Flux::toast(variant: 'success', text: 'Horario eliminado correctamente.');
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->reset([
            'vehicle_id',
            'responsible_id',
            'maintenance_type',
            'day_of_week',
            'start_time',
            'end_time',
            'editingId',
        ]);
    }

    // --- Detail Modal Methods ---

    public function openDetail(int $scheduleId): void
    {
        $this->detailScheduleId = $scheduleId;
        $this->showDetailModal = true;
        $this->dispatch('refresh-detail');
        Flux::modal('detail-view-modal')->show();
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->detailScheduleId = null;
        $this->detailEditingId = null;
        $this->detail_observation = null;
        $this->detail_image = null;
        $this->detail_completed = null;
        $this->resetValidation();
        Flux::modal('detail-view-modal')->close();
    }

    public function openDetailEdit(int $detailId): void
    {
        $detail = MaintenanceDetail::findOrFail($detailId);
        $this->detailEditingId = $detail->id;
        $this->detail_observation = $detail->observation;
        $this->detail_completed = $detail->completed;
        $this->detail_image = null;
        Flux::modal('detail-edit-form')->show();
    }

    public function closeDetailEditModal(): void
    {
        $this->detailEditingId = null;
        $this->detail_observation = null;
        $this->detail_image = null;
        $this->detail_completed = null;
        $this->resetValidation();
        Flux::modal('detail-edit-form')->close();
    }

    public function openImageModal(string $path): void
    {
        $this->viewImagePath = $path;
        Flux::modal('image-view-modal')->show();
    }

    public function closeImageModal(): void
    {
        $this->viewImagePath = null;
        Flux::modal('image-view-modal')->close();
    }

    public function saveDetail(): void
    {
        $rules = [
            'detail_observation' => ['nullable', 'string', 'max:1000'],
            'detail_image' => ['nullable', 'image', 'max:2048'],
            'detail_completed' => ['nullable', 'boolean'],
        ];

        $messages = [
            'detail_observation.max' => 'La observación no puede exceder los 1000 caracteres.',
            'detail_image.image' => 'El archivo debe ser una imagen.',
            'detail_image.max' => 'La imagen no debe superar los 2MB.',
        ];

        $validated = $this->validate($rules, $messages);

        $detail = MaintenanceDetail::findOrFail($this->detailEditingId);

        $updateData = [];

        if ($this->detail_image) {
            $path = $this->detail_image->store('maintenance', 'public');
            if ($detail->image_path) {
                \Storage::disk('public')->delete($detail->image_path);
            }
            $updateData['image_path'] = $path;
        }

        if ($this->detail_observation !== null) {
            $updateData['observation'] = $this->detail_observation;
        }

        $updateData['completed'] = $this->detail_completed;

        $detail->update($updateData);
        $this->closeDetailEditModal();
        Flux::toast(variant: 'success', text: 'Detalle actualizado correctamente.');
    }

    // --- Computed Properties ---

    #[Computed]
    public function maintenance()
    {
        return Maintenance::findOrFail($this->maintenanceId);
    }

    #[Computed]
    public function vehicles()
    {
        return Vehicle::where('status', true)->orderBy('name')->get();
    }

    #[Computed]
    public function employees()
    {
        return Employee::where('active', true)->orderBy('first_name')->get();
    }

    #[Computed]
    public function types()
    {
        return config('maintenance.types', []);
    }

    #[Computed]
    public function days()
    {
        return config('maintenance.days_of_week', []);
    }

    #[Computed]
    public function schedules()
    {
        return MaintenanceSchedule::query()
            ->where('maintenance_id', $this->maintenanceId)
            ->when($this->search !== '', function (Builder $query) {
                $query->whereHas('vehicle', function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%');
                });
            })
            ->with(['vehicle', 'responsible'])
            ->orderBy('start_time')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function detailSchedule()
    {
        if (! $this->detailScheduleId) return null;

        return MaintenanceSchedule::with(['maintenance', 'vehicle', 'responsible'])
            ->find($this->detailScheduleId);
    }

    #[Computed]
    public function detailRecords()
    {
        if (! $this->detailScheduleId) return collect();

        return MaintenanceDetail::query()
            ->where('maintenance_schedule_id', $this->detailScheduleId)
            ->orderBy('date')
            ->get();
    }

    public function render()
    {
        return view('pages.maintenance.schedules.index');
    }
}
