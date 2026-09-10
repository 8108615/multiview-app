<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CanalController;
use App\Http\Controllers\MultiviewController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Rutas protegidas por autenticación
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rutas de los canales
    Route::get('/canales', [CanalController::class, 'index'])->name('canales.index');
    Route::post('/canales', [CanalController::class, 'store'])->name('canales.store');
    Route::delete('/canales/{canal}', [CanalController::class, 'destroy'])->name('canales.destroy');

    // Ruta de Multiview
    Route::get('/multiview', [MultiviewController::class, 'index'])->name('multiview.index');

});

require __DIR__.'/auth.php';
