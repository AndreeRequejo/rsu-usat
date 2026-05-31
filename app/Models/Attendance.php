<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $table = 'attendances';

    protected $fillable = [
        'employee_id',
        'attendance_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}