<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_maintenance_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_maintenance_schedule_id');
            $table->date('maintenance_date');
            $table->text('observation')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('completed')->default(false);
            $table->timestamps();

            $table->foreign('vehicle_maintenance_schedule_id', 'vmd_schedule_fk')
                ->references('id')
                ->on('vehicle_maintenance_schedules')
                ->cascadeOnDelete();
            $table->unique(['vehicle_maintenance_schedule_id', 'maintenance_date'], 'vmd_schedule_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_maintenance_details');
    }
};
