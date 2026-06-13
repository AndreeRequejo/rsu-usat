<?php

namespace App\Livewire\Pages\Scheduling\Zones;

use Livewire\Component;
use App\Models\{Zone, Department, Province, District};

class ZoneExplorer extends Component
{
    public ?int $departmentId = null;
    public ?int $provinceId = null;
    public ?int $districtId = null;

    public function mount(): void
    {
        $district = District::where('name', 'Jose Leonardo Ortiz')->first();
        if ($district) {
            $this->departmentId = $district->department_id;
            $this->provinceId = $district->province_id;
            $this->districtId = $district->id;
        }
    }

    public function updatedDepartmentId(): void
    {
        $this->provinceId = null;
        $this->districtId = null;
    }

    public function updatedProvinceId(): void
    {
        $this->districtId = null;
    }

    public function getDepartmentsProperty()
    {
        return Department::orderBy('name')->get();
    }

    public function getProvincesProperty()
    {
        if (! $this->departmentId) {
            return collect();
        }

        return Province::where('department_id', $this->departmentId)->orderBy('name')->get();
    }

    public function getDistrictsProperty()
    {
        if (! $this->provinceId) {
            return collect();
        }

        return District::where('province_id', $this->provinceId)->orderBy('name')->get();
    }

    public function getFilteredZonesProperty()
    {
        $query = Zone::with(['district.province.department', 'zoneCoords'])
            ->whereHas('zoneCoords');

        if ($this->districtId) {
            $query->where('district_id', $this->districtId);
        } elseif ($this->provinceId) {
            $query->whereHas('district', fn ($q) => $q->where('province_id', $this->provinceId));
        } elseif ($this->departmentId) {
            $query->whereHas('district', fn ($q) => $q->where('department_id', $this->departmentId));
        }

        return $query->orderBy('name')->get();
    }

    public function getFilteredZonesJsonProperty(): array
    {
        return $this->filteredZones->map(fn ($zone) => [
            'id' => $zone->id,
            'name' => $zone->name,
            'description' => $zone->description,
            'status' => $zone->status,
            'average_waste' => $zone->average_waste,
            'district' => $zone->district->name ?? null,
            'province' => $zone->district->province->name ?? null,
            'department' => $zone->district->department->name ?? null,
            'coords' => $zone->zoneCoords->map(fn ($c) => [
                'lat' => (float) $c->latitude,
                'lng' => (float) $c->longitude,
            ])->toArray(),
        ])->toArray();
    }

    public function getZonesStatsProperty(): array
    {
        $zones = $this->filteredZones;

        return [
            'total' => $zones->count(),
            'active' => $zones->where('status', 'active')->count(),
            'total_points' => $zones->sum(fn ($z) => $z->zoneCoords->count()),
        ];
    }

    public function getLocationNameProperty(): string
    {
        if ($this->districtId) {
            $district = District::with('province.department')->find($this->districtId);
            if ($district) {
                return "{$district->name}, {$district->province->name}, {$district->department->name}";
            }
        }

        return '';
    }

    public function getDistrictCenterProperty(): array
    {
        if ($this->districtId) {
            $zone = Zone::where('district_id', $this->districtId)
                ->whereHas('zoneCoords')
                ->with('zoneCoords')
                ->first();

            if ($zone && $zone->zoneCoords->count() > 0) {
                return [
                    'lat' => (float) $zone->zoneCoords->avg('latitude'),
                    'lng' => (float) $zone->zoneCoords->avg('longitude'),
                ];
            }
        }

        return ['lat' => -6.756, 'lng' => -79.832];
    }

    public function render()
    {
        $this->dispatch('zones-updated', zones: $this->filteredZonesJson);

        return view('pages.scheduling.zones.components.zone-explorer');
    }
}
