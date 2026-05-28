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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('code', 50)->nullable();
            $table->string('plate', 20)->unique();
            $table->year('year')->nullable();
            $table->smallInteger('occupant_capacity')->nullable();
            $table->decimal('load_capacity', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->foreignId('brand_id')
                ->nullable()
                ->constrained('brands')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('model_id')
                ->nullable()
                ->constrained('brandmodels')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('type_id')
                ->nullable()
                ->constrained('vehicletypes')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('color_id')
                ->nullable()
                ->constrained('vehiclecolors')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
