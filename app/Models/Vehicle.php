<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vehicle extends Model
{
    protected $table = 'vehicles';

    protected $fillable = [
        'name',
        'code',
        'plate',
        'year',
        'occupant_capacity',
        'load_capacity',
        'description',
        'status',
        'brand_id',
        'model_id',
        'type_id',
        'color_id',
    ];

    protected $casts = [
        'year' => 'integer',
        'occupant_capacity' => 'integer',
        'load_capacity' => 'decimal:2',
        'status' => 'boolean',
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

    public function maintenances(): HasManyThrough
    {
        return $this->hasManyThrough(Maintenance::class, MaintenanceSchedule::class);
    }

    public function vehicleImages(): HasMany
    {
        return $this->hasMany(VehicleImage::class);
    }

    public function profileImage(): HasOne
    {
        return $this->hasOne(VehicleImage::class, 'vehicle_id')
            ->where('profile', true);
    }
}
