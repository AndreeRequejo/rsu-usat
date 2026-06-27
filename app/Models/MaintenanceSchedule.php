<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceSchedule extends Model
{
    protected $table = 'maintenance_schedules';

    protected $fillable = [
        'maintenance_id',
        'vehicle_id',
        'responsible_id',
        'maintenance_type',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    protected $casts = [];

    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(Maintenance::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'responsible_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(MaintenanceDetail::class);
    }
}
