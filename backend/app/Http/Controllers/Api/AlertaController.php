<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResuelveProductorAutenticado;
use App\Http\Controllers\Controller;
use App\Models\Alerta;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AlertaController extends Controller
{
    use ResuelveProductorAutenticado;

    /**
     * RF09: lista las alertas del productor autenticado (las mas nuevas primero).
     */
    #[OA\Get(
        path: '/alertas',
        summary: 'Lista las alertas del productor autenticado',
        description: 'Plaga/enfermedad, stock bajo o suelo fuera de rango. Filtra según las preferencias de notificación del usuario.',
        tags: ['Calidad'],
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Listado de alertas')]
    )]
    public function index(Request $request)
    {
        $productor = $this->productorAutenticado($request);

        $usuario = $request->user();

        $alertas = Alerta::where('productor_id', $productor->id)
            ->when(! $usuario->notificar_plagas, fn ($q) => $q->where('tipo', '!=', 'plaga'))
            ->when(! $usuario->notificar_stock, fn ($q) => $q->where('tipo', '!=', 'stock'))
            ->with(['diagnostico.parcela:id,nombre_parcela', 'inventario.cosecha:id,parcela_id,codigo_trazabilidad', 'parcela:id,nombre_parcela'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        return response()->json($alertas);
    }

    /**
     * Marca una alerta propia como leida.
     */
    #[OA\Put(
        path: '/alertas/{id}/leida',
        summary: 'Marca una alerta propia como leída',
        tags: ['Calidad'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Alerta actualizada'), new OA\Response(response: 404, description: 'No encontrada o no es propia')]
    )]
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
    #[OA\Put(
        path: '/alertas/{id}/resuelta',
        summary: 'Marca una alerta propia como resuelta (ya se atendió la planta)',
        tags: ['Calidad'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Alerta actualizada'), new OA\Response(response: 404, description: 'No encontrada o no es propia')]
    )]
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
