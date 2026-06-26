<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\GroupDetail;
use App\Models\Scheduling;
use App\Models\Shift;
use App\Models\Vehicle;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DashboardSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today()->toDateString();

        $shift    = Shift::first();
        $vehicles = Vehicle::take(3)->get();
        $zones    = Zone::take(3)->get();
        $employees = Employee::take(6)->get();

        if (! $shift || $vehicles->isEmpty() || $zones->isEmpty() || $employees->isEmpty()) {
            $this->command->warn('Faltan datos base. Ejecuta primero los seeders base.');
            return;
        }

        if ($vehicles->count() < 3) {
            $this->command->warn('Se necesitan al menos 3 vehículos (unique: date+shift+vehicle). Solo hay ' . $vehicles->count() . '.');
            return;
        }

        // ── Programación 1: COMPLETA ──
        $sched1 = Scheduling::firstOrCreate(
            ['date' => $today, 'shift_id' => $shift->id, 'vehicle_id' => $vehicles[0]->id],
            [
                'zone_id' => $zones[0]->id,
                'status'  => 'Programado',
                'notes'   => 'Prueba - completa',
            ]
        );

        foreach ($employees->take(2) as $emp) {
            GroupDetail::firstOrCreate(['scheduling_id' => $sched1->id, 'employee_id' => $emp->id]);

            Attendance::firstOrCreate(
                ['employee_id' => $emp->id, 'attendance_date' => $today, 'type' => 'ingreso'],
                ['shift_id' => $shift->id, 'attendance_time' => '06:05:00', 'status' => 'presente', 'notes' => 'Prueba']
            );
        }

        // ── Programación 2: INCOMPLETA (1 presente, 2 faltantes) ──
        $sched2 = Scheduling::firstOrCreate(
            ['date' => $today, 'shift_id' => $shift->id, 'vehicle_id' => $vehicles[1]->id],
            [
                'zone_id' => $zones->get(1, $zones[0])->id,
                'status'  => 'Programado',
                'notes'   => 'Prueba - incompleta',
            ]
        );

        foreach ($employees->slice(2, 3)->values() as $index => $emp) {
            GroupDetail::firstOrCreate(['scheduling_id' => $sched2->id, 'employee_id' => $emp->id]);

            if ($index === 0) {
                Attendance::firstOrCreate(
                    ['employee_id' => $emp->id, 'attendance_date' => $today, 'type' => 'ingreso'],
                    ['shift_id' => $shift->id, 'attendance_time' => '06:10:00', 'status' => 'presente', 'notes' => 'Prueba']
                );
            }
        }

        // ── Programación 3: INCOMPLETA (nadie marcó) ──
        $sched3 = Scheduling::firstOrCreate(
            ['date' => $today, 'shift_id' => $shift->id, 'vehicle_id' => $vehicles[2]->id],
            [
                'zone_id' => $zones->get(2, $zones[0])->id,
                'status'  => 'Programado',
                'notes'   => 'Prueba - sin asistencia',
            ]
        );

        $emp = $employees->last();
        GroupDetail::firstOrCreate(['scheduling_id' => $sched3->id, 'employee_id' => $emp->id]);
    }
}