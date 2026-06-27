<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceDetail extends Model
{
    protected $table = 'maintenance_details';

    protected $fillable = [
        'maintenance_schedule_id',
        'date',
        'observation',
        'image_path',
        'completed',
    ];

    protected $casts = [
        'date' => 'date',
        'completed' => 'boolean',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(MaintenanceSchedule::class, 'maintenance_schedule_id');
    }
}
