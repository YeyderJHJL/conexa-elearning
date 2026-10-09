<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DescargaResumenController;
use App\Http\Controllers\LeccionController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizController;
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
    Route::get('/modulos/{modulo}/resumen', [DescargaResumenController::class, 'modulo'])->name('modulos.resumen');
    Route::get('/areas/{area}/resumen', [DescargaResumenController::class, 'area'])->name('areas.resumen');
    Route::get('/modulos/{modulo}/quiz', [QuizController::class, 'show'])->name('quiz.show');
    Route::post('/modulos/{modulo}/quiz', [QuizController::class, 'store'])->name('quiz.enviar');
    Route::get('/modulos/{modulo}/quiz/intentos/{intento}', [QuizController::class, 'resultado'])->name('quiz.resultado');
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
