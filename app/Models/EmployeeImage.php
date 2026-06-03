<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeImage extends Model
{
    protected $table = 'employee_images';

    protected $fillable = [
        'employee_id',
        'image',
        'profile',
    ];

    protected $casts = [
        'profile' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
