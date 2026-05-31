<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('contract_type');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('salary', 10, 2);
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->integer('vacation_days_per_year')->default(30);
            $table->integer('probation_period_months')->default(3);
            $table->boolean('is_active')->default(true);
            $table->text('termination_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};