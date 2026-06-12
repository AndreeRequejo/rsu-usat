<?php

namespace App\Livewire\Pages\Scheduling\Holidays;

use App\Models\Holiday;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public string $date = '';

    public string $name = '';

    public string $description = '';

    public bool $is_active = true;

    public int $perPage = 10;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedDate(): void
    {
        // No hacer nada aquí, el día se calcula dinámicamente en la vista
    }

    protected function rules(): array
    {
        return [
            'date' => [
                'required',
                'date',
                Rule::unique('holidays', 'date')->ignore($this->editingId),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
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
            'date.required' => 'La fecha del feriado es obligatoria.',
            'date.date' => 'La fecha no es válida.',
            'date.unique' => 'Ya existe un feriado registrado para esta fecha.',
            'name.required' => 'La descripción es obligatoria.',
            'name.max' => 'La descripción no puede exceder los 255 caracteres.',
        ];
    }

    #[Computed]
    public function holidays()
    {
        return Holiday::query()
            ->when($this->search !== '', function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%')
                        ->orWhere('date', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter !== '', function (Builder $q) {
                $q->where('is_active', $this->statusFilter === 'active');
            })
            ->when($this->dateFrom, function (Builder $q) {
                $q->whereDate('date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function (Builder $q) {
                $q->whereDate('date', '<=', $this->dateTo);
            })
            ->orderBy('date', 'desc')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function stats(): array
    {
        $total = Holiday::count();
        $active = Holiday::where('is_active', true)->count();
        $upcoming = Holiday::where('is_active', true)
            ->whereDate('date', '>=', now()->toDateString())
            ->count();
        $currentYear = now()->year;

        return [
            'total' => $total,
            'active' => $active,
            'upcoming' => $upcoming,
            'current_year' => $currentYear,
        ];
    }

    public function openCreate(): void
    {
        $this->resetForm();
        Flux::modal('holiday-form')->show();
    }

    public function openEdit(int $id): void
    {
        $holiday = Holiday::findOrFail($id);
        $this->editingId = $holiday->id;
        $this->date = $holiday->date->format('Y-m-d');
        $this->name = $holiday->name;
        $this->description = $holiday->description ?? '';
        $this->is_active = $holiday->is_active;
        Flux::modal('holiday-form')->show();
    }

    public function closeFormModal(): void
    {
        $this->resetForm();
        Flux::modal('holiday-form')->close();
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingId) {
            $holiday = Holiday::findOrFail($this->editingId);
            $holiday->update([
                'date' => $validated['date'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'],
            ]);
            Flux::toast(variant: 'success', text: 'Feriado actualizado correctamente.');
        } else {
            Holiday::create([
                'date' => $validated['date'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'],
            ]);
            Flux::toast(variant: 'success', text: 'Feriado registrado correctamente.');
        }

        $this->closeFormModal();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        Flux::modal('confirm-delete-holiday')->show();
    }

    public function delete(): void
    {
        if (! $this->deletingId) {
            return;
        }

        $holiday = Holiday::findOrFail($this->deletingId);
        $holiday->delete();

        Flux::toast(variant: 'success', text: 'Feriado eliminado correctamente.');
        $this->deletingId = null;
        Flux::modal('confirm-delete-holiday')->close();
    }

    public function loadPeruHolidays(): void
    {
        $seeder = new \Database\Seeders\HolidaySeeder();
        $seeder->run();

        Flux::toast(variant: 'success', text: 'Feriados oficiales de Perú cargados correctamente.');
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->reset([
            'date',
            'name',
            'description',
            'is_active',
            'editingId',
        ]);
        $this->is_active = true;
    }

    public function render()
    {
        return view('pages.scheduling.holidays.index');
    }
}
