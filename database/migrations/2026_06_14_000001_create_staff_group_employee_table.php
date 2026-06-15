<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_group_employee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_group_id')->constrained('staff_groups')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('role');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['staff_group_id', 'employee_id']);
            $table->index(['staff_group_id', 'role']);
        });

        DB::table('staff_groups')->orderBy('id')->each(function ($group) {
            $timestamp = now();
            $records = [];

            if ($group->driver_id) {
                $records[] = [
                    'staff_group_id' => $group->id,
                    'employee_id' => $group->driver_id,
                    'role' => 'driver',
                    'sort_order' => 0,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            if ($group->helper_one_id) {
                $records[] = [
                    'staff_group_id' => $group->id,
                    'employee_id' => $group->helper_one_id,
                    'role' => 'helper',
                    'sort_order' => 0,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            if ($group->helper_two_id) {
                $records[] = [
                    'staff_group_id' => $group->id,
                    'employee_id' => $group->helper_two_id,
                    'role' => 'helper',
                    'sort_order' => 1,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            if (!empty($records)) {
                DB::table('staff_group_employee')->insert($records);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_group_employee');
    }
};
