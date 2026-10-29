<?php

use App\Http\Controllers\Api\AdministradorController;
use App\Http\Controllers\Api\AlertaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogoController;
use App\Http\Controllers\Api\ClimaController;
use App\Http\Controllers\Api\ConfiguracionController;
use App\Http\Controllers\Api\ControlCalidadController;
use App\Http\Controllers\Api\CosechaController;
use App\Http\Controllers\Api\DiagnosticoController;
use App\Http\Controllers\Api\EtapaProcesamientoController;
use App\Http\Controllers\Api\InventarioController;
use App\Http\Controllers\Api\ObservacionController;
use App\Http\Controllers\Api\ParcelaController;
use App\Http\Controllers\Api\ProductorController;
use App\Http\Controllers\Api\RecomendacionController;
use App\Http\Controllers\Api\TrazabilidadController;
use App\Http\Controllers\Api\VerificacionController;
use Illuminate\Support\Facades\Route;

// throttle:6,1 = maximo 6 intentos por minuto por IP, contra fuerza bruta
// en el login y spam de registros. Laravel responde 429 al superarlo.
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

// Modulo 7 - Exportacion (RF18-22): pagina publica del Comprador, sin login.
Route::get('/trazabilidad/{codigo}', [TrazabilidadController::class, 'porCodigo']);
Route::post('/trazabilidad/{codigo}/reclamos', [TrazabilidadController::class, 'registrarReclamo']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [AuthController::class, 'actualizarCuenta']);
    Route::put('/me/preferencias', [AuthController::class, 'actualizarPreferencias']);
    Route::get('/configuracion', [ConfiguracionController::class, 'mostrar']);
    Route::put('/configuracion', [ConfiguracionController::class, 'actualizar']);
    Route::get('/catalogos/variedades-cafe', [CatalogoController::class, 'variedadesCafe']);
    Route::post('/administrador/catalogos/variedades-cafe', [CatalogoController::class, 'crearVariedadCafe']);
    Route::put('/administrador/catalogos/variedades-cafe/{id}', [CatalogoController::class, 'actualizarVariedadCafe']);
    Route::put('/me/password', [AuthController::class, 'cambiarPassword']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Modulo 1 - Produccion (RF01-03)
    Route::get('/productor/perfil', [ProductorController::class, 'show']);
    Route::put('/productor/perfil', [ProductorController::class, 'update']);

    Route::apiResource('parcelas', ParcelaController::class)->except(['show']);
    Route::apiResource('cosechas', CosechaController::class)->except(['show']);

    // Modulo 2 - Monitoreo (RF04-05)
    Route::get('/parcelas/{id}/clima', [ClimaController::class, 'porParcela']);
    Route::get('/parcelas/{id}/recomendaciones', [RecomendacionController::class, 'porParcela']);
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
    Route::put('/inventario/{id}/limites', [InventarioController::class, 'definirLimites']);
    Route::post('/inventario/{id}/salida', [InventarioController::class, 'registrarSalida']);

    // Modulo 6 - Verificacion (RF14-17)
    Route::post('/cosechas/{id}/solicitar-verificacion', [VerificacionController::class, 'solicitar']);
    Route::get('/verificador/solicitudes', [VerificacionController::class, 'index']);
    Route::get('/verificador/solicitudes/{id}', [VerificacionController::class, 'detalle']);
    Route::put('/verificador/solicitudes/{id}', [VerificacionController::class, 'resolver']);
    Route::get('/verificador/mi-historial', [VerificacionController::class, 'miHistorial']);
    Route::get('/productor/historial-verificaciones', [VerificacionController::class, 'historialProductor']);
    Route::put('/verificador/parcelas/{id}/parametros-suelo', [VerificacionController::class, 'definirParametrosSuelo']);

    // Administrador (RF23 exportacion, RF24-25 usuarios/roles, RF27 auditoria parcial)
    Route::get('/administrador/usuarios', [AdministradorController::class, 'usuarios']);
    Route::post('/administrador/usuarios', [AdministradorController::class, 'crearUsuario']);
    Route::put('/administrador/usuarios/{id}/aprobar', [AdministradorController::class, 'aprobarUsuario']);
    Route::put('/administrador/usuarios/{id}/rechazar', [AdministradorController::class, 'rechazarUsuario']);
    Route::put('/administrador/usuarios/{id}/desactivar', [AdministradorController::class, 'desactivarUsuario']);
    Route::put('/administrador/usuarios/{id}/reactivar', [AdministradorController::class, 'reactivarUsuario']);
    Route::get('/administrador/reportes', [AdministradorController::class, 'reportes']);
    Route::get('/administrador/auditoria', [AdministradorController::class, 'auditoria']);
    Route::get('/administrador/lotes-para-exportar', [AdministradorController::class, 'lotesParaExportar']);
    Route::put('/administrador/lotes/{id}/autorizar-exportacion', [AdministradorController::class, 'autorizarExportacion']);
    Route::get('/administrador/lotes-exportados', [AdministradorController::class, 'lotesExportados']);
    Route::post('/administrador/lotes/{id}/envio', [AdministradorController::class, 'agregarEstadoEnvio']);
    Route::get('/administrador/reclamos', [AdministradorController::class, 'reclamos']);
    Route::put('/administrador/reclamos/{id}/atender', [AdministradorController::class, 'atenderReclamo']);
    Route::get('/administrador/lotes/buscar', [AdministradorController::class, 'buscarLotes']);
});
