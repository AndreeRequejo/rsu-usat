<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $holidays = [
            ['date' => '2026-01-01', 'name' => 'Año Nuevo'],
            ['date' => '2026-04-02', 'name' => 'Jueves Santo'],
            ['date' => '2026-04-03', 'name' => 'Viernes Santo'],
            ['date' => '2026-05-01', 'name' => 'Dia del Trabajo'],
            ['date' => '2026-06-07', 'name' => 'Dia de la Bandera'],
            ['date' => '2026-06-29', 'name' => 'San Pedro y San Pablo'],
            ['date' => '2026-07-23', 'name' => 'Dia de la Fuerza Aerea del Peru'],
            ['date' => '2026-07-28', 'name' => 'Fiestas Patrias'],
            ['date' => '2026-07-29', 'name' => 'Fiestas Patrias'],
            ['date' => '2026-08-06', 'name' => 'Batalla de Junin'],
            ['date' => '2026-08-30', 'name' => 'Santa Rosa de Lima'],
            ['date' => '2026-10-08', 'name' => 'Combate de Angamos'],
            ['date' => '2026-11-01', 'name' => 'Dia de Todos los Santos'],
            ['date' => '2026-12-08', 'name' => 'Inmaculada Concepcion'],
            ['date' => '2026-12-09', 'name' => 'Batalla de Ayacucho'],
            ['date' => '2026-12-25', 'name' => 'Navidad'],
            ['date' => '2027-01-01', 'name' => 'Año Nuevo'],
            ['date' => '2027-03-25', 'name' => 'Jueves Santo'],
            ['date' => '2027-03-26', 'name' => 'Viernes Santo'],
            ['date' => '2027-05-01', 'name' => 'Dia del Trabajo'],
            ['date' => '2027-06-07', 'name' => 'Dia de la Bandera'],
            ['date' => '2027-06-29', 'name' => 'San Pedro y San Pablo'],
            ['date' => '2027-07-23', 'name' => 'Dia de la Fuerza Aerea del Peru'],
            ['date' => '2027-07-28', 'name' => 'Fiestas Patrias'],
            ['date' => '2027-07-29', 'name' => 'Fiestas Patrias'],
            ['date' => '2027-08-06', 'name' => 'Batalla de Junin'],
            ['date' => '2027-08-30', 'name' => 'Santa Rosa de Lima'],
            ['date' => '2027-10-08', 'name' => 'Combate de Angamos'],
            ['date' => '2027-11-01', 'name' => 'Dia de Todos los Santos'],
            ['date' => '2027-12-08', 'name' => 'Inmaculada Concepcion'],
            ['date' => '2027-12-09', 'name' => 'Batalla de Ayacucho'],
            ['date' => '2027-12-25', 'name' => 'Navidad'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::updateOrCreate(
                ['date' => $holiday['date']],
                [
                    'name' => $holiday['name'],
                    'description' => 'Feriado nacional.',
                ]
            );
        }
    }
}
