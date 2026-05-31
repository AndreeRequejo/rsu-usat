<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sector extends Model
{
    protected $table = 'sectors';

    protected $fillable = [
        'name',
        'district_id',
    ];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class);
    }
}