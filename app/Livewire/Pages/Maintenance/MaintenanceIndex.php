<?php

namespace App\Livewire\Pages\Maintenance;

use App\Models\Maintenance;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class MaintenanceIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $editingId = null;
    public ?int $deletingId = null;
    public string $name = '';
    public ?string $start_date = null;
    public ?string $end_date = null;
    public int $perPage = 10;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
            ],
            'start_date' => [
                'required',
                'date',
            ],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede exceder los 150 caracteres.',
            'start_date.required' => 'La fecha de inicio es obligatoria.',
            'start_date.date' => 'La fecha de inicio no es válida.',
            'end_date.required' => 'La fecha de fin es obligatoria.',
            'end_date.date' => 'La fecha de fin no es válida.',
            'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ];
    }

    public function openCreate(): void
    {
        $this->resetForm();
        Flux::modal('maintenance-form')->show();
    }

    public function openEdit(int $id): void
    {
        $maintenance = Maintenance::findOrFail($id);
        $this->editingId = $maintenance->id;
        $this->name = $maintenance->name;
        $this->start_date = $maintenance->start_date->format('Y-m-d');
        $this->end_date = $maintenance->end_date->format('Y-m-d');
        Flux::modal('maintenance-form')->show();
    }

    public function closeFormModal(): void
    {
        $this->resetForm();
        Flux::modal('maintenance-form')->close();
    }

    public function save(): void
    {
        $validated = $this->validate();

        $overlap = Maintenance::query()
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->where('start_date', '<=', $validated['end_date'])
            ->where('end_date', '>=', $validated['start_date'])
            ->exists();

        if ($overlap) {
            $this->addError('start_date', 'El rango de fechas se solapa con otro mantenimiento existente.');
            return;
        }

        if ($this->editingId) {
            Maintenance::findOrFail($this->editingId)->update($validated);
            Flux::toast(variant: 'success', text: 'Mantenimiento actualizado correctamente.');
        } else {
            Maintenance::create($validated);
            Flux::toast(variant: 'success', text: 'Mantenimiento registrado correctamente.');
        }

        $this->closeFormModal();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        Flux::modal('confirm-delete-maintenance')->show();
    }

    public function delete(): void
    {
        if (! $this->deletingId) return;

        $maintenance = Maintenance::findOrFail($this->deletingId);
        $hasSchedules = $maintenance->schedules()->exists();

        if ($hasSchedules) {
            Flux::modal('confirm-delete-maintenance')->close();
            Flux::toast(variant: 'warning', text: 'No se puede eliminar: existen horarios asociados.');
            return;
        }

        $maintenance->delete();
        $this->deletingId = null;
        Flux::modal('confirm-delete-maintenance')->close();
        Flux::toast(variant: 'success', text: 'Mantenimiento eliminado correctamente.');
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->reset([
            'name',
            'start_date',
            'end_date',
            'editingId',
        ]);
    }

    #[Computed]
    public function maintenances()
    {
        return Maintenance::query()
            ->when($this->search !== '', function (Builder $query) {
                $query->where('name', 'like', '%'.$this->search.'%');
            })
            ->withCount('schedules')
            ->orderBy('start_date', 'desc')
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('pages.maintenance.maintenances.index');
    }
}
