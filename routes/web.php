<?php

use App\Livewire\Pages\Personnel\Employee;
use App\Livewire\Pages\Scheduling\Holidays\Index as HolidayIndex;
use App\Livewire\Pages\Scheduling\Zones\Zone;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'scheduling.dashboard.index')->name('dashboard');

    Route::livewire('vehiculos/colores', 'vehicles.colors.index')->name('vehicles.colors');
    Route::livewire('vehiculos/marcas', 'vehicles.brands.index')->name('vehicles.brands.index');
    Route::livewire('vehiculos/modelos', 'vehicles.models.index')->name('vehicles.models.index');
    Route::livewire('vehiculos/tipos', 'vehicles.types.index')->name('vehicles.types.index');
    Route::livewire('vehiculos/vehiculos', 'vehicles.vehicles.index')->name('vehicles.vehicles.index');
    Route::livewire('personal/tipos', 'personnel.types.index')->name('personnel.types.index');
    Route::livewire('personal/personal', Employee::class)->name('personnel.personnel.index');
    Route::livewire('personal/asistencias', 'personnel.attendance.index')->name('personnel.attendance.index');
    Route::livewire('personal/contratos', 'personnel.contracts.index')->name('personnel.contracts.index');
    Route::livewire('personal/vacaciones', 'personnel.vacations.index')->name('personnel.vacations.index');
    Route::livewire('programacion/turnos', 'scheduling.shifts.index')->name('scheduling.shifts.index');
    Route::livewire('programacion/zonas', Zone::class)->name('scheduling.zones.index');
    Route::livewire('programacion/feriados', HolidayIndex::class)->name('scheduling.holidays.index');
    Route::livewire('programacion/grupos', 'scheduling.groups.index')->name('scheduling.groups.index');
    Route::livewire('programacion/programacion', 'scheduling.scheduling.index')->name('scheduling.scheduling.index');
});

require __DIR__.'/settings.php';
