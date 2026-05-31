<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('scheduling_id')->constrained('schedulings')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'scheduling_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_details');
    }
};