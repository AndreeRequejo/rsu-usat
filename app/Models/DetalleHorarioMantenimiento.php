<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleHorarioMantenimiento extends Model
{
    protected $table = 'detalle_horarios_mantenimiento';

    protected $fillable = [
        'horario_id',
        'fecha',
        'observacion',
        'imagen',
        'realizado',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'realizado' => 'boolean',
        ];
    }

    public function horario(): BelongsTo
    {
        return $this->belongsTo(HorarioMantenimiento::class, 'horario_id');
    }
}
