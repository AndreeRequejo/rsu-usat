<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        $shifts = [
            [
                'name' => 'Turno Mañana',
                'description' => 'Turno de la manana.',
                'hour_in' => '04:00:00',
                'hour_out' => '07:00:00',
            ],
            [
                'name' => 'Turno Noche',
                'description' => 'Turno de la noche.',
                'hour_in' => '18:00:00',
                'hour_out' => '19:00:00',
            ],
            [
                'name' => 'Turno Madrugada',
                'description' => 'Turno de madrugada.',
                'hour_in' => '00:00:00',
                'hour_out' => '03:00:00',
            ],
        ];

        foreach ($shifts as $shift) {
            Shift::firstOrCreate(
                ['name' => $shift['name']],
                $shift
            );
        }
    }
}