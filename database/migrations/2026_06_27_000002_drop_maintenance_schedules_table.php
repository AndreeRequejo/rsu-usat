<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('maintenance_schedules');
    }

    public function down(): void
    {
        Schema::create('maintenance_schedules', function ($table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->date('scheduled_date');
            $table->string('type');
            $table->timestamps();
            $table->index(['vehicle_id', 'scheduled_date']);
        });
    }
};
