<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('employee_type_id')->constrained('employee_types')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('dni', 8)->unique();
            $table->date('birthdate')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};