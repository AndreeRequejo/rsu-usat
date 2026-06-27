<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_horarios_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('horario_id')
                ->constrained('horarios_mantenimiento')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->date('fecha');
            $table->text('observacion')->nullable();
            $table->string('imagen')->nullable();
            $table->boolean('realizado')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_horarios_mantenimiento');
    }
};
