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
        $today = Carbon::today('America/Lima')->toDateString();

        $shifts = Shift::orderBy('id')->get();
        $zones = Zone::orderBy('id')->take(3)->get();
        $vehicles = Vehicle::orderBy('id')->take(3)->get();

        if ($shifts->isEmpty() || $zones->isEmpty() || $vehicles->count() < 3) {
            $this->command->warn('Faltan datos base (Shift/Zone/Vehicle). Se necesitan al menos 3 vehículos.');
            return;
        }

        $conductores = collect([
            Employee::where('dni', '70000001')->first(), // Diaz Vidarte Miguel
            Employee::where('dni', '70000002')->first(), // Guerrero Carlos
            Employee::where('dni', '71234567')->first(), // Garcia Torres Maria Elena
            Employee::where('dni', '75432189')->first(), // Rodriguez Mendoza Carlos Alberto
        ])->filter()->values();

        $ayudantes = collect([
            Employee::where('dni', '70000003')->first(), // Gomez Miguel
            Employee::where('dni', '70000004')->first(), // Ramirez Fernando
            Employee::where('dni', '70000005')->first(), // Perez Juan
            Employee::where('dni', '70000006')->first(), // Ramirez Alvaro
            Employee::where('dni', '76890123')->first(), // Cruz Fernandez Jose Manuel
        ])->filter()->values();

        if ($conductores->count() < 3 || $ayudantes->count() < 3) {
            $this->command->warn('No se encontraron suficientes empleados por DNI. Verifica que existan en la BD (mínimo 3 conductores y 3 ayudantes).');
            return;
        }

        foreach ($shifts as $shift) {

            // Cada turno usa los mismos 3 vehículos/zonas; no hay conflicto porque el
            // constraint único es (date + shift_id + vehicle_id), y aquí shift_id cambia.
            $vehiculo1 = $vehicles[0];
            $vehiculo2 = $vehicles[1];
            $vehiculo3 = $vehicles[2];

            $zona1 = $zones[0];
            $zona2 = $zones->get(1, $zones[0]);
            $zona3 = $zones->get(2, $zones[0]);

            // Dentro de un mismo turno no repetimos empleados entre las 3 programaciones,
            // para evitar conflictos de disponibilidad (mismo turno, misma fecha).
            $conductor1 = $conductores[0];
            $conductor2 = $conductores[1];
            $conductor3 = $conductores[2];

            $ayudante1 = $ayudantes[0];
            $ayudante2 = $ayudantes[1];
            $ayudante3 = $ayudantes[2];
            $ayudante4 = $ayudantes->get(3, $ayudantes[0]);

            // ── Programación 1: COMPLETA (conductor + hasta 2 ayudantes, todos presentes) ──
            $sched1 = Scheduling::firstOrCreate(
                ['date' => $today, 'shift_id' => $shift->id, 'vehicle_id' => $vehiculo1->id],
                ['zone_id' => $zona1->id, 'status' => 'Programado', 'notes' => 'Prueba - completa']
            );

            $equipo1 = $this->limitarPorCapacidad($vehiculo1, [$conductor1, $ayudante1, $ayudante2]);
            $this->asignarEquipo($sched1, $equipo1);
            $this->marcarPresente($shift, $today, $equipo1);

            // ── Programación 2: INCOMPLETA parcial (conductor presente, ayudante(s) con falta) ──
            $sched2 = Scheduling::firstOrCreate(
                ['date' => $today, 'shift_id' => $shift->id, 'vehicle_id' => $vehiculo2->id],
                ['zone_id' => $zona2->id, 'status' => 'Programado', 'notes' => 'Prueba - incompleta parcial']
            );

            $equipo2 = $this->limitarPorCapacidad($vehiculo2, [$conductor2, $ayudante3]);
            $this->asignarEquipo($sched2, $equipo2);
            $this->marcarPresente($shift, $today, [$conductor2]); // el/los ayudante(s) quedan con falta

            // ── Programación 3: INCOMPLETA (nadie marcó asistencia) ──
            $sched3 = Scheduling::firstOrCreate(
                ['date' => $today, 'shift_id' => $shift->id, 'vehicle_id' => $vehiculo3->id],
                ['zone_id' => $zona3->id, 'status' => 'Programado', 'notes' => 'Prueba - sin asistencia']
            );

            $equipo3 = $this->limitarPorCapacidad($vehiculo3, [$conductor3, $ayudante4]);
            $this->asignarEquipo($sched3, $equipo3);
            // Sin marcarPresente: todos quedan con "Falta".
        }

        $this->command->info('DashboardSeeder ejecutado correctamente: 3 programaciones creadas por cada turno (' . $shifts->count() . ' turnos).');
    }

    /**
     * Recorta el equipo (conductor + ayudantes) según la capacidad real del vehículo,
     * para no crear más GroupDetail que cupos disponibles (occupant_capacity).
     * El primer elemento del array se asume conductor.
     */
    private function limitarPorCapacidad(Vehicle $vehicle, array $employees): array
    {
        $employees = array_filter($employees);
        $capacidad = max(1, $vehicle->occupant_capacity ?? 1);

        return array_slice($employees, 0, $capacidad);
    }

    /**
     * Crea los GroupDetail para una programación. El primer elemento del array
     * se asume conductor (posición 0) y el resto ayudantes, respetando el orden
     * que usa el componente (driver_id = primero, helper_ids = resto).
     */
    private function asignarEquipo(Scheduling $scheduling, array $employees): void
    {
        foreach ($employees as $emp) {
            GroupDetail::firstOrCreate([
                'scheduling_id' => $scheduling->id,
                'employee_id' => $emp->id,
            ]);
        }
    }

    /**
     * Marca asistencia "Presente" (Ingreso) para los empleados indicados en la fecha dada.
     */
    private function marcarPresente(Shift $shift, string $date, array $employees): void
    {
        foreach (array_filter($employees) as $emp) {
            Attendance::firstOrCreate(
                ['employee_id' => $emp->id, 'attendance_date' => $date, 'type' => 'Ingreso'],
                ['shift_id' => $shift->id, 'attendance_time' => '06:05:00', 'status' => 'Presente', 'notes' => 'Prueba']
            );
        }
    }
}