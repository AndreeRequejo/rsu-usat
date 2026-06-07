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
}
