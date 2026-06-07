<?php

namespace App\Livewire\Pages\Scheduling\Zones;

use App\Models\Zone;
use App\Models\ZoneCoord;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ZoneViewer extends Component
{
    public ?int $zoneId = null;

    public function mount(?int $zoneId = null): void
    {
        $this->zoneId = $zoneId;
    }

    #[Computed]
    public function zone()
    {
        if (! $this->zoneId) {
            return null;
        }

        return Zone::with(['district.province.department', 'zoneCoords'])
            ->find($this->zoneId);
    }

    #[Computed]
    public function referenceZones()
    {
        if (! $this->zoneId) {
            return collect();
        }

        return Zone::query()
            ->with('zoneCoords')
            ->where('id', '!=', $this->zoneId)
            ->whereHas('zoneCoords')
            ->get()
            ->map(function (Zone $zone) {
                return [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    'coords' => $zone->zoneCoords->map(fn (ZoneCoord $c) => [
                        'latitude' => (float) $c->latitude,
                        'longitude' => (float) $c->longitude,
                    ])->toArray(),
                ];
            });
    }

    public function render()
    {
        return view('pages.scheduling.zones.components.viewer');
    }
}
