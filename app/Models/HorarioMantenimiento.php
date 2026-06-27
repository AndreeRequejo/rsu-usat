<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HorarioMantenimiento extends Model
{
    protected $table = 'horarios_mantenimiento';

    protected $fillable = [
        'mantenimiento_id',
        'vehiculo_id',
        'responsable_id',
        'tipo',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
    ];

    public function mantenimiento(): BelongsTo
    {
        return $this->belongsTo(Mantenimiento::class);
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehiculo_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'responsable_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleHorarioMantenimiento::class, 'horario_id');
    }
}
