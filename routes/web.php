<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CanalController;
use App\Http\Controllers\MultiviewController;
use App\Http\Controllers\StreamProxyController;
use App\Models\Canal;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Rutas protegidas por autenticación
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rutas de los Usuarios
    Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/create', [UserController::class, 'create'])->name('usuarios.create'); 
    Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');
    Route::get('/usuarios/{usuario}/edit', [UserController::class, 'edit'])->name('usuarios.edit'); 
    Route::post('/usuarios/{usuario}', [UserController::class, 'update'])->name('usuarios.update'); 
    Route::delete('/usuarios/{usuario}', [UserController::class, 'destroy'])->name('usuarios.destroy');

    // Rutas de los canales
    Route::get('/canales', [CanalController::class, 'index'])->name('canales.index');
    Route::post('/canales', [CanalController::class, 'store'])->name('canales.store');
    Route::put('/canales/{canal}', [CanalController::class, 'update'])->name('canales.update');
    Route::delete('/canales/{canal}', [CanalController::class, 'destroy'])->name('canales.destroy');
    Route::get('/canales/activos', function () {
        return response()->json(Canal::where('estado', 'Activo')->get());
    });

    // Ruta de Multiview
    Route::get('/multiview', [MultiviewController::class, 'index'])->name('multiview.index');
});

require __DIR__.'/auth.php';
