<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Database\Seeder;

class ContractSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::all();

        $contracts = [
            [
                'employee_id' => $employees->where('dni', '75432189')->first()?->id,
                'contract_type' => 'Nombrado',
                'start_date' => '2020-01-15',
                'end_date' => null,
                'salary' => 2500.00,
                'vacation_days_per_year' => 30,
                'probation_period_months' => 0,
                'is_active' => true,
            ],
            [
                'employee_id' => $employees->where('dni', '71234567')->first()?->id,
                'contract_type' => 'Permanente',
                'start_date' => '2021-06-01',
                'end_date' => null,
                'salary' => 2200.00,
                'vacation_days_per_year' => 30,
                'probation_period_months' => 3,
                'is_active' => true,
            ],
            [
                'employee_id' => $employees->where('dni', '76890123')->first()?->id,
                'contract_type' => 'Nombrado',
                'start_date' => '2022-03-10',
                'end_date' => null,
                'salary' => 1800.00,
                'vacation_days_per_year' => 30,
                'probation_period_months' => 0,
                'is_active' => true,
            ],
        ];

        foreach ($contracts as $contract) {
            if ($contract['employee_id']) {
                Contract::firstOrCreate(
                    [
                        'employee_id' => $contract['employee_id'],
                        'contract_type' => $contract['contract_type'],
                    ],
                    $contract
                );
            }
        }
    }
}