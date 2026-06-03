<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleColor extends Model
{
    protected $table = 'vehiclecolors';

    protected $fillable = [
        'name',
        'code',
        'description',
    ];

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'color_id');
    }
}
