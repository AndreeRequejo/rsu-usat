<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $table = 'departments';

    protected $fillable = [
        'name',
        'description',
    ];

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}