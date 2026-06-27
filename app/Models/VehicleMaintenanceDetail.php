<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleMaintenanceDetail extends Model
{
    protected $fillable = [
        'vehicle_maintenance_schedule_id',
        'maintenance_date',
        'observation',
        'image_path',
        'completed',
    ];

    protected $casts = [
        'maintenance_date' => 'date',
        'completed' => 'boolean',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(VehicleMaintenanceSchedule::class, 'vehicle_maintenance_schedule_id');
    }
}
