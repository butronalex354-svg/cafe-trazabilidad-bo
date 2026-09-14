<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClimaController;
use App\Http\Controllers\Api\CosechaController;
use App\Http\Controllers\Api\ObservacionController;
use App\Http\Controllers\Api\ParcelaController;
use App\Http\Controllers\Api\ProductorController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Modulo 1 - Produccion (RF01-03)
    Route::get('/productor/perfil', [ProductorController::class, 'show']);
    Route::put('/productor/perfil', [ProductorController::class, 'update']);

    Route::apiResource('parcelas', ParcelaController::class)->except(['show']);
    Route::apiResource('cosechas', CosechaController::class)->except(['show']);

    // Modulo 2 - Monitoreo (RF04-05)
    Route::get('/parcelas/{id}/clima', [ClimaController::class, 'porParcela']);
    Route::apiResource('observaciones', ObservacionController::class)->only(['index', 'store', 'destroy']);
});
