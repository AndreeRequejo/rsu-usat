<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\District;
use App\Models\Province;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $lambayeque = Department::firstOrCreate(
            ['code' => '14'],
            ['name' => 'Lambayeque']
        );

        $chiclayo = Province::firstOrCreate(
            ['code' => '1401', 'department_id' => $lambayeque->id],
            ['name' => 'Chiclayo']
        );

        District::firstOrCreate(
            ['code' => '140105', 'province_id' => $chiclayo->id, 'department_id' => $lambayeque->id],
            ['name' => 'Jose Leonardo Ortiz']
        );

        $otherDistricts = [
            '140101' => 'Chiclayo',
            '140102' => 'Chongoyape',
            '140103' => 'Eten',
            '140104' => 'Eten Puerto',
            '140106' => 'La Victoria',
            '140107' => 'Lagunas',
            '140108' => 'Monsefu',
            '140109' => 'Nueva Arica',
            '140110' => 'Oyotun',
            '140111' => 'Picsi',
            '140112' => 'Pimentel',
            '140113' => 'Reque',
            '140114' => 'Santa Rosa',
            '140115' => 'Saña',
            '140116' => 'Cayalti',
            '140117' => 'Patapo',
            '140118' => 'Pomalca',
            '140119' => 'Pucala',
            '140120' => 'Tuman',
        ];

        foreach ($otherDistricts as $code => $name) {
            District::firstOrCreate(
                ['code' => $code, 'province_id' => $chiclayo->id, 'department_id' => $lambayeque->id],
                ['name' => $name]
            );
        }
    }
}
