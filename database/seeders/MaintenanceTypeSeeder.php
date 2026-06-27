<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MaintenanceTypeSeeder extends Seeder
{
    public const TYPES = [
        'preventive' => 'Preventivo',
        'cleaning' => 'Limpieza',
        'repair' => 'Reparación',
    ];

    public function run(): void
    {
        config(['maintenance.types' => self::TYPES]);
    }
}
