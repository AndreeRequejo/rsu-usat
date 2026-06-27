<?php

namespace App\Livewire\Pages\Maintenance;

use App\Models\MaintenanceDetail;
use App\Models\MaintenanceSchedule;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class DetailIndex extends Component
{
    use WithPagination, WithFileUploads;

    public int $scheduleId;
    public ?int $editingId = null;
    public string $search = '';

    public ?string $observation = null;
    public $image = null;
    public ?bool $completed = null;
    public int $perPage = 15;

    public function mount(int $scheduleId): void
    {
        $this->scheduleId = $scheduleId;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'observation' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'max:2048'],
            'completed' => ['nullable', 'boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'observation.max' => 'La observación no puede exceder los 1000 caracteres.',
            'image.image' => 'El archivo debe ser una imagen.',
            'image.max' => 'La imagen no debe superar los 2MB.',
        ];
    }

    public function openEdit(int $id): void
    {
        $detail = MaintenanceDetail::findOrFail($id);
        $this->editingId = $detail->id;
        $this->observation = $detail->observation;
        $this->completed = $detail->completed;
        $this->image = null;
        Flux::modal('detail-form')->show();
    }

    public function closeFormModal(): void
    {
        $this->resetForm();
        Flux::modal('detail-form')->close();
    }

    public function save(): void
    {
        $validated = $this->validate();
        $detail = MaintenanceDetail::findOrFail($this->editingId);

        if ($this->image) {
            $path = $this->image->store('maintenance', 'public');
            if ($detail->image_path) {
                \Storage::disk('public')->delete($detail->image_path);
            }
            $validated['image_path'] = $path;
        } else {
            unset($validated['image']);
        }

        $detail->update($validated);
        $this->closeFormModal();
        Flux::toast(variant: 'success', text: 'Detalle actualizado correctamente.');
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->reset([
            'observation',
            'image',
            'completed',
            'editingId',
        ]);
    }

    #[Computed]
    public function schedule()
    {
        return MaintenanceSchedule::with(['maintenance', 'vehicle', 'responsible'])
            ->findOrFail($this->scheduleId);
    }

    #[Computed]
    public function details()
    {
        return MaintenanceDetail::query()
            ->where('maintenance_schedule_id', $this->scheduleId)
            ->when($this->search !== '', function (Builder $query) {
                $query->where('observation', 'like', '%'.$this->search.'%');
            })
            ->orderBy('date')
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('pages.maintenance.details.index');
    }
}
