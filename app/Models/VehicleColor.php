<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleColor extends Model
{
    protected $table = 'vehiclecolors';

    protected $fillable = [
        'name',
        'code',
        'description',
    ];
}
