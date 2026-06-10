<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffGroup extends Model
{
    protected $fillable = [
        'name',
        'zone_id',
        'shift_id',
        'vehicle_id',
        'driver_id',
        'helper_one_id',
        'helper_two_id',
        'work_days',
        'active',
    ];

    protected $casts = [
        'work_days' => 'array',
        'active' => 'boolean',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'driver_id');
    }

    public function helperOne(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'helper_one_id');
    }

    public function helperTwo(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'helper_two_id');
    }
}
