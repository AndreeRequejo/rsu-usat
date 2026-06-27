<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mantenimiento extends Model
{
    protected $table = 'mantenimientos';

    protected $fillable = [
        'nombre',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioMantenimiento::class);
    }
}
