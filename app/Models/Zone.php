<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zone extends Model
{
    protected $table = 'zones';

    protected $fillable = [
        'name',
        'description',
        'area',
        'average_waste',
        'status',
        'sector_id',
        'district_id',
    ];

    protected $casts = [
        'area' => 'decimal:2',
        'average_waste' => 'decimal:2',
    ];

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function zoneCoords(): HasMany
    {
        return $this->hasMany(ZoneCoord::class)->orderBy('id');
    }

    public function schedulings(): HasMany
    {
        return $this->hasMany(Scheduling::class);
    }

    public static function hasOverlap(array $coords, ?int $excludeId = null, ?int $districtId = null): bool
    {
        if (count($coords) < 3) {
            return false;
        }

        $query = self::whereHas('zoneCoords');

        if ($districtId !== null) {
            $query->where('district_id', $districtId);
        }

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $existing = $query->with('zoneCoords')->get();

        foreach ($existing as $zone) {
            $existingCoords = $zone->zoneCoords->map(fn ($c) => [
                'latitude' => (float) $c->latitude,
                'longitude' => (float) $c->longitude,
            ])->toArray();

            if (count($existingCoords) < 3) {
                continue;
            }

            if (self::polygonsOverlap($coords, $existingCoords)) {
                return true;
            }
        }

        return false;
    }

    private static function polygonsOverlap(array $poly1, array $poly2): bool
    {
        $n1 = count($poly1);
        $n2 = count($poly2);

        for ($i = 0; $i < $n1; $i++) {
            $a1 = $poly1[$i];
            $a2 = $poly1[($i + 1) % $n1];

            for ($j = 0; $j < $n2; $j++) {
                $b1 = $poly2[$j];
                $b2 = $poly2[($j + 1) % $n2];

                if (self::segmentsCross($a1, $a2, $b1, $b2)) {
                    return true;
                }
            }
        }

        foreach ($poly1 as $point) {
            if (self::pointInPolygon($point, $poly2)) {
                return true;
            }
        }

        foreach ($poly2 as $point) {
            if (self::pointInPolygon($point, $poly1)) {
                return true;
            }
        }

        return false;
    }

    private static function segmentsCross(array $a, array $b, array $c, array $d): bool
    {
        $o1 = self::orientation($a, $b, $c);
        $o2 = self::orientation($a, $b, $d);
        $o3 = self::orientation($c, $d, $a);
        $o4 = self::orientation($c, $d, $b);

        return $o1 !== $o2 && $o3 !== $o4;
    }

    private static function orientation(array $p, array $q, array $r): int
    {
        $val = ($q['latitude'] - $p['latitude']) * ($r['longitude'] - $q['longitude'])
            - ($q['longitude'] - $p['longitude']) * ($r['latitude'] - $q['latitude']);

        if (abs($val) < PHP_FLOAT_EPSILON) {
            return 0;
        }

        return $val > 0 ? 1 : 2;
    }

    private static function pointInPolygon(array $point, array $polygon): bool
    {
        $x = $point['longitude'];
        $y = $point['latitude'];
        $n = count($polygon);
        $inside = false;

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $polygon[$i]['longitude'];
            $yi = $polygon[$i]['latitude'];
            $xj = $polygon[$j]['longitude'];
            $yj = $polygon[$j]['latitude'];

            $intersect = (($yi > $y) !== ($yj > $y))
                && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi);

            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }
}
