<?php

namespace Database\Seeders;

use App\Models\EmployeeType;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
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
            LocationSeeder::class,
            VehicleTypeSeeder::class,
            BrandSeeder::class,
            BrandModelSeeder::class,
            VehicleColorSeeder::class,
            EmployeeSeeder::class,
            ContractSeeder::class,
            VehicleSeeder::class,
            ShiftSeeder::class,
            StaffGroupSeeder::class,
            HolidaySeeder::class,
        ]);
    }
}
