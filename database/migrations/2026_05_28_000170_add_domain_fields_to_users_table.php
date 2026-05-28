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
        Schema::table('users', function (Blueprint $table) {
            $table->string('dni', 20)->nullable()->after('name');
            $table->date('birthdate')->nullable()->after('dni');
            $table->string('license', 50)->nullable()->after('birthdate');
            $table->string('address', 255)->nullable()->after('license');
            $table->unsignedBigInteger('current_team_id')->nullable()->after('remember_token');
            $table->string('profile_photo_path', 2048)->nullable()->after('current_team_id');
            $table->foreignId('usertype_id')
                ->nullable()
                ->after('profile_photo_path')
                ->constrained('usertypes')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('zone_id')
                ->nullable()
                ->after('usertype_id')
                ->constrained('zones')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['usertype_id']);
            $table->dropForeign(['zone_id']);
            $table->dropColumn([
                'dni',
                'birthdate',
                'license',
                'address',
                'current_team_id',
                'profile_photo_path',
                'usertype_id',
                'zone_id',
            ]);
        });
    }
};
