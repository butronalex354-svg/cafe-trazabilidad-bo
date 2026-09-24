<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResuelveProductorAutenticado;
use App\Http\Controllers\Controller;
use App\Models\Inventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InventarioController extends Controller
{
    use ResuelveProductorAutenticado;

    /**
     * RF13: vista de inventario por lote y por estado del productor autenticado.
     * Filtros opcionales: ?parcela_id=3, ?estado=en_proceso
     */
    public function index(Request $request)
    {
        $productor = $this->productorAutenticado($request);

        $inventario = Inventario::whereHas('cosecha.parcela', function ($q) use ($productor) {
                $q->where('productor_id', $productor->id);
            })
            ->when($request->filled('parcela_id'), function ($q) use ($request) {
                $q->whereHas('cosecha', function ($q2) use ($request) {
                    $q2->where('parcela_id', $request->integer('parcela_id'));
                });
            })
            ->when($request->filled('estado'), function ($q) use ($request) {
                $q->where('estado', $request->input('estado'));
            })
            ->with('cosecha:id,parcela_id,codigo_trazabilidad,variedad_cafe,cantidad', 'cosecha.parcela:id,nombre_parcela')
            ->orderByDesc('fecha_actualizacion')
            ->orderByDesc('id')
            ->get();

        return response()->json($inventario);
    }

    /**
     * RF13: cambia el estado de un lote en el inventario (en_proceso -> terminado -> exportado).
     * Cuando el estado pasa a "exportado", la cantidad disponible se actualiza
     * automaticamente a 0 (el lote ya salio del stock).
     */
    public function update(Request $request, int $id)
    {
        $productor = $this->productorAutenticado($request);

        $inventario = Inventario::whereHas('cosecha.parcela', function ($q) use ($productor) {
                $q->where('productor_id', $productor->id);
            })
            ->find($id);

        if (! $inventario) {
            return response()->json(['message' => 'Registro de inventario no encontrado.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'estado' => 'required|in:en_proceso,terminado,exportado',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // $request->string() devuelve un objeto Stringable, no un string plano,
        // asi que la comparacion "===" de abajo nunca daba true — se usa
        // input() para tener un string real.
        $nuevoEstado = $request->input('estado');

        $inventario->update([
            'estado' => $nuevoEstado,
            // Actualizacion automatica de la cantidad disponible segun el estado (RF13):
            // exportado = ya no queda nada en stock; en_proceso/terminado = se
            // mantiene toda la cantidad original del lote.
            'cantidad_disponible' => $nuevoEstado === 'exportado' ? 0 : $inventario->cosecha->cantidad,
            'fecha_actualizacion' => now()->toDateString(),
        ]);

        return response()->json($inventario->fresh('cosecha.parcela'));
    }
}
