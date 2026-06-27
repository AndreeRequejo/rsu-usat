<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('maintenance_records');
    }

    public function down(): void
    {
        Schema::create('maintenance_records', function ($table) {
            $table->id();
            $table->foreignId('maintenance_schedule_id')->constrained('maintenance_schedules')->cascadeOnDelete();
            $table->date('maintenance_date');
            $table->text('description');
            $table->string('image_url')->nullable();
            $table->timestamps();
        });
    }
};
