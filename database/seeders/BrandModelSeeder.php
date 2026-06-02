<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\BrandModel;
use Illuminate\Database\Seeder;

class BrandModelSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            'Toyota' => [
                ['name' => 'Hilux', 'code' => 'HIL-TOY-25', 'description' => 'Pick-up de trabajo.'],
                ['name' => 'Coaster', 'code' => 'COA-TOY-25', 'description' => 'Bus mediano para transporte.'],
            ],
            'Nissan' => [
                ['name' => 'NP300', 'code' => 'NP3-NIS-25', 'description' => 'Pick-up utilitaria.'],
                ['name' => 'Urvan', 'code' => 'URV-NIS-25', 'description' => 'Van de transporte.'],
            ],
            'Volvo' => [
                ['name' => 'FMX', 'code' => 'FMX-VOL-25', 'description' => 'Camion pesado para trabajo severo.'],
            ],
            'Mercedes-Benz' => [
                ['name' => 'Atego', 'code' => 'ATE-MER-25', 'description' => 'Camion mediano multiproposito.'],
            ],
            'Hino' => [
                ['name' => 'Dutro', 'code' => 'DUT-HIN-25', 'description' => 'Camion ligero de reparto.'],
            ],
            'Isuzu' => [
                ['name' => 'NQR', 'code' => 'NQR-ISU-25', 'description' => 'Camion comercial liviano.'],
            ],
        ];

        foreach ($brands as $brandName => $models) {
            $brand = Brand::firstOrCreate(['name' => $brandName]);

            foreach ($models as $model) {
                BrandModel::firstOrCreate(
                    ['name' => $model['name'], 'brand_id' => $brand->id],
                    ['code' => $model['code'], 'description' => $model['description']]
                );
            }
        }
    }
}
