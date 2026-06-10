<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\District;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\Sector;
use App\Models\Shift;
use App\Models\StaffGroup;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffGroupSeeder extends Seeder
{
    public function run(): void
    {
        $driverType = EmployeeType::firstOrCreate(
            ['name' => 'Conductor'],
            ['description' => 'Personal autorizado para conducir vehiculos.']
        );

        $helperType = EmployeeType::firstOrCreate(
            ['name' => 'Ayudante'],
            ['description' => 'Personal de apoyo en la recoleccion.']
        );

        $employees = [
            ['dni' => '70000001', 'first_name' => 'Miguel', 'last_name' => 'Diaz Vidarte', 'employee_type_id' => $driverType->id],
            ['dni' => '70000002', 'first_name' => 'Carlos', 'last_name' => 'Guerrero', 'employee_type_id' => $driverType->id],
            ['dni' => '70000003', 'first_name' => 'Miguel', 'last_name' => 'Gomez', 'employee_type_id' => $helperType->id],
            ['dni' => '70000004', 'first_name' => 'Fernando', 'last_name' => 'Ramirez', 'employee_type_id' => $helperType->id],
            ['dni' => '70000005', 'first_name' => 'Juan', 'last_name' => 'Perez', 'employee_type_id' => $helperType->id],
            ['dni' => '70000006', 'first_name' => 'Alvaro', 'last_name' => 'Ramirez', 'employee_type_id' => $helperType->id],
        ];

        foreach ($employees as $employee) {
            $user = User::firstOrCreate(
                ['email' => strtolower(str_replace(' ', '.', $employee['first_name'].'.'.$employee['last_name'])).'@rsu.test'],
                [
                    'name' => $employee['first_name'].' '.$employee['last_name'],
                    'password' => Hash::make('password'),
                ]
            );

            $model = Employee::firstOrCreate(
                ['dni' => $employee['dni']],
                $employee + [
                    'user_id' => $user->id,
                    'birthdate' => '1990-01-01',
                    'phone' => '999999999',
                    'address' => 'Chiclayo',
                    'active' => true,
                ]
            );

            Contract::firstOrCreate(
                ['employee_id' => $model->id, 'contract_type' => 'Permanente'],
                [
                    'start_date' => '2025-01-01',
                    'end_date' => null,
                    'salary' => 1800,
                    'vacation_days_per_year' => 30,
                    'probation_period_months' => 0,
                    'is_active' => true,
                ]
            );
        }

        $district = District::first();
        $sector = Sector::firstOrCreate(
            ['name' => 'Sector Operativo'],
            [
                'description' => 'Sector creado para programaciones de prueba.',
                'district_id' => $district?->id,
            ]
        );

        $zones = [
            'Zona de prueba',
            'Norte',
            'Oeste',
        ];

        foreach ($zones as $zoneName) {
            Zone::firstOrCreate(
                ['name' => $zoneName],
                [
                    'description' => 'Zona operativa para programaciones.',
                    'area' => 12,
                    'average_waste' => 20,
                    'status' => 'active',
                    'sector_id' => $sector->id,
                    'district_id' => $district?->id,
                ]
            );
        }

        $shift = Shift::first() ?? Shift::create([
            'name' => 'Turno Manana',
            'description' => 'Turno de manana.',
            'hour_in' => '06:00:00',
            'hour_out' => '14:00:00',
        ]);

        $vehicle = Vehicle::first();
        if (! $vehicle) {
            return;
        }

        $driver = Employee::where('dni', '70000001')->first();
        $helperOne = Employee::where('dni', '70000003')->first();
        $helperTwo = Employee::where('dni', '70000004')->first();
        $zone = Zone::where('name', 'Zona de prueba')->first();

        StaffGroup::updateOrCreate(
            ['name' => 'Grupo A'],
            [
                'zone_id' => $zone->id,
                'shift_id' => $shift->id,
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver->id,
                'helper_one_id' => $helperOne->id,
                'helper_two_id' => $helperTwo->id,
                'work_days' => [1, 3, 5, 7],
                'active' => true,
            ]
        );
    }
}
