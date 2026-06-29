<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('scheduling_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('change_type', 30); // turn, vehicle, driver, helper
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('zone_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->foreignId('old_shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->foreignId('new_shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->foreignId('old_vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('new_vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('old_person_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('new_person_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('person_role', 20)->nullable(); // driver, helper
            $table->string('reason_preset', 100)->nullable();
            $table->string('reason_detail', 255)->nullable();
            $table->text('reason_full')->nullable();
            $table->unsignedInteger('affected_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduling_changes');
    }
};
