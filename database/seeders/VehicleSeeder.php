<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\BrandModel;
use App\Models\Vehicle;
use App\Models\VehicleColor;
use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        $brandToyota = Brand::where('name', 'Toyota')->first();
        $brandNissan = Brand::where('name', 'Nissan')->first();
        $brandHino = Brand::where('name', 'Hino')->first();

        $modelHilux = $brandToyota ? BrandModel::where('name', 'Hilux')->where('brand_id', $brandToyota->id)->first() : null;
        $modelUrvan = $brandNissan ? BrandModel::where('name', 'Urvan')->where('brand_id', $brandNissan->id)->first() : null;
        $modelDutro = $brandHino ? BrandModel::where('name', 'Dutro')->where('brand_id', $brandHino->id)->first() : null;

        $typeCompactador = VehicleType::where('name', 'Compactador')->first();
        $typeCamioneta = VehicleType::where('name', 'Camioneta')->first();
        $typeVolquete = VehicleType::where('name', 'Volquete')->first();

        $colorVerde = VehicleColor::where('name', 'Verde')->first();
        $colorBlanco = VehicleColor::where('name', 'Blanco')->first();
        $colorGris = VehicleColor::where('name', 'Gris')->first();

        $vehicles = [
            [
                'name' => 'Compactador 001',
                'code' => 'COMP-001',
                'plate' => 'ABC-123',
                'year' => 2022,
                'occupant_capacity' => 2,
                'load_capacity' => 5000,
                'description' => 'Vehiculo compactador para recoleccion de residuos.',
                'status' => true,
                'brand_id' => $brandToyota?->id,
                'model_id' => $modelHilux?->id,
                'type_id' => $typeCompactador?->id,
                'color_id' => $colorVerde?->id,
            ],
            [
                'name' => 'Urvan Transporte',
                'code' => 'URV-001',
                'plate' => 'DEF-456',
                'year' => 2021,
                'occupant_capacity' => 12,
                'load_capacity' => 1000,
                'description' => 'Van para transporte de personal operativo.',
                'status' => true,
                'brand_id' => $brandNissan?->id,
                'model_id' => $modelUrvan?->id,
                'type_id' => $typeCamioneta?->id,
                'color_id' => $colorBlanco?->id,
            ],
            [
                'name' => 'Camion Volquete',
                'code' => 'VOL-001',
                'plate' => 'GHI-789',
                'year' => 2020,
                'occupant_capacity' => 2,
                'load_capacity' => 8000,
                'description' => 'Camion volquete para transporte de materiales.',
                'status' => true,
                'brand_id' => $brandHino?->id,
                'model_id' => $modelDutro?->id,
                'type_id' => $typeVolquete?->id,
                'color_id' => $colorGris?->id,
            ],
        ];

        foreach ($vehicles as $vehicle) {
            Vehicle::firstOrCreate(
                ['code' => $vehicle['code']],
                $vehicle
            );
        }
    }
}