<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResuelveProductorAutenticado;
use App\Http\Controllers\Controller;
use App\Models\Parcela;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class ParcelaController extends Controller
{
    use ResuelveProductorAutenticado;

    /**
     * RF02: listar las parcelas del productor autenticado.
     */
    #[OA\Get(
        path: '/parcelas',
        summary: 'Lista las parcelas del productor autenticado',
        tags: ['Producción'],
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Listado de parcelas propias')]
    )]
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
    #[OA\Post(
        path: '/parcelas',
        summary: 'Registra una parcela nueva',
        tags: ['Producción'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['nombre_parcela'],
            properties: [
                new OA\Property(property: 'nombre_parcela', type: 'string'),
                new OA\Property(property: 'ubicacion', type: 'string', nullable: true, example: '-16.1, -67.5'),
                new OA\Property(property: 'tipo_suelo', type: 'string', nullable: true),
                new OA\Property(property: 'tamano_terreno', type: 'number', nullable: true, description: 'Hectáreas'),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Parcela creada'), new OA\Response(response: 422, description: 'Datos inválidos')]
    )]
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
    #[OA\Put(
        path: '/parcelas/{id}',
        summary: 'Edita una parcela propia',
        tags: ['Producción'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Parcela actualizada'), new OA\Response(response: 404, description: 'No encontrada o no es propia')]
    )]
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
    #[OA\Delete(
        path: '/parcelas/{id}',
        summary: 'Elimina una parcela propia (solo si no tiene cosechas)',
        tags: ['Producción'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Parcela eliminada'),
            new OA\Response(response: 404, description: 'No encontrada o no es propia'),
            new OA\Response(response: 422, description: 'Tiene cosechas registradas'),
        ]
    )]
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
