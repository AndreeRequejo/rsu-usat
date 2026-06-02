<?php

namespace Database\Seeders;

use App\Models\EmployeeType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Database\Seeders\BrandSeeder;
use Database\Seeders\BrandModelSeeder;
use Database\Seeders\VehicleColorSeeder;
use Database\Seeders\VehicleTypeSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Administrador',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('admin'),
        ]);

        EmployeeType::firstOrCreate(
            ['name' => 'Conductor'],
            ['description' => 'Personal autorizado para conducir vehiculos.']
        );

        EmployeeType::firstOrCreate(
            ['name' => 'Ayudante'],
            ['description' => 'Personal de apoyo en la recoleccion.']
        );

        $this->call([
            VehicleTypeSeeder::class,
            BrandSeeder::class,
            BrandModelSeeder::class,
            VehicleColorSeeder::class,
        ]);
    }
}
