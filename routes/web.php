<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('vehiculos/colores', 'vehicles.colors.index')->name('vehicles.colors');
    Route::livewire('vehiculos/marcas', 'vehicles.brands.index')->name('vehicles.brands.index');
    Route::livewire('vehiculos/modelos', 'vehicles.models.index')->name('vehicles.models.index');
    Route::livewire('vehiculos/tipos', 'vehicles.types.index')->name('vehicles.types.index');
    Route::livewire('personal/tipos', 'personal.types.index')->name('personal.types.index');
    Route::livewire('personal/personal', 'personal.personal.index')->name('personal.personal.index');
    Route::livewire('personal/asistencias', 'personal.attendance.index')->name('personal.attendance.index');
    Route::livewire('personal/contratos', 'personal.contracts.index')->name('personal.contracts.index');
    Route::livewire('personal/vacaciones', 'personal.vacations.index')->name('personal.vacations.index');
});

require __DIR__.'/settings.php';
