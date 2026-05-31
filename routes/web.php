<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('vehiculos/colores', 'vehicles.colors.index')->name('vehicles.colors');
    Route::livewire('vehiculos/marcas', 'vehicles.brands.index')->name('vehicles.brands.index');
    Route::livewire('vehiculos/modelos', 'vehicles.models.index')->name('vehicles.models.index');
    Route::livewire('vehiculos/tipos', 'vehicles.types.index')->name('vehicles.types.index');
    Route::livewire('personal/tipos', 'personnel.types.index')->name('personnel.types.index');
    Route::livewire('personal/personal', 'personnel.personnel.index')->name('personnel.personal.index');
    Route::livewire('personal/asistencias', 'personnel.attendance.index')->name('personnel.attendance.index');
    Route::livewire('personal/contratos', 'personnel.contracts.index')->name('personnel.contracts.index');
    Route::livewire('personal/vacaciones', 'personnel.vacations.index')->name('personnel.vacations.index');
});

require __DIR__.'/settings.php';
