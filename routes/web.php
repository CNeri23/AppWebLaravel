<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\LogController;
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
});