<?php

namespace Database\Seeders;

use App\Models\PersonalType;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        PersonalType::firstOrCreate(
            ['name' => 'Conductor'],
            ['description' => 'Personal autorizado para conducir vehiculos.']
        );

        PersonalType::firstOrCreate(
            ['name' => 'Ayudante'],
            ['description' => 'Personal de apoyo en la recoleccion.']
        );
    }
}
