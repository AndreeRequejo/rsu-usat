<?php

namespace Database\Seeders;

use App\Models\EmployeeType;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        EmployeeType::firstOrCreate(
            ['name' => 'Conductor'],
            ['description' => 'Personal autorizado para conducir vehiculos.']
        );

        EmployeeType::firstOrCreate(
            ['name' => 'Ayudante'],
            ['description' => 'Personal de apoyo en la recoleccion.']
        );
    }
}
