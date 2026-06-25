<?php

namespace App\Livewire\Pages\Scheduling\Changes;

use App\Models\ChangeReason;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Reasons extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $editingId = null;
    public ?int $deletingId = null;
    public string $name = '';
    public string $description = '';
    public bool $is_active = true;
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
                'max:120',
                Rule::unique('change_reasons', 'name')->ignore($this->editingId),
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'is_active' => [
                'boolean',
            ],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'El nombre del motivo es obligatorio.',
            'name.unique' => 'Ya existe un motivo con este nombre.',
            'name.max' => 'El nombre no puede exceder los 120 caracteres.',
        ];
    }

    public function openCreate(): void
    {
        $this->resetForm();
        Flux::modal('reason-form')->show();
    }

    public function openEdit(int $id): void
    {
        $reason = ChangeReason::findOrFail($id);
        $this->editingId = $reason->id;
        $this->name = $reason->name;
        $this->description = $reason->description ?? '';
        $this->is_active = $reason->is_active;
        Flux::modal('reason-form')->show();
    }

    public function closeFormModal(): void
    {
        $this->resetForm();
        Flux::modal('reason-form')->close();
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingId) {
            $reason = ChangeReason::findOrFail($this->editingId);
            $reason->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'],
            ]);
            Flux::toast(variant: 'success', text: 'Motivo actualizado correctamente.');
        } else {
            ChangeReason::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'],
            ]);
            Flux::toast(variant: 'success', text: 'Motivo registrado correctamente.');
        }

        $this->closeFormModal();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        Flux::modal('confirm-delete-reason')->show();
    }

    public function delete(): void
    {
        if (! $this->deletingId) return;

        ChangeReason::findOrFail($this->deletingId)->delete();
        $this->deletingId = null;
        Flux::modal('confirm-delete-reason')->close();
        Flux::toast(variant: 'success', text: 'Motivo eliminado correctamente.');
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->reset([
            'name',
            'description',
            'is_active',
            'editingId',
        ]);
        $this->is_active = true;
    }

    #[Computed]
    public function reasons()
    {
        return ChangeReason::query()
            ->when($this->search !== '', function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('pages.scheduling.changes.reasons');
    }
}
