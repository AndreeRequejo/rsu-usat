<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->dropForeign(['sector_id']);
            $table->foreignId('sector_id')
                ->nullable()
                ->change();
            $table->foreign('sector_id')
                ->references('id')
                ->on('sectors')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->dropForeign(['sector_id']);
            $table->foreignId('sector_id')
                ->nullable(false)
                ->change();
            $table->foreign('sector_id')
                ->references('id')
                ->on('sectors')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }
};
