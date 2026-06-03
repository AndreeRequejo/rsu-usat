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

    protected $casts = [
        'hour_in' => 'datetime:H:i',
        'hour_out' => 'datetime:H:i',
    ];

    public function schedulings(): HasMany
    {
        return $this->hasMany(Scheduling::class);
    }
}
