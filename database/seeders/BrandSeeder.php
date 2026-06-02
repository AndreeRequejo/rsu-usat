<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['name' => 'Toyota', 'description' => 'Marca japonesa de vehiculos comerciales y ligeros.'],
            ['name' => 'Nissan', 'description' => 'Marca japonesa con amplia linea de vehiculos.'],
            ['name' => 'Volvo', 'description' => 'Marca sueca de camiones y maquinaria pesada.'],
            ['name' => 'Mercedes-Benz', 'description' => 'Marca alemana de camiones y vehiculos.'],
            ['name' => 'Hino', 'description' => 'Marca japonesa especializada en camiones.'],
            ['name' => 'Isuzu', 'description' => 'Marca japonesa enfocada en vehiculos comerciales.'],
        ];

        foreach ($brands as $brand) {
            Brand::firstOrCreate(
                ['name' => $brand['name']],
                ['description' => $brand['description']]
            );
        }
    }
}
