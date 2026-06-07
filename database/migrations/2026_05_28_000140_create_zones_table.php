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
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->decimal('area', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->decimal('average_waste', 10, 2)->nullable();
            $table->string('status', 50)->default('active');
            $table->foreignId('sector_id')
                ->constrained('sectors')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('district_id')
                ->constrained('districts')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};
