<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResuelveProductorAutenticado;
use App\Http\Controllers\Controller;
use App\Models\ControlCalidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ControlCalidadController extends Controller
{
    use ResuelveProductorAutenticado;

    private function reglasValidacion(): array
    {
        return [
            'cosecha_id' => 'required|integer',
            'fecha' => 'required|date_format:Y-m-d|after_or_equal:2000-01-01|before_or_equal:2100-12-31',
            'humedad' => 'nullable|numeric|min:0|max:100',
            'defectos' => 'nullable|integer|min:0',
            'clasificacion' => 'nullable|in:primera,segunda,tercera,exportacion',
            'observaciones' => 'nullable|string|max:1000',
        ];
    }

    /**
     * RF10: lista los controles de calidad del productor autenticado.
     * Filtro opcional por cosecha: ?cosecha_id=5
     */
    public function index(Request $request)
    {
        $productor = $this->productorAutenticado($request);

        $controles = ControlCalidad::whereHas('cosecha.parcela', function ($q) use ($productor) {
                $q->where('productor_id', $productor->id);
            })
            ->when($request->filled('cosecha_id'), function ($q) use ($request) {
                $q->where('cosecha_id', $request->integer('cosecha_id'));
            })
            ->with('cosecha:id,parcela_id,codigo_trazabilidad,variedad_cafe')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        return response()->json($controles);
    }

    /**
     * RF10: registra un control de calidad (humedad, defectos, clasificacion) para un lote propio.
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

        $cosecha = $this->cosechaPropia($productor->id, $request->integer('cosecha_id'));

        if (! $cosecha) {
            return response()->json(['message' => 'El lote indicado no existe o no te pertenece.'], 422);
        }

        $control = ControlCalidad::create($request->only([
            'cosecha_id', 'fecha', 'humedad', 'defectos', 'clasificacion', 'observaciones',
        ]));

        return response()->json($control, 201);
    }

    /**
     * Editar un control de calidad propio.
     */
    public function update(Request $request, int $id)
    {
        $productor = $this->productorAutenticado($request);

        $control = $this->controlPropio($productor->id, $id);

        if (! $control) {
            return response()->json(['message' => 'Control de calidad no encontrado.'], 404);
        }

        $validator = Validator::make($request->all(), $this->reglasValidacion());

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $control->update($request->only([
            'cosecha_id', 'fecha', 'humedad', 'defectos', 'clasificacion', 'observaciones',
        ]));

        return response()->json($control);
    }

    /**
     * Eliminar un control de calidad propio.
     */
    public function destroy(Request $request, int $id)
    {
        $productor = $this->productorAutenticado($request);

        $control = $this->controlPropio($productor->id, $id);

        if (! $control) {
            return response()->json(['message' => 'Control de calidad no encontrado.'], 404);
        }

        $control->delete();

        return response()->json(['message' => 'Control de calidad eliminado.']);
    }

    private function cosechaPropia(int $productorId, int $cosechaId)
    {
        return \App\Models\Cosecha::whereHas('parcela', function ($q) use ($productorId) {
                $q->where('productor_id', $productorId);
            })
            ->find($cosechaId);
    }

    private function controlPropio(int $productorId, int $id)
    {
        return ControlCalidad::whereHas('cosecha.parcela', function ($q) use ($productorId) {
                $q->where('productor_id', $productorId);
            })
            ->find($id);
    }
}
