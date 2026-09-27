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

Route::post('/registro', [LoginController::class, 'register'])
    ->name('register');

Route::post('/recuperar-password', [LoginController::class, 'forgotPassword'])
    ->name('password.email');

Route::get('/restablecer-password/{token}', [LoginController::class, 'showResetPassword'])
    ->name('password.reset');

Route::post('/restablecer-password', [LoginController::class, 'resetPassword'])
    ->name('password.update');

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::post('/logout', [LoginController::class, 'logout'])
        ->name('logout');


    Route::get('/usuarios', [UserController::class, 'index'])
        ->middleware('permiso:submodulo,usuarios')
        ->name('usuarios.index');

    Route::post('/usuarios', [UserController::class, 'store'])
        ->middleware('permiso:accion,usuarios.crear')
        ->name('usuarios.store');

    Route::put('/usuarios/{usuario}', [UserController::class, 'update'])
        ->middleware('permiso:submodulo,usuarios')
        ->name('usuarios.update');

    Route::put('/usuarios/{usuario}/password', [UserController::class, 'updatePassword'])
        ->middleware('permiso:submodulo,usuarios')
        ->name('usuarios.password');

    Route::put('/usuarios/{usuario}/roles', [UserController::class, 'updateRoles'])
        ->middleware('permiso:submodulo,usuarios')
        ->name('usuarios.roles');

    Route::delete('/usuarios/{usuario}', [UserController::class, 'destroy'])
        ->middleware('permiso:submodulo,usuarios')
        ->name('usuarios.destroy');


    Route::get('/roles', [RoleController::class, 'index'])
        ->middleware('permiso:submodulo,roles')
        ->name('roles.index');

    Route::post('/roles', [RoleController::class, 'store'])
        ->middleware('permiso:submodulo,roles')
        ->name('roles.store');

    Route::put('/roles/{rol}', [RoleController::class, 'update'])
        ->middleware('permiso:submodulo,roles')
        ->name('roles.update');

    Route::delete('/roles/{rol}', [RoleController::class, 'destroy'])
        ->middleware('permiso:submodulo,roles')
        ->name('roles.destroy');

    Route::get('/roles/{rol}/permisos', [RoleController::class, 'permisos'])
        ->middleware('permiso:submodulo,roles')
        ->name('roles.permisos');

    Route::put('/roles/{rol}/permisos', [RoleController::class, 'actualizarPermisos'])
        ->middleware('permiso:submodulo,roles')
        ->name('roles.actualizarPermisos');


    Route::get('/logs', [LogController::class, 'index'])
        ->middleware('permiso:submodulo,logs')
        ->name('logs.index');

    Route::get('/modulos', [ModuloController::class, 'index'])
        ->middleware('permiso:submodulo,modulos')
        ->name('modulos.index');

    Route::post('/modulos', [ModuloController::class, 'store'])
        ->middleware('permiso:submodulo,modulos')
        ->name('modulos.store');

    Route::put('/modulos/{modulo}', [ModuloController::class, 'update'])
        ->middleware('permiso:submodulo,modulos')
        ->name('modulos.update');

    Route::delete('/modulos/{modulo}', [ModuloController::class, 'destroy'])
        ->middleware('permiso:submodulo,modulos')
        ->name('modulos.destroy');

    Route::patch('/modulos/{modulo}/toggle', [ModuloController::class, 'toggle'])
        ->middleware('permiso:submodulo,modulos')
        ->name('modulos.toggle');

    Route::patch('/modulos/{modulo}/reordenar', [ModuloController::class, 'reorder'])
        ->middleware('permiso:submodulo,modulos')
        ->name('modulos.reordenar');

    Route::post('/modulos/{modulo}/submodulos', [SubmoduloController::class, 'store'])
        ->middleware('permiso:submodulo,modulos')
        ->name('submodulos.store');

    Route::put('/submodulos/{submodulo}', [SubmoduloController::class, 'update'])
        ->middleware('permiso:submodulo,modulos')
        ->name('submodulos.update');

    Route::delete('/submodulos/{submodulo}', [SubmoduloController::class, 'destroy'])
        ->middleware('permiso:submodulo,modulos')
        ->name('submodulos.destroy');

    Route::patch('/submodulos/{submodulo}/toggle', [SubmoduloController::class, 'toggle'])
        ->middleware('permiso:submodulo,modulos')
        ->name('submodulos.toggle');

    Route::patch('/submodulos/{submodulo}/reordenar', [SubmoduloController::class, 'reorder'])
        ->middleware('permiso:submodulo,modulos')
        ->name('submodulos.reordenar');

    Route::get('/submodulos/{submodulo}/acciones', [AccionController::class, 'index'])
        ->middleware('permiso:submodulo,modulos')
        ->name('acciones.index');

    Route::post('/submodulos/{submodulo}/acciones', [AccionController::class, 'store'])
        ->middleware('permiso:submodulo,modulos')
        ->name('acciones.store');

    Route::put('/acciones/{accion}', [AccionController::class, 'update'])
        ->middleware('permiso:submodulo,modulos')
        ->name('acciones.update');

    Route::delete('/acciones/{accion}', [AccionController::class, 'destroy'])
        ->middleware('permiso:submodulo,modulos')
        ->name('acciones.destroy');

    Route::patch('/acciones/{accion}/toggle', [AccionController::class, 'toggle'])
        ->middleware('permiso:submodulo,modulos')
        ->name('acciones.toggle');

    Route::patch('/acciones/{accion}/reordenar', [AccionController::class, 'reorder'])
        ->middleware('permiso:submodulo,modulos')
        ->name('acciones.reordenar');
});