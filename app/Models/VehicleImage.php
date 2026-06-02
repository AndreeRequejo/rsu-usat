<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleImage extends Model
{
    protected $table = 'vehicleimages';

    protected $fillable = [
        'vehicle_id',
        'image',
        'profile',
    ];

    protected $casts = [
        'profile' => 'boolean',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
