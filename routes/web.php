<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('vehiculos/colores', 'vehicles.colors.index')->name('vehicles.colors');
    Route::livewire('vehiculos/marcas', 'vehicles.brands.index')->name('vehicles.brands.index');
    Route::livewire('vehiculos/modelos', 'vehicles.models.index')->name('vehicles.models.index');
    Route::livewire('vehiculos/tipos', 'vehicles.types.index')->name('vehicles.types.index');
});

require __DIR__.'/settings.php';
