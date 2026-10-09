<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\SubmoduloController;
use App\Http\Controllers\AccionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SystemSettingsController;
use App\Http\Controllers\PreferencesController;
use App\Http\Controllers\MiembroController;
use App\Http\Controllers\DireccionController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\MembresiaController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\SesionCajaController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\AsistenciaController;
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

    // Rutas dashboard

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Rutas configuración

    Route::get('/configuracion', [SystemSettingsController::class, 'index'])
        ->name('configuracion.index');

    Route::put('/configuracion', [SystemSettingsController::class, 'update'])
        ->name('configuracion.update');

    // Rutas preferencias

    Route::get('/preferencias', [PreferencesController::class, 'index'])
        ->name('preferencias.index');

    Route::put('/preferencias/tema', [SystemSettingsController::class, 'updateTheme'])
        ->name('preferencias.tema.update');

    // Rutas autenticación

    Route::post('/logout', [LoginController::class, 'logout'])
        ->name('logout');

    // Rutas usuarios

    Route::get('/usuarios', [UserController::class, 'index'])
        ->middleware('permiso:submodulo,usuarios')
        ->name('usuarios.index');

    Route::post('/usuarios', [UserController::class, 'store'])
        ->middleware('permiso:accion,usuarios.crear')
        ->name('usuarios.store');

    Route::put('/usuarios/{usuario}', [UserController::class, 'update'])
        ->middleware('permiso:accion,usuarios.editar')
        ->name('usuarios.update');

    Route::put('/usuarios/{usuario}/estado', [UserController::class, 'toggleEstado'])
        ->middleware('permiso:accion,usuarios.editar')
        ->name('usuarios.estado');

    Route::put('/usuarios/{usuario}/password', [UserController::class, 'updatePassword'])
        ->middleware('permiso:accion,usuarios.password')
        ->name('usuarios.password');

    Route::put('/usuarios/{usuario}/roles', [UserController::class, 'updateRoles'])
        ->middleware('permiso:accion,usuarios.roles')
        ->name('usuarios.roles');

    Route::delete('/usuarios/{usuario}', [UserController::class, 'destroy'])
        ->middleware('permiso:accion,usuarios.eliminar')
        ->name('usuarios.destroy');

    // Rutas miembros

    Route::get('/miembros', [MiembroController::class, 'index'])
        ->middleware('permiso:submodulo,miembros')
        ->name('miembros.index');

    Route::post('/miembros', [MiembroController::class, 'store'])
        ->middleware('permiso:accion,miembros.crear')
        ->name('miembros.store');

    Route::put('/miembros/{miembro}', [MiembroController::class, 'update'])
        ->middleware('permiso:accion,miembros.editar')
        ->name('miembros.update');

    Route::post('/miembros/{miembro}/usuario', [MiembroController::class, 'asignarUsuario'])
        ->middleware('permiso:accion,miembros.usuario')
        ->name('miembros.usuario');

    Route::delete('/miembros/{miembro}', [MiembroController::class, 'destroy'])
        ->middleware('permiso:accion,miembros.eliminar')
        ->name('miembros.destroy');

    // Rutas direcciones

    Route::get('/direcciones', [DireccionController::class, 'index'])
        ->middleware('permiso:submodulo,direcciones')
        ->name('direcciones.index');

    Route::post('/direcciones', [DireccionController::class, 'store'])
        ->middleware('permiso:accion,direcciones.crear')
        ->name('direcciones.store');

    Route::put('/direcciones/{direccion}', [DireccionController::class, 'update'])
        ->middleware('permiso:accion,direcciones.editar')
        ->name('direcciones.update');

    Route::delete('/direcciones/{direccion}', [DireccionController::class, 'destroy'])
        ->middleware('permiso:accion,direcciones.eliminar')
        ->name('direcciones.destroy');

    // Rutas planes

    Route::get('/planes', [PlanController::class, 'index'])
        ->middleware('permiso:submodulo,planes')
        ->name('planes.index');

    Route::post('/planes', [PlanController::class, 'store'])
        ->middleware('permiso:accion,planes.crear')
        ->name('planes.store');

    Route::put('/planes/{plan}', [PlanController::class, 'update'])
        ->middleware('permiso:accion,planes.editar')
        ->name('planes.update');

    Route::delete('/planes/{plan}', [PlanController::class, 'destroy'])
        ->middleware('permiso:accion,planes.eliminar')
        ->name('planes.destroy');

    Route::post('/planes/{plan}/toggle', [PlanController::class, 'toggleActivo'])
        ->middleware('permiso:accion,planes.estado')
        ->name('planes.toggle');

    // Rutas membresías

    Route::get('/membresias', [MembresiaController::class, 'index'])
        ->middleware('permiso:submodulo,membresias')
        ->name('membresias.index');

    Route::post('/membresias', [MembresiaController::class, 'store'])
        ->middleware('permiso:accion,membresias.crear')
        ->name('membresias.store');

    Route::get('/membresias/{membresia}', [MembresiaController::class, 'show'])
        ->middleware('permiso:accion,membresias.ver')
        ->name('membresias.show');

    Route::put('/membresias/{membresia}', [MembresiaController::class, 'update'])
        ->middleware('permiso:accion,membresias.editar')
        ->name('membresias.update');

    Route::post('/membresias/{membresia}/renovar', [MembresiaController::class, 'renovar'])
        ->middleware('permiso:accion,membresias.renovar')
        ->name('membresias.renovar');

    Route::post('/membresias/{membresia}/cancelar', [MembresiaController::class, 'cancelar'])
        ->middleware('permiso:accion,membresias.cancelar')
        ->name('membresias.cancelar');

    // Rutas tickets

    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])
        ->middleware('permiso:accion,membresias.ver')
        ->name('tickets.show');

    Route::get('/tickets/{ticket}/imprimir', [TicketController::class, 'print'])
        ->middleware('permiso:accion,membresias.ver')
        ->name('tickets.print');

    // Rutas asistencias

    Route::get('/asistencias', [AsistenciaController::class, 'index'])
        ->middleware('permiso:submodulo,asistencias')
        ->name('asistencias.index');

    Route::get('/asistencias/buscar', [AsistenciaController::class, 'buscar'])
        ->middleware('permiso:accion,asistencias.registrar')
        ->name('asistencias.buscar');

    Route::post('/asistencias', [AsistenciaController::class, 'store'])
        ->middleware('permiso:accion,asistencias.registrar')
        ->name('asistencias.store');

    Route::delete('/asistencias/{asistencia}', [AsistenciaController::class, 'destroy'])
        ->middleware('permiso:accion,asistencias.eliminar')
        ->name('asistencias.destroy');

    // Rutas caja

    Route::get('/cajas', [CajaController::class, 'index'])
        ->middleware('permiso:submodulo,cajas')
        ->name('cajas.index');

    Route::post('/cajas', [CajaController::class, 'store'])
        ->middleware('permiso:accion,cajas.crear')
        ->name('cajas.store');

    Route::get('/cajas/{caja}', [CajaController::class, 'show'])
        ->middleware('permiso:accion,cajas.ver')
        ->name('cajas.show');

    Route::put('/cajas/{caja}', [CajaController::class, 'update'])
        ->middleware('permiso:accion,cajas.editar')
        ->name('cajas.update');

    Route::patch('/cajas/{caja}/estado', [CajaController::class, 'cambiarEstado'])
        ->middleware('permiso:accion,cajas.editar')
        ->name('cajas.estado');

    // Rutas sesiones de caja

    Route::get('/sesiones-caja', [SesionCajaController::class, 'index'])
        ->middleware('permiso:accion,cajas.ver')
        ->name('cajas.sesiones.index');

    Route::post('/sesiones-caja/abrir', [SesionCajaController::class, 'abrir'])
        ->middleware('permiso:accion,cajas.abrir')
        ->name('cajas.sesiones.abrir');

    Route::get('/sesiones-caja/actual', [SesionCajaController::class, 'actual'])
        ->middleware('permiso:accion,cajas.ver')
        ->name('cajas.sesiones.actual');

    Route::get('/sesiones-caja/{sesion}', [SesionCajaController::class, 'show'])
        ->middleware('permiso:accion,cajas.ver')
        ->name('cajas.sesiones.show');

    Route::get('/sesiones-caja/{sesion}/movimientos', [SesionCajaController::class, 'movimientos'])
        ->middleware('permiso:accion,cajas.movimientos')
        ->name('cajas.sesiones.movimientos');

    Route::post('/sesiones-caja/{sesion}/retiros', [SesionCajaController::class, 'retirar'])
        ->middleware('permiso:accion,cajas.retirar')
        ->name('cajas.sesiones.retirar');

    Route::get('/sesiones-caja/{sesion}/arqueo', [SesionCajaController::class, 'arqueo'])
        ->middleware('permiso:accion,cajas.cerrar')
        ->name('cajas.sesiones.arqueo');

    Route::post('/sesiones-caja/{sesion}/cerrar', [SesionCajaController::class, 'cerrar'])
        ->middleware('permiso:accion,cajas.cerrar')
        ->name('cajas.sesiones.cerrar');

    // Rutas roles

    Route::get('/roles', [RoleController::class, 'index'])
        ->middleware('permiso:submodulo,roles')
        ->name('roles.index');

    Route::post('/roles', [RoleController::class, 'store'])
        ->middleware('permiso:accion,roles.crear')
        ->name('roles.store');

    Route::put('/roles/{rol}', [RoleController::class, 'update'])
        ->middleware('permiso:accion,roles.editar')
        ->name('roles.update');

    Route::delete('/roles/{rol}', [RoleController::class, 'destroy'])
        ->middleware('permiso:accion,roles.eliminar')
        ->name('roles.destroy');

    Route::get('/roles/{rol}/permisos', [RoleController::class, 'permisos'])
        ->middleware('permiso:accion,roles.permisos')
        ->name('roles.permisos');

    Route::put('/roles/{rol}/permisos', [RoleController::class, 'actualizarPermisos'])
        ->middleware('permiso:accion,roles.permisos')
        ->name('roles.actualizarPermisos');

    // Rutas logs

    Route::get('/logs', [LogController::class, 'index'])
        ->middleware('permiso:submodulo,logs')
        ->name('logs.index');

    // Rutas módulos

    Route::get('/modulos', [ModuloController::class, 'index'])
        ->middleware('permiso:submodulo,modulos')
        ->name('modulos.index');

    Route::post('/modulos', [ModuloController::class, 'store'])
        ->middleware('permiso:accion,modulos.crear')
        ->name('modulos.store');

    Route::put('/modulos/{modulo}', [ModuloController::class, 'update'])
        ->middleware('permiso:accion,modulos.editar')
        ->name('modulos.update');

    Route::delete('/modulos/{modulo}', [ModuloController::class, 'destroy'])
        ->middleware('permiso:accion,modulos.eliminar')
        ->name('modulos.destroy');

    Route::patch('/modulos/{modulo}/toggle', [ModuloController::class, 'toggle'])
        ->middleware('permiso:accion,modulos.activar')
        ->name('modulos.toggle');

    Route::patch('/modulos/{modulo}/reordenar', [ModuloController::class, 'reorder'])
        ->middleware('permiso:accion,modulos.reordenar')
        ->name('modulos.reordenar');

    // Rutas submódulos

    Route::post('/modulos/{modulo}/submodulos', [SubmoduloController::class, 'store'])
        ->middleware('permiso:accion,submodulos.crear')
        ->name('submodulos.store');

    Route::put('/submodulos/{submodulo}', [SubmoduloController::class, 'update'])
        ->middleware('permiso:accion,submodulos.editar')
        ->name('submodulos.update');

    Route::delete('/submodulos/{submodulo}', [SubmoduloController::class, 'destroy'])
        ->middleware('permiso:accion,submodulos.eliminar')
        ->name('submodulos.destroy');

    Route::patch('/submodulos/{submodulo}/toggle', [SubmoduloController::class, 'toggle'])
        ->middleware('permiso:accion,submodulos.activar')
        ->name('submodulos.toggle');

    Route::patch('/submodulos/{submodulo}/reordenar', [SubmoduloController::class, 'reorder'])
        ->middleware('permiso:accion,submodulos.reordenar')
        ->name('submodulos.reordenar');

    // Rutas acciones

    Route::get('/submodulos/{submodulo}/acciones', [AccionController::class, 'index'])
        ->middleware('permiso:submodulo,modulos')
        ->name('acciones.index');

    Route::post('/submodulos/{submodulo}/acciones', [AccionController::class, 'store'])
        ->middleware('permiso:accion,acciones.crear')
        ->name('acciones.store');

    Route::put('/acciones/{accion}', [AccionController::class, 'update'])
        ->middleware('permiso:accion,acciones.editar')
        ->name('acciones.update');

    Route::delete('/acciones/{accion}', [AccionController::class, 'destroy'])
        ->middleware('permiso:accion,acciones.eliminar')
        ->name('acciones.destroy');

    Route::patch('/acciones/{accion}/toggle', [AccionController::class, 'toggle'])
        ->middleware('permiso:accion,acciones.estado')
        ->name('acciones.toggle');

    Route::patch('/acciones/{accion}/reordenar', [AccionController::class, 'reorder'])
        ->middleware('permiso:accion,acciones.reordenar')
        ->name('acciones.reordenar');

    // Rutas perfil

    Route::get('/perfil', [ProfileController::class, 'index'])
        ->name('perfil');

    Route::put('/perfil', [ProfileController::class, 'update'])
        ->name('perfil.update');

    Route::put('/perfil/password', [ProfileController::class, 'updatePassword'])
        ->name('perfil.password');

    Route::post('/perfil/foto', [ProfileController::class, 'updatePhoto'])
        ->name('perfil.foto');

    Route::delete('/perfil/foto', [ProfileController::class, 'deletePhoto'])
        ->name('perfil.foto.delete');
});