<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DayOfWeekSeeder extends Seeder
{
    public const DAYS = [
        'monday' => 'Lunes',
        'tuesday' => 'Martes',
        'wednesday' => 'Miércoles',
        'thursday' => 'Jueves',
        'friday' => 'Viernes',
        'saturday' => 'Sábado',
        'sunday' => 'Domingo',
    ];

    public function run(): void
    {
        config(['maintenance.days_of_week' => self::DAYS]);
    }
}
