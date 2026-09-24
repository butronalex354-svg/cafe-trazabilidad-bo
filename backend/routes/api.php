<?php

use App\Http\Controllers\Api\AdministradorController;
use App\Http\Controllers\Api\AlertaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClimaController;
use App\Http\Controllers\Api\ControlCalidadController;
use App\Http\Controllers\Api\CosechaController;
use App\Http\Controllers\Api\DiagnosticoController;
use App\Http\Controllers\Api\EtapaProcesamientoController;
use App\Http\Controllers\Api\InventarioController;
use App\Http\Controllers\Api\ObservacionController;
use App\Http\Controllers\Api\ParcelaController;
use App\Http\Controllers\Api\ProductorController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [AuthController::class, 'actualizarCuenta']);
    Route::put('/me/password', [AuthController::class, 'cambiarPassword']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Modulo 1 - Produccion (RF01-03)
    Route::get('/productor/perfil', [ProductorController::class, 'show']);
    Route::put('/productor/perfil', [ProductorController::class, 'update']);

    Route::apiResource('parcelas', ParcelaController::class)->except(['show']);
    Route::apiResource('cosechas', CosechaController::class)->except(['show']);

    // Modulo 2 - Monitoreo (RF04-05)
    Route::get('/parcelas/{id}/clima', [ClimaController::class, 'porParcela']);
    Route::apiResource('observaciones', ObservacionController::class)->only(['index', 'store', 'update', 'destroy']);

    // Modulo 4 - Calidad (RF06-10)
    Route::get('/diagnosticos', [DiagnosticoController::class, 'index']);
    Route::post('/diagnosticos', [DiagnosticoController::class, 'store']);
    Route::delete('/diagnosticos/{id}', [DiagnosticoController::class, 'destroy']);
    Route::get('/alertas', [AlertaController::class, 'index']);
    Route::put('/alertas/{id}/leida', [AlertaController::class, 'marcarLeida']);
    Route::put('/alertas/{id}/resuelta', [AlertaController::class, 'marcarResuelta']);
    Route::apiResource('control-calidad', ControlCalidadController::class)->only(['index', 'store', 'update', 'destroy']);

    // Modulo 5 - Procesamiento (RF11-12)
    Route::apiResource('etapas-procesamiento', EtapaProcesamientoController::class)->only(['index', 'store', 'update', 'destroy']);

    // Modulo 6 - Inventario (RF13)
    Route::apiResource('inventario', InventarioController::class)->only(['index', 'update']);

    // Administrador (RF23 exportacion, RF24-25 usuarios/roles, RF27 auditoria parcial)
    Route::get('/administrador/usuarios', [AdministradorController::class, 'usuarios']);
    Route::post('/administrador/usuarios', [AdministradorController::class, 'crearUsuario']);
    Route::put('/administrador/usuarios/{id}/aprobar', [AdministradorController::class, 'aprobarUsuario']);
    Route::put('/administrador/usuarios/{id}/rechazar', [AdministradorController::class, 'rechazarUsuario']);
    Route::put('/administrador/usuarios/{id}/desactivar', [AdministradorController::class, 'desactivarUsuario']);
    Route::put('/administrador/usuarios/{id}/reactivar', [AdministradorController::class, 'reactivarUsuario']);
    Route::get('/administrador/reportes', [AdministradorController::class, 'reportes']);
});
