<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRecord extends Model
{
    protected $table = 'maintenance_records';

    protected $fillable = [
        'maintenance_schedule_id',
        'maintenance_date',
        'description',
        'image_url',
    ];

    protected $casts = [
        'maintenance_date' => 'date',
    ];

    public function maintenanceSchedule(): BelongsTo
    {
        return $this->belongsTo(MaintenanceSchedule::class);
    }
}
