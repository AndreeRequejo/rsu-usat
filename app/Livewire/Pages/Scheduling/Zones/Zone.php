<?php

namespace App\Livewire\Pages\Scheduling\Zones;

use App\Models\Department;
use App\Models\District;
use App\Models\Province;
use App\Models\Zone as ZoneModel;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Zone extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $districtFilter = '';

    public ?int $editingId = null;

    public ?string $createSessionId = null;

    public ?int $deletingId = null;

    public ?int $viewingId = null;

    public string $activeTab = 'data';

    public string $name = '';

    public ?int $department_id = null;

    public ?int $province_id = null;

    public ?int $district_id = null;

    public string $description = '';

    public ?string $average_waste = null;

    public string $status = 'active';

    public array $coords = [];

    public string $coords_json = '';

    public function mount(): void
    {
        $jlo = District::where('name', 'Jose Leonardo Ortiz')->first();
        if ($jlo) {
            $this->province_id = $jlo->province_id;
            $this->department_id = $jlo->department_id;
            $this->district_id = $jlo->id;
        }
    }

    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('zones', 'name')->ignore($this->editingId),
            ],
            'department_id' => 'required|exists:departments,id',
            'province_id' => 'required|exists:provinces,id',
            'district_id' => 'required|exists:districts,id',
            'description' => 'nullable|string|max:1000',
            'average_waste' => 'nullable|numeric|min:0|max:999999.99',
            'status' => 'required|in:active,inactive',
            'coords' => 'array',
            'coords.*.latitude' => 'required_with:coords|numeric|between:-90,90',
            'coords.*.longitude' => 'required_with:coords|numeric|between:-180,180',
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'El nombre de la zona es obligatorio.',
            'name.unique' => 'Ya existe una zona con este nombre.',
            'department_id.required' => 'El departamento es obligatorio.',
            'province_id.required' => 'La provincia es obligatoria.',
            'district_id.required' => 'El distrito es obligatorio.',
            'average_waste.numeric' => 'Los residuos promedio deben ser un numero valido.',
            'average_waste.min' => 'Los residuos promedio no pueden ser negativos.',
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDistrictFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDepartmentId(): void
    {
        $this->province_id = null;
        $this->district_id = null;
    }

    public function updatedProvinceId(): void
    {
        $this->district_id = null;
    }

    #[Computed]
    public function zones()
    {
        return ZoneModel::query()
            ->with(['district.province.department', 'zoneCoords'])
            ->withCount('zoneCoords')
            ->when($this->search !== '', function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->where('zones.name', 'like', '%'.$this->search.'%')
                        ->orWhere('zones.description', 'like', '%'.$this->search.'%')
                        ->orWhereHas('district', fn ($dq) => $dq->where('name', 'like', '%'.$this->search.'%'))
                        ->orWhereHas('district.province', fn ($pq) => $pq->where('name', 'like', '%'.$this->search.'%'))
                        ->orWhereHas('district.department', fn ($dq) => $dq->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->when($this->statusFilter !== '', fn (Builder $q) => $q->where('zones.status', $this->statusFilter))
            ->when($this->districtFilter !== '', fn (Builder $q) => $q->where('zones.district_id', $this->districtFilter))
            ->orderBy('zones.name')
            ->paginate(10);
    }

    #[Computed]
    public function departments()
    {
        return Department::orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function provinces()
    {
        if (! $this->department_id) {
            return collect();
        }

        return Province::where('department_id', $this->department_id)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function districts()
    {
        if (! $this->province_id) {
            return collect();
        }

        return District::where('province_id', $this->province_id)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function allDistricts()
    {
        return District::with(['province.department'])
            ->orderBy('name')
            ->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->createSessionId = uniqid('new-', true);
        $jlo = District::where('name', 'Jose Leonardo Ortiz')->first();
        if ($jlo) {
            $this->department_id = $jlo->department_id;
            $this->province_id = $jlo->province_id;
            $this->district_id = $jlo->id;
        }
        $this->activeTab = 'data';
        Flux::modal('zone-form')->show();
    }

    public function openEdit(int $id): void
    {
        $zone = ZoneModel::with('zoneCoords')->findOrFail($id);
        $this->editingId = $zone->id;
        $this->name = $zone->name;
        $this->district_id = $zone->district_id;
        $this->province_id = $zone->district->province_id ?? null;
        $this->department_id = $zone->district->department_id ?? null;
        $this->description = $zone->description ?? '';
        $this->average_waste = $zone->average_waste !== null ? (string) $zone->average_waste : null;
        $this->status = $zone->status ?? 'active';
        $this->coords = $zone->zoneCoords->map(fn ($c) => [
            'latitude' => (float) $c->latitude,
            'longitude' => (float) $c->longitude,
        ])->toArray();
        $this->coords_json = json_encode($this->coords);
        Flux::modal('zone-edit')->show();
    }

    #[On('open-viewer')]
    public function openViewer(int $id): void
    {
        $this->viewingId = $id;
        Flux::modal('zone-viewer')->show();
    }

    public function closeFormModal(): void
    {
        $this->resetForm();
        Flux::modal('zone-form')->close();
    }

    public function closeEditModal(): void
    {
        $this->resetForm();
        Flux::modal('zone-edit')->close();
    }

    public function closeViewer(): void
    {
        $this->viewingId = null;
        Flux::modal('zone-viewer')->close();
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function save(string $coordsJson = ''): void
    {
        if ($coordsJson) {
            $this->coords_json = $coordsJson;
        }

        if ($this->coords_json) {
            $decoded = json_decode($this->coords_json, true);
            if (is_array($decoded)) {
                $this->coords = $decoded;
            }
        }

        $validated = $this->validate();
        $coords = $this->coords;

        $area = $this->calculateArea($coords);

        if ($this->editingId) {
            $zone = ZoneModel::findOrFail($this->editingId);
            $zone->update([
                'name' => $validated['name'],
                'district_id' => $validated['district_id'],
                'description' => $validated['description'] ?? null,
                'average_waste' => $validated['average_waste'] ?? null,
                'status' => $validated['status'],
                'area' => $area,
            ]);
            $zone->zoneCoords()->delete();
            $this->saveCoords($zone, $coords);
            Flux::toast(variant: 'success', text: 'Zona actualizada correctamente.');
        } else {
            $zone = ZoneModel::create([
                'name' => $validated['name'],
                'district_id' => $validated['district_id'],
                'description' => $validated['description'] ?? null,
                'average_waste' => $validated['average_waste'] ?? null,
                'status' => $validated['status'],
                'area' => $area,
            ]);
            $this->saveCoords($zone, $coords);
            Flux::toast(variant: 'success', text: 'Zona registrada correctamente.');
        }

        $this->closeFormModal();
    }

    public function saveEdit(string $coordsJson = ''): void
    {
        $this->save($coordsJson);
        Flux::modal('zone-edit')->close();
    }

    public function saveAndFinish(): void
    {
        $this->save();
    }

    public function skipMapAndSave(): void
    {
        $this->coords = [];
        $this->save();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        Flux::modal('confirm-delete-zone')->show();
    }

    public function delete(): void
    {
        if (! $this->deletingId) {
            return;
        }

        $zone = ZoneModel::findOrFail($this->deletingId);

        if ($zone->schedulings()->exists()) {
            Flux::toast(variant: 'warning', text: 'La zona tiene programaciones asociadas y no puede eliminarse.');
            $this->deletingId = null;
            Flux::modal('confirm-delete-zone')->close();

            return;
        }

        $zone->delete();
        Flux::toast(variant: 'success', text: 'Zona eliminada correctamente.');
        $this->deletingId = null;
        Flux::modal('confirm-delete-zone')->close();
    }

    private function saveCoords(ZoneModel $zone, array $coords): void
    {
        foreach ($coords as $coord) {
            $zone->zoneCoords()->create([
                'latitude' => $coord['latitude'],
                'longitude' => $coord['longitude'],
            ]);
        }
    }

    private function calculateArea(array $coords): ?float
    {
        if (count($coords) < 3) {
            return null;
        }

        $earthRadius = 6378137.0;
        $area = 0.0;
        $numPoints = count($coords);

        for ($i = 0; $i < $numPoints; $i++) {
            $p1 = $coords[$i];
            $p2 = $coords[($i + 1) % $numPoints];

            $lat1 = deg2rad((float) $p1['latitude']);
            $lon1 = deg2rad((float) $p1['longitude']);
            $lat2 = deg2rad((float) $p2['latitude']);
            $lon2 = deg2rad((float) $p2['longitude']);

            $area += ($lon2 - $lon1) * (2 + sin($lat1) + sin($lat2));
        }

        $area = abs($area * $earthRadius * $earthRadius / 2.0);

        return round($area / 1000000, 4);
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->reset([
            'name',
            'department_id',
            'province_id',
            'district_id',
            'description',
            'average_waste',
            'status',
            'coords',
            'editingId',
            'createSessionId',
        ]);
        $this->status = 'active';
        $this->activeTab = 'data';
        $this->coords_json = '';
    }

    public function render()
    {
        return view('pages.scheduling.zones.index');
    }
}
