<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $table = 'employees';

    protected $fillable = [
        'user_id',
        'employee_type_id',
        'first_name',
        'last_name',
        'dni',
        'birthdate',
        'phone',
        'address',
        'active',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employeeType(): BelongsTo
    {
        return $this->belongsTo(EmployeeType::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function vacations(): HasMany
    {
        return $this->hasMany(Vacation::class);
    }

    public function employeeImages(): HasMany
    {
        return $this->hasMany(EmployeeImage::class);
    }

    public function groupDetails(): HasMany
    {
        return $this->hasMany(GroupDetail::class);
    }
}
