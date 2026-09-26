<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\SubmoduloController;
use App\Http\Controllers\AccionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [LoginController::class, 'login'])
    ->name('login.submit');

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::post('/logout', [LoginController::class, 'logout'])
        ->name('logout');

    Route::get('/usuarios', [UserController::class, 'index'])
        ->name('usuarios.index');

    Route::post('/usuarios', [UserController::class, 'store'])
        ->name('usuarios.store');

    Route::put('/usuarios/{usuario}', [UserController::class, 'update'])
        ->name('usuarios.update');

    Route::put('/usuarios/{usuario}/password', [UserController::class, 'updatePassword'])
        ->name('usuarios.password');

    Route::put('/usuarios/{usuario}/roles', [UserController::class, 'updateRoles'])
        ->name('usuarios.roles');

    Route::delete('/usuarios/{usuario}', [UserController::class, 'destroy'])
        ->name('usuarios.destroy');

    Route::get('/roles', [RoleController::class, 'index'])
        ->name('roles.index');

    Route::post('/roles', [RoleController::class, 'store'])
        ->name('roles.store');

    Route::put('/roles/{rol}', [RoleController::class, 'update'])
        ->name('roles.update');

    Route::delete('/roles/{rol}', [RoleController::class, 'destroy'])
        ->name('roles.destroy');

    Route::get('/logs', [LogController::class, 'index'])
        ->name('logs.index');

    Route::get('/modulos', [ModuloController::class, 'index'])
        ->name('modulos.index');

    Route::post('/modulos', [ModuloController::class, 'store'])
        ->name('modulos.store');

    Route::put('/modulos/{modulo}', [ModuloController::class, 'update'])
        ->name('modulos.update');

    Route::delete('/modulos/{modulo}', [ModuloController::class, 'destroy'])
        ->name('modulos.destroy');

    Route::patch('/modulos/{modulo}/toggle', [ModuloController::class, 'toggle'])
        ->name('modulos.toggle');

    Route::patch('/modulos/{modulo}/reordenar', [ModuloController::class, 'reorder'])
        ->name('modulos.reordenar');

    Route::get('/modulos/{modulo}/submodulos', [SubmoduloController::class, 'index'])
        ->name('submodulos.index');

    Route::post('/modulos/{modulo}/submodulos', [SubmoduloController::class, 'store'])
        ->name('submodulos.store');

    Route::put('/submodulos/{submodulo}', [SubmoduloController::class, 'update'])
        ->name('submodulos.update');

    Route::delete('/submodulos/{submodulo}', [SubmoduloController::class, 'destroy'])
        ->name('submodulos.destroy');

    Route::patch('/submodulos/{submodulo}/toggle', [SubmoduloController::class, 'toggle'])
        ->name('submodulos.toggle');

    Route::patch('/submodulos/{submodulo}/reordenar', [SubmoduloController::class, 'reorder'])
        ->name('submodulos.reordenar');

    Route::get('/submodulos/{submodulo}/acciones', [AccionController::class, 'index'])
        ->name('acciones.index');

    Route::post('/submodulos/{submodulo}/acciones', [AccionController::class, 'store'])
        ->name('acciones.store');

    Route::put('/acciones/{accion}', [AccionController::class, 'update'])
        ->name('acciones.update');

    Route::delete('/acciones/{accion}', [AccionController::class, 'destroy'])
        ->name('acciones.destroy');

    Route::patch('/acciones/{accion}/toggle', [AccionController::class, 'toggle'])
        ->name('acciones.toggle');

    Route::patch('/acciones/{accion}/reordenar', [AccionController::class, 'reorder'])
        ->name('acciones.reordenar');
});