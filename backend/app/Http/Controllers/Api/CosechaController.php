<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResuelveProductorAutenticado;
use App\Http\Controllers\Controller;
use App\Models\Cosecha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CosechaController extends Controller
{
    use ResuelveProductorAutenticado;

    /**
     * RF03: listar las cosechas (lotes) del productor autenticado.
     * Filtro opcional por parcela: ?parcela_id=3
     */
    public function index(Request $request)
    {
        $productor = $this->productorAutenticado($request);

        $cosechas = Cosecha::whereHas('parcela', function ($q) use ($productor) {
                $q->where('productor_id', $productor->id);
            })
            ->when($request->filled('parcela_id'), function ($q) use ($request) {
                $q->where('parcela_id', $request->integer('parcela_id'));
            })
            ->with('parcela:id,nombre_parcela')
            ->orderByDesc('id')
            ->get();

        return response()->json($cosechas);
    }

    /**
     * RF03: registrar una cosecha (fecha, cantidad, variedad, parcela de origen).
     * El codigo de trazabilidad unico del lote se genera automaticamente.
     */
    public function store(Request $request)
    {
        $productor = $this->productorAutenticado($request);

        $validator = Validator::make($request->all(), [
            'parcela_id' => 'required|integer',
            // La regla "date" sola no basta: Carbon reinterpreta un año mal
            // escrito como "62026" (lo lee como 2006) en vez de rechazarlo, pero
            // el texto original SIN corregir es el que se manda a MySQL, que si
            // lo rechaza. "date_format:Y-m-d" exige el formato exacto de una vez.
            'fecha' => 'required|date_format:Y-m-d|after_or_equal:2000-01-01|before_or_equal:2100-12-31',
            'cantidad' => 'required|numeric|min:0.01',
            'variedad_cafe' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $parcela = $productor->parcelas()->find($request->parcela_id);

        if (! $parcela) {
            return response()->json([
                'message' => 'La parcela indicada no existe o no te pertenece.',
            ], 422);
        }

        $cosecha = $parcela->cosechas()->create($validator->safe()->except('parcela_id') + [
            'parcela_id' => $parcela->id,
        ]);

        return response()->json($cosecha, 201);
    }

    /**
     * Editar una cosecha propia (el codigo de trazabilidad no se puede editar).
     */
    public function update(Request $request, int $id)
    {
        $productor = $this->productorAutenticado($request);
        $cosecha = $this->cosechaPropia($productor->id, $id);

        if (! $cosecha) {
            return response()->json(['message' => 'Cosecha no encontrada.'], 404);
        }

        $validator = Validator::make($request->all(), [
            // La regla "date" sola no basta: Carbon reinterpreta un año mal
            // escrito como "62026" (lo lee como 2006) en vez de rechazarlo, pero
            // el texto original SIN corregir es el que se manda a MySQL, que si
            // lo rechaza. "date_format:Y-m-d" exige el formato exacto de una vez.
            'fecha' => 'required|date_format:Y-m-d|after_or_equal:2000-01-01|before_or_equal:2100-12-31',
            'cantidad' => 'required|numeric|min:0.01',
            'variedad_cafe' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $cosecha->update($validator->validated());

        return response()->json($cosecha->fresh());
    }

    public function destroy(Request $request, int $id)
    {
        $productor = $this->productorAutenticado($request);
        $cosecha = $this->cosechaPropia($productor->id, $id);

        if (! $cosecha) {
            return response()->json(['message' => 'Cosecha no encontrada.'], 404);
        }

        $cosecha->delete();

        return response()->json(['message' => 'Cosecha eliminada.']);
    }

    private function cosechaPropia(int $productorId, int $cosechaId): ?Cosecha
    {
        return Cosecha::whereHas('parcela', function ($q) use ($productorId) {
                $q->where('productor_id', $productorId);
            })
            ->find($cosechaId);
    }
}
