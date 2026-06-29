<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchedulingChange extends Model
{
    protected $fillable = [
        'user_id',
        'change_type',
        'start_date',
        'end_date',
        'zone_id',
        'old_shift_id',
        'new_shift_id',
        'old_vehicle_id',
        'new_vehicle_id',
        'old_person_id',
        'new_person_id',
        'person_role',
        'reason_preset',
        'reason_detail',
        'reason_full',
        'affected_count',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function oldShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'old_shift_id');
    }

    public function newShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'new_shift_id');
    }

    public function oldVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'old_vehicle_id');
    }

    public function newVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'new_vehicle_id');
    }

    public function oldPerson(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'old_person_id');
    }

    public function newPerson(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'new_person_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SchedulingChangeItem::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->change_type) {
            'turn' => 'Turno',
            'vehicle' => 'Vehiculo',
            'driver' => 'Conductor',
            'helper' => 'Ocupante',
            default => ucfirst($this->change_type),
        };
    }

    public function getTypeBadgeColorAttribute(): string
    {
        return match ($this->change_type) {
            'turn' => '#F4C542',
            'vehicle' => '#1976D2',
            'driver' => '#4CAF50',
            'helper' => '#00BCD4',
            default => '#999999',
        };
    }
}
