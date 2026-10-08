<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeccionController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/areas/{area}', [AreaController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('areas.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/modulos/{modulo}', [ModuloController::class, 'show'])->name('modulos.show');
    Route::get('/lecciones/{leccion}', [LeccionController::class, 'show'])->name('lecciones.show');
    Route::post('/lecciones/{leccion}/completar', [LeccionController::class, 'completar'])->name('lecciones.completar');
    Route::get('/lecciones/{leccion}/pdf', [LeccionController::class, 'pdf'])->name('lecciones.pdf');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
