<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_schedule_id')->constrained('maintenance_schedules')->cascadeOnDelete();
            $table->date('date');
            $table->text('observation')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('completed')->nullable();
            $table->timestamps();

            $table->unique(['maintenance_schedule_id', 'date'], 'mdet_sched_dt_uniq');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_details');
    }
};
