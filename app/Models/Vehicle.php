<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $table = 'vehicles';

    protected $fillable = [
        'brand_id',
        'model_id',
        'color_id',
        'license_plate',
        'year',
        'type_id',
        'capacity',
        'status',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(BrandModel::class, 'model_id');
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(VehicleColor::class, 'color_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class, 'type_id');
    }

    public function schedulings(): HasMany
    {
        return $this->hasMany(Scheduling::class);
    }

    public function maintenanceSchedules(): HasMany
    {
        return $this->hasMany(MaintenanceSchedule::class);
    }
}