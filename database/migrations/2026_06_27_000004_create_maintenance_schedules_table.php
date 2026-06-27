<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_id')->constrained('maintenances')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('responsible_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('maintenance_type', ['preventive', 'cleaning', 'repair']);
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->unique(['maintenance_id', 'vehicle_id', 'day_of_week', 'start_time'], 'msch_vh_dy_st_uniq');
            $table->index(['vehicle_id', 'day_of_week'], 'msch_vh_dy_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_schedules');
    }
};
