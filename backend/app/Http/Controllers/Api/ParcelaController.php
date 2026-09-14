<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResuelveProductorAutenticado;
use App\Http\Controllers\Controller;
use App\Models\Parcela;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ParcelaController extends Controller
{
    use ResuelveProductorAutenticado;

    /**
     * RF02: listar las parcelas del productor autenticado.
     */
    public function index(Request $request)
    {
        $productor = $this->productorAutenticado($request);

        $parcelas = $productor->parcelas()
            ->withCount('cosechas')
            ->orderByDesc('id')
            ->get();

        return response()->json($parcelas);
    }

    /**
     * RF02: registrar una parcela nueva (nombre, ubicacion GPS y tamano de terreno).
     */
    public function store(Request $request)
    {
        $productor = $this->productorAutenticado($request);

        $validator = Validator::make($request->all(), [
            'nombre_parcela' => 'required|string|max:100',
            'ubicacion' => 'nullable|string|max:255',
            'tipo_suelo' => 'nullable|string|max:100',
            'tamano_terreno' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $parcela = $productor->parcelas()->create($validator->validated());

        return response()->json($parcela, 201);
    }

    /**
     * RF02: editar una parcela propia.
     */
    public function update(Request $request, int $id)
    {
        $productor = $this->productorAutenticado($request);
        $parcela = $productor->parcelas()->find($id);

        if (! $parcela) {
            return response()->json(['message' => 'Parcela no encontrada.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre_parcela' => 'required|string|max:100',
            'ubicacion' => 'nullable|string|max:255',
            'tipo_suelo' => 'nullable|string|max:100',
            'tamano_terreno' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $parcela->update($validator->validated());

        return response()->json($parcela->fresh());
    }

    /**
     * Eliminar una parcela propia (solo si no tiene cosechas registradas).
     */
    public function destroy(Request $request, int $id)
    {
        $productor = $this->productorAutenticado($request);
        $parcela = $productor->parcelas()->withCount('cosechas')->find($id);

        if (! $parcela) {
            return response()->json(['message' => 'Parcela no encontrada.'], 404);
        }

        if ($parcela->cosechas_count > 0) {
            return response()->json([
                'message' => 'No se puede eliminar: la parcela tiene cosechas registradas.',
            ], 422);
        }

        $parcela->delete();

        return response()->json(['message' => 'Parcela eliminada.']);
    }
}
