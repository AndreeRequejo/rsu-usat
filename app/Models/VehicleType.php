<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleType extends Model
{
    protected $table = 'vehicletypes';

    protected $fillable = [
        'name',
        'description',
    ];
}
