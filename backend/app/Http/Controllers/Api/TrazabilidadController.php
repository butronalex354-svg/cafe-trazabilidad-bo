<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cosecha;
use App\Models\Reclamo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class TrazabilidadController extends Controller
{
    /**
     * RF19-20: pagina publica (sin login) que cualquiera puede ver al
     * escanear el QR de un lote. Muestra origen, procesamiento, calidad y
     * el contacto de WhatsApp del productor.
     */
    #[OA\Get(
        path: '/trazabilidad/{codigo}',
        summary: 'Historial completo de un lote (público, sin login)',
        description: 'A esta URL apunta el QR impreso/adjunto a cada lote exportado.',
        tags: ['Trazabilidad pública'],
        parameters: [new OA\Parameter(name: 'codigo', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'LOTE-20261022-AB12')],
        responses: [
            new OA\Response(response: 200, description: 'Origen, procesamiento, calidad, verificación e inventario del lote'),
            new OA\Response(response: 404, description: 'No existe un lote con ese código'),
        ]
    )]
    public function porCodigo(string $codigo)
    {
        $cosecha = Cosecha::with([
                'parcela.productor',
                'etapasProcesamiento',
                'controlesCalidad',
                'verificacion',
                'inventario',
                'envios' => fn ($q) => $q->orderBy('fecha')->orderBy('id'),
            ])
            ->where('codigo_trazabilidad', $codigo)
            ->first();

        if (! $cosecha) {
            return response()->json(['message' => 'No se encontró ningún lote con ese código.'], 404);
        }

        return response()->json($cosecha);
    }

    /**
     * RF22: el comprador registra un reclamo sobre el lote indicando su
     * codigo de trazabilidad. No requiere cuenta.
     */
    #[OA\Post(
        path: '/trazabilidad/{codigo}/reclamos',
        summary: 'Registra un reclamo sobre un lote (público, sin login)',
        tags: ['Trazabilidad pública'],
        parameters: [new OA\Parameter(name: 'codigo', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['mensaje'],
            properties: [
                new OA\Property(property: 'nombre_comprador', type: 'string', nullable: true),
                new OA\Property(property: 'contacto_comprador', type: 'string', nullable: true),
                new OA\Property(property: 'mensaje', type: 'string', example: 'El lote llegó con menos peso del indicado.'),
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: 'Reclamo registrado'),
            new OA\Response(response: 404, description: 'No existe un lote con ese código'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function registrarReclamo(Request $request, string $codigo)
    {
        $cosecha = Cosecha::where('codigo_trazabilidad', $codigo)->first();

        if (! $cosecha) {
            return response()->json(['message' => 'No se encontró ningún lote con ese código.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre_comprador' => 'nullable|string|max:150',
            'contacto_comprador' => 'nullable|string|max:150',
            'mensaje' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $reclamo = Reclamo::create([
            'cosecha_id' => $cosecha->id,
            'nombre_comprador' => $request->nombre_comprador,
            'contacto_comprador' => $request->contacto_comprador,
            'mensaje' => $request->mensaje,
            'estado' => 'pendiente',
            'created_at' => now(),
        ]);

        return response()->json(['message' => 'Reclamo registrado. El administrador lo revisará pronto.', 'reclamo' => $reclamo], 201);
    }
}
