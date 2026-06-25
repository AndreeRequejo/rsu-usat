<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchedulingChangeItem extends Model
{
    protected $fillable = [
        'scheduling_change_id',
        'scheduling_id',
        'before',
        'after',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
    ];

    public function schedulingChange(): BelongsTo
    {
        return $this->belongsTo(SchedulingChange::class);
    }

    public function scheduling(): BelongsTo
    {
        return $this->belongsTo(Scheduling::class);
    }
}
