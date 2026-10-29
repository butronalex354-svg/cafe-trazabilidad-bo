<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResuelveProductorAutenticado;
use App\Http\Controllers\Controller;
use App\Models\ControlCalidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

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
    #[OA\Get(
        path: '/control-calidad',
        summary: 'Lista los controles de calidad del productor autenticado',
        tags: ['Calidad'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'cosecha_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Listado de controles de calidad')]
    )]
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
    #[OA\Post(
        path: '/control-calidad',
        summary: 'Registra un control de calidad para un lote propio',
        tags: ['Calidad'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['cosecha_id', 'fecha'],
            properties: [
                new OA\Property(property: 'cosecha_id', type: 'integer'),
                new OA\Property(property: 'fecha', type: 'string', format: 'date'),
                new OA\Property(property: 'humedad', type: 'number', nullable: true),
                new OA\Property(property: 'defectos', type: 'integer', nullable: true),
                new OA\Property(property: 'clasificacion', type: 'string', enum: ['primera', 'segunda', 'tercera', 'exportacion'], nullable: true),
                new OA\Property(property: 'observaciones', type: 'string', nullable: true),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Control registrado'), new OA\Response(response: 422, description: 'Datos inválidos o lote ajeno')]
    )]
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
    #[OA\Put(
        path: '/control-calidad/{id}',
        summary: 'Edita un control de calidad propio',
        tags: ['Calidad'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Control actualizado'), new OA\Response(response: 404, description: 'No encontrado o no es propio')]
    )]
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
    #[OA\Delete(
        path: '/control-calidad/{id}',
        summary: 'Elimina un control de calidad propio',
        tags: ['Calidad'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Control eliminado'), new OA\Response(response: 404, description: 'No encontrado o no es propio')]
    )]
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
