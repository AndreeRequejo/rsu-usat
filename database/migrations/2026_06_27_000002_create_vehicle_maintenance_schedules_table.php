<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_maintenance_program_id');
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('responsible_id');
            $table->string('type', 30);
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->foreign('vehicle_maintenance_program_id', 'vms_program_fk')
                ->references('id')
                ->on('vehicle_maintenance_programs')
                ->cascadeOnDelete();
            $table->foreign('vehicle_id', 'vms_vehicle_fk')
                ->references('id')
                ->on('vehicles')
                ->cascadeOnDelete();
            $table->foreign('responsible_id', 'vms_responsible_fk')
                ->references('id')
                ->on('employees')
                ->cascadeOnDelete();
            $table->index(['vehicle_maintenance_program_id', 'vehicle_id', 'day_of_week'], 'vms_program_vehicle_day_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_maintenance_schedules');
    }
};
