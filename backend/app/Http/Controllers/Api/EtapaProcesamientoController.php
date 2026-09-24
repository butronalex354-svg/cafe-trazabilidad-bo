<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResuelveProductorAutenticado;
use App\Http\Controllers\Controller;
use App\Models\Cosecha;
use App\Models\EtapaProcesamiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EtapaProcesamientoController extends Controller
{
    use ResuelveProductorAutenticado;

    private function reglasValidacion(): array
    {
        return [
            'cosecha_id' => 'required|integer',
            'etapa' => 'required|in:' . implode(',', EtapaProcesamiento::ORDEN_ETAPAS),
            'fecha' => 'required|date_format:Y-m-d|after_or_equal:2000-01-01|before_or_equal:2100-12-31',
            'responsable' => 'required|string|max:150',
            'observaciones' => 'nullable|string|max:1000',
        ];
    }

    /**
     * RF12: linea de tiempo de las etapas de un lote, en orden cronologico
     * de proceso (no necesariamente el orden en que se registraron).
     * Filtro obligatorio en la practica por lote: ?cosecha_id=5
     */
    public function index(Request $request)
    {
        $productor = $this->productorAutenticado($request);

        $etapas = EtapaProcesamiento::whereHas('cosecha.parcela', function ($q) use ($productor) {
                $q->where('productor_id', $productor->id);
            })
            ->when($request->filled('cosecha_id'), function ($q) use ($request) {
                $q->where('cosecha_id', $request->integer('cosecha_id'));
            })
            ->with('cosecha:id,parcela_id,codigo_trazabilidad,variedad_cafe')
            ->get()
            ->sortBy(function ($etapa) {
                return array_search($etapa->etapa, EtapaProcesamiento::ORDEN_ETAPAS);
            })
            ->values();

        return response()->json($etapas);
    }

    /**
     * RF11: registra una etapa de procesamiento (despulpado, fermentacion,
     * lavado, secado, tostado, envasado o embalaje) para un lote propio.
     */
    public function store(Request $request)
    {
        $productor = $this->productorAutenticado($request);

        $validator = Validator::make($request->all(), $this->reglasValidacion());

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $cosecha = Cosecha::whereHas('parcela', function ($q) use ($productor) {
                $q->where('productor_id', $productor->id);
            })
            ->find($request->integer('cosecha_id'));

        if (! $cosecha) {
            return response()->json(['message' => 'El lote indicado no existe o no te pertenece.'], 422);
        }

        $etapa = EtapaProcesamiento::create($request->only([
            'cosecha_id', 'etapa', 'fecha', 'responsable', 'observaciones',
        ]));

        return response()->json($etapa, 201);
    }

    /**
     * Editar una etapa propia.
     */
    public function update(Request $request, int $id)
    {
        $productor = $this->productorAutenticado($request);

        $etapa = $this->etapaPropia($productor->id, $id);

        if (! $etapa) {
            return response()->json(['message' => 'Etapa no encontrada.'], 404);
        }

        $validator = Validator::make($request->all(), $this->reglasValidacion());

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $etapa->update($request->only([
            'cosecha_id', 'etapa', 'fecha', 'responsable', 'observaciones',
        ]));

        return response()->json($etapa);
    }

    /**
     * Eliminar una etapa propia.
     */
    public function destroy(Request $request, int $id)
    {
        $productor = $this->productorAutenticado($request);

        $etapa = $this->etapaPropia($productor->id, $id);

        if (! $etapa) {
            return response()->json(['message' => 'Etapa no encontrada.'], 404);
        }

        $etapa->delete();

        return response()->json(['message' => 'Etapa eliminada.']);
    }

    private function etapaPropia(int $productorId, int $id)
    {
        return EtapaProcesamiento::whereHas('cosecha.parcela', function ($q) use ($productorId) {
                $q->where('productor_id', $productorId);
            })
            ->find($id);
    }
}
