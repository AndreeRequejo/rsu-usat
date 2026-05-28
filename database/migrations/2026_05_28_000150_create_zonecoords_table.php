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
        Schema::create('zonecoords', function (Blueprint $table) {
            $table->id();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->foreignId('zone_id')
                ->constrained('zones')
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
        Schema::dropIfExists('zonecoords');
    }
};
