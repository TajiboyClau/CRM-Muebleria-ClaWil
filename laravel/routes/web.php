<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\OportunidadController;
use App\Http\Controllers\ActividadController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ProductoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'panel.role:administrador,supervisor'])->name('dashboard');

Route::middleware(['auth', 'verified', 'panel.role:administrador,supervisor'])
    ->group(function () {
        Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');
        Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
        Route::get('/clientes/crear', [ClienteController::class, 'create'])->name('clientes.create');
        Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');
        Route::get('/clientes/{cliente}/editar', [ClienteController::class, 'edit'])->name('clientes.edit');
        Route::patch('/clientes/{cliente}', [ClienteController::class, 'update'])->name('clientes.update');
        Route::patch('/clientes/{cliente}/asignar', [ClienteController::class, 'assign'])->name('clientes.assign');
        Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy'])->name('clientes.destroy');
        Route::get('/oportunidades', [OportunidadController::class, 'index'])->name('oportunidades.index');
        Route::get('/actividades', [ActividadController::class, 'index'])->name('actividades.index');
        Route::patch('/actividades/{actividad}/completar', [ActividadController::class, 'complete'])->name('actividades.complete');
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
    });

Route::middleware(['auth', 'verified', 'panel.role:administrador'])
    ->group(function () {
        Route::get('/productos/crear', [ProductoController::class, 'create'])->name('productos.create');
        Route::post('/productos', [ProductoController::class, 'store'])->name('productos.store');
        Route::get('/productos/{producto}/editar', [ProductoController::class, 'edit'])->name('productos.edit');
        Route::patch('/productos/{producto}', [ProductoController::class, 'update'])->name('productos.update');
        Route::patch('/productos/{producto}/desactivar', [ProductoController::class, 'deactivate'])->name('productos.deactivate');
        Route::get('/actividades/crear', [ActividadController::class, 'create'])->name('actividades.create');
        Route::post('/actividades', [ActividadController::class, 'store'])->name('actividades.store');
        Route::get('/actividades/{actividad}/editar', [ActividadController::class, 'edit'])->name('actividades.edit');
        Route::patch('/actividades/{actividad}', [ActividadController::class, 'update'])->name('actividades.update');
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::get('/usuarios/crear', [UsuarioController::class, 'create'])->name('usuarios.create');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::get('/usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit');
        Route::patch('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
        Route::patch('/usuarios/{usuario}/estado', [UsuarioController::class, 'toggle'])->name('usuarios.toggle');
    });

Route::middleware(['auth', 'panel.role:administrador,supervisor'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
