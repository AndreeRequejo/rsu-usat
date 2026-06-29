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
        Schema::create('scheduling_change_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduling_change_id')->constrained('scheduling_changes')->cascadeOnDelete();
            $table->foreignId('scheduling_id')->constrained('schedulings')->cascadeOnDelete();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduling_change_items');
    }
};
