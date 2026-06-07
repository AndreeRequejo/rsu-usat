<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    protected $table = 'shifts';

    protected $fillable = [
        'name',
        'description',
        'hour_in',
        'hour_out',
    ];

    public function schedulings(): HasMany
    {
        return $this->hasMany(Scheduling::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
