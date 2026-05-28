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
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->decimal('latitud_start', 10, 7)->nullable();
            $table->decimal('latitude_end', 10, 7)->nullable();
            $table->decimal('longitude_start', 10, 7)->nullable();
            $table->decimal('longitude_end', 10, 7)->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
