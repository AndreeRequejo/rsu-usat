<?php

namespace Database\Seeders;

use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class VehicleTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Compactador', 'description' => 'Vehiculo para recoleccion con compactacion.'],
            ['name' => 'Barredora', 'description' => 'Vehiculo para limpieza y barrido.'],
            ['name' => 'Volquete', 'description' => 'Vehiculo de transporte con tolva.'],
            ['name' => 'Camion cisterna', 'description' => 'Vehiculo para transporte de agua u otros fluidos.'],
            ['name' => 'Camioneta', 'description' => 'Vehiculo ligero de apoyo operativo.'],
            ['name' => 'Motocicleta', 'description' => 'Vehiculo ligero para supervision.'],
        ];

        foreach ($types as $type) {
            VehicleType::firstOrCreate(
                ['name' => $type['name']],
                ['description' => $type['description']]
            );
        }
    }
}
