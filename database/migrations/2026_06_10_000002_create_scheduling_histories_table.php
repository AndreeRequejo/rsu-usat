<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduling_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduling_id')->nullable()->constrained('schedulings')->nullOnDelete();
            $table->string('action', 50);
            $table->text('description');
            $table->json('changes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduling_histories');
    }
};
