<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vacation extends Model
{
    protected $table = 'vacations';

    protected $fillable = [
        'employee_id',
        'request_date',
        'requested_days',
        'start_date',
        'end_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'request_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'requested_days' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}