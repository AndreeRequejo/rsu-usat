<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    protected $table = 'contracts';

    protected $fillable = [
        'employee_id',
        'contract_type',
        'start_date',
        'end_date',
        'salary',
        'department_id',
        'vacation_days_per_year',
        'probation_period_months',
        'is_active',
        'termination_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'salary' => 'decimal:2',
        'vacation_days_per_year' => 'integer',
        'probation_period_months' => 'integer',
        'is_active' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function isEffectivelyActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->contract_type === 'Temporal' && $this->end_date) {
            return ! $this->end_date->isPast();
        }

        return true;
    }
}
