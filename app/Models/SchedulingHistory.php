<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchedulingHistory extends Model
{
    protected $fillable = [
        'scheduling_id',
        'action',
        'description',
        'changes',
        'user_id',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function scheduling(): BelongsTo
    {
        return $this->belongsTo(Scheduling::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
