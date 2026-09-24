<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResuelveProductorAutenticado;
use App\Http\Controllers\Controller;
use App\Models\Alerta;
use Illuminate\Http\Request;

class AlertaController extends Controller
{
    use ResuelveProductorAutenticado;

    /**
     * RF09: lista las alertas del productor autenticado (las mas nuevas primero).
     */
    public function index(Request $request)
    {
        $productor = $this->productorAutenticado($request);

        $alertas = Alerta::where('productor_id', $productor->id)
            ->with('diagnostico.parcela:id,nombre_parcela')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        return response()->json($alertas);
    }

    /**
     * Marca una alerta propia como leida.
     */
    public function marcarLeida(Request $request, int $id)
    {
        $productor = $this->productorAutenticado($request);

        $alerta = Alerta::where('productor_id', $productor->id)->find($id);

        if (! $alerta) {
            return response()->json(['message' => 'Alerta no encontrada.'], 404);
        }

        $alerta->update(['leida' => true]);

        return response()->json($alerta);
    }

    /**
     * Marca una alerta propia como resuelta (ya se atendio la planta) —
     * distinto de "leida", que solo significa que el productor la vio.
     */
    public function marcarResuelta(Request $request, int $id)
    {
        $productor = $this->productorAutenticado($request);

        $alerta = Alerta::where('productor_id', $productor->id)->find($id);

        if (! $alerta) {
            return response()->json(['message' => 'Alerta no encontrada.'], 404);
        }

        $alerta->update(['resuelta' => true, 'leida' => true]);

        return response()->json($alerta);
    }
}
