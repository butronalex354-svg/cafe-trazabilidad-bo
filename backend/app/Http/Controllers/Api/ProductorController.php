<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResuelveProductorAutenticado;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class ProductorController extends Controller
{
    use ResuelveProductorAutenticado;

    /**
     * RF01: perfil del productor autenticado (datos personales).
     */
    #[OA\Get(
        path: '/productor/perfil',
        summary: 'Perfil del productor autenticado',
        tags: ['Producción'],
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Datos del productor')]
    )]
    public function show(Request $request)
    {
        return response()->json($this->productorAutenticado($request));
    }

    /**
     * RF01: editar los datos personales del productor autenticado.
     */
    #[OA\Put(
        path: '/productor/perfil',
        summary: 'Edita el perfil del productor autenticado',
        tags: ['Producción'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['nombre_completo', 'ci', 'telefono', 'direccion'],
            properties: [
                new OA\Property(property: 'nombre_completo', type: 'string'),
                new OA\Property(property: 'ci', type: 'string'),
                new OA\Property(property: 'telefono', type: 'string'),
                new OA\Property(property: 'whatsapp', type: 'string', nullable: true),
                new OA\Property(property: 'direccion', type: 'string'),
            ]
        )),
        responses: [new OA\Response(response: 200, description: 'Perfil actualizado'), new OA\Response(response: 422, description: 'Datos inválidos')]
    )]
    public function update(Request $request)
    {
        $productor = $this->productorAutenticado($request);

        $validator = Validator::make($request->all(), [
            'nombre_completo' => 'required|string|max:150',
            'ci' => 'required|string|max:20',
            'telefono' => 'required|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'direccion' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $productor->update([
            'nombre_completo' => $request->nombre_completo,
            'ci' => $request->ci,
            'telefono' => $request->telefono,
            'whatsapp' => $request->whatsapp ?: $request->telefono,
            'direccion' => $request->direccion,
        ]);

        return response()->json($productor->fresh());
    }
}
