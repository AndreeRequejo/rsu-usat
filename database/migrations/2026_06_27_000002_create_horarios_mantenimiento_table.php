<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mantenimiento_id')
                ->constrained('mantenimientos')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('vehiculo_id')
                ->constrained('vehicles')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('responsable_id')
                ->constrained('employees')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('tipo', 50);
            $table->string('dia_semana', 15);
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_mantenimiento');
    }
};
