<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'API Trazabilidad Inteligente del Café Boliviano',
    description: 'Sistema de trazabilidad por lote: producción, monitoreo, procesamiento, calidad (IA), '
        . 'inventario, verificación, exportación y consulta pública vía código QR.'
)]
#[OA\Server(url: 'http://127.0.0.1:8000/api', description: 'Entorno local de desarrollo')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    description: 'Token de Laravel Sanctum. Se obtiene en POST /login y se envía como "Authorization: Bearer {token}".'
)]
#[OA\Tag(name: 'Autenticación', description: 'Registro, login y gestión de la cuenta propia')]
#[OA\Tag(name: 'Trazabilidad pública', description: 'Consulta sin login vía código del lote (acceso desde el QR)')]
#[OA\Tag(name: 'Producción', description: 'Parcelas y cosechas (lotes) del productor')]
#[OA\Tag(name: 'Monitoreo', description: 'Clima y observaciones del cultivo')]
#[OA\Tag(name: 'Calidad', description: 'Diagnóstico por IA, alertas y control de calidad')]
#[OA\Tag(name: 'Procesamiento', description: 'Etapas post-cosecha del lote')]
#[OA\Tag(name: 'Inventario', description: 'Stock disponible por lote')]
#[OA\Tag(name: 'Verificación', description: 'Revisión técnica de lotes por el Verificador')]
#[OA\Tag(name: 'Administrador', description: 'Usuarios, exportación, reclamos, auditoría y reportes')]
class OpenApiSpec
{
    //
}
