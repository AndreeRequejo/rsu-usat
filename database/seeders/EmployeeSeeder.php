<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $conductorType = EmployeeType::where('name', 'Conductor')->first();
        $ayudanteType = EmployeeType::where('name', 'Ayudante')->first();

        $usersData = [
            [
                'name' => 'Carlos Rodriguez',
                'email' => 'carlos.rodriguez@gmail.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Maria Garcia',
                'email' => 'maria.garcia@gmail.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Jose Cruz',
                'email' => 'jose.cruz@gmail.com',
                'password' => Hash::make('password'),
            ],
        ];

        $employees = [
            [
                'first_name' => 'Carlos Alberto',
                'last_name' => 'Rodriguez Mendoza',
                'dni' => '75432189',
                'birthdate' => '1985-06-15',
                'phone' => '987654321',
                'address' => 'Av. Peru 456, Chiclayo',
                'active' => true,
            ],
            [
                'first_name' => 'Maria Elena',
                'last_name' => 'Garcia Torres',
                'dni' => '71234567',
                'birthdate' => '1990-03-22',
                'phone' => '956123478',
                'address' => 'Jr. Lima 789, Lambayeque',
                'active' => true,
            ],
            [
                'first_name' => 'Jose Manuel',
                'last_name' => 'Cruz Fernandez',
                'dni' => '76890123',
                'birthdate' => '1995-11-08',
                'phone' => '923456789',
                'address' => 'Calle Los Almendros 123, Chiclayo',
                'active' => true,
            ],
        ];

        foreach ($usersData as $index => $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            $employeeData = $employees[$index];
            $employeeData['user_id'] = $user->id;
            $employeeData['employee_type_id'] = $index < 2 ? $conductorType->id : $ayudanteType->id;

            Employee::firstOrCreate(
                ['dni' => $employeeData['dni']],
                $employeeData
            );
        }
    }
}
