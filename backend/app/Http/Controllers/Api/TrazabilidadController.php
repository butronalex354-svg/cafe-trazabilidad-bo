<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cosecha;
use App\Models\Reclamo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TrazabilidadController extends Controller
{
    /**
     * RF19-20: pagina publica (sin login) que cualquiera puede ver al
     * escanear el QR de un lote. Muestra origen, procesamiento, calidad y
     * el contacto de WhatsApp del productor.
     */
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
