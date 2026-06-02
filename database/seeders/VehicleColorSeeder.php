<?php

namespace Database\Seeders;

use App\Models\VehicleColor;
use Illuminate\Database\Seeder;

class VehicleColorSeeder extends Seeder
{
    public function run(): void
    {
        $colors = [
            ['name' => 'Verde', 'code' => '#2E8B57', 'description' => 'Color institucional.'],
            ['name' => 'Blanco', 'code' => '#FFFFFF', 'description' => 'Color base.'],
            ['name' => 'Gris', 'code' => '#9E9E9E', 'description' => 'Color neutro.'],
            ['name' => 'Azul', 'code' => '#104CAD', 'description' => 'Color operativo.'],
            ['name' => 'Amarillo', 'code' => '#F4C542', 'description' => 'Color de seguridad.'],
            ['name' => 'Rojo', 'code' => '#E53935', 'description' => 'Color de alerta.'],
        ];

        foreach ($colors as $color) {
            VehicleColor::firstOrCreate(
                ['code' => strtoupper($color['code'])],
                ['name' => $color['name'], 'description' => $color['description']]
            );
        }
    }
}
