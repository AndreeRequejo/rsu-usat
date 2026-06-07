<?php

namespace App\Livewire\Pages\Scheduling\Zones;

use App\Models\District;
use App\Models\Zone;
use App\Models\ZoneCoord;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ZoneMapPicker extends Component
{
    public array $coords = [];

    public ?int $zoneId = null;

    public bool $readOnly = false;

    public string $storageKey = 'coords-storage-new';

    public function mount(array $coords = [], ?int $zoneId = null, bool $readOnly = false, string $storageKey = 'coords-storage-new'): void
    {
        $this->coords = $coords;
        $this->zoneId = $zoneId;
        $this->readOnly = $readOnly;
        $this->storageKey = $storageKey;
    }

    #[Computed]
    public function referenceZones()
    {
        return Zone::query()
            ->with('zoneCoords')
            ->when($this->zoneId, fn ($q) => $q->where('id', '!=', $this->zoneId))
            ->whereHas('zoneCoords')
            ->get()
            ->map(function (Zone $zone) {
                $zone->load('district');

                return [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    'status' => $zone->status,
                    'coords' => $zone->zoneCoords->map(fn (ZoneCoord $c) => [
                        'latitude' => (float) $c->latitude,
                        'longitude' => (float) $c->longitude,
                    ])->toArray(),
                ];
            })
            ->values()
            ->toArray();
    }

    #[Computed]
    public function districtCenter()
    {
        $jlo = District::where('name', 'Jose Leonardo Ortiz')->first();
        if (! $jlo) {
            return ['lat' => -6.756, 'lng' => -79.832, 'zoom' => 14];
        }

        $coords = ZoneCoord::whereHas('zone', fn ($q) => $q->where('district_id', $jlo->id))->get();
        if ($coords->isEmpty()) {
            return ['lat' => -6.756, 'lng' => -79.832, 'zoom' => 14];
        }

        $lat = $coords->avg('latitude');
        $lng = $coords->avg('longitude');

        return ['lat' => (float) $lat, 'lng' => (float) $lng, 'zoom' => 14];
    }

    public function render()
    {
        return view('pages.scheduling.zones.components.map-picker');
    }
}
