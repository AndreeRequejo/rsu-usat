<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleMaintenanceSchedule extends Model
{
    protected $fillable = [
        'vehicle_maintenance_program_id',
        'vehicle_id',
        'responsible_id',
        'type',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(VehicleMaintenanceProgram::class, 'vehicle_maintenance_program_id');
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
        return $this->hasMany(VehicleMaintenanceDetail::class);
    }
}
