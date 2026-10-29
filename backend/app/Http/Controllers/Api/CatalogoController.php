<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VariedadCafe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class CatalogoController extends Controller
{
    /**
     * Cualquier usuario autenticado puede leer el catalogo (lo necesita el
     * Productor para elegir la variedad al registrar una cosecha).
     */
    #[OA\Get(
        path: '/catalogos/variedades-cafe',
        summary: 'Catálogo de variedades de café',
        tags: ['Producción'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'todas', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'), description: 'true incluye inactivas (solo útil para el Administrador)')],
        responses: [new OA\Response(response: 200, description: 'Listado de variedades')]
    )]
    public function variedadesCafe(Request $request)
    {
        $query = VariedadCafe::orderBy('nombre');

        // El selector de Produccion solo quiere las activas; el Administrador,
        // en su pantalla de catalogos, quiere ver todas (incluidas inactivas).
        if (! $request->boolean('todas')) {
            $query->where('activo', true);
        }

        return response()->json($query->get());
    }

    /**
     * Crea una variedad nueva en el catalogo (Administrador).
     */
    #[OA\Post(
        path: '/administrador/catalogos/variedades-cafe',
        summary: 'Crea una variedad de café nueva (rol Administrador)',
        tags: ['Administrador'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['nombre'],
            properties: [new OA\Property(property: 'nombre', type: 'string')]
        )),
        responses: [new OA\Response(response: 201, description: 'Variedad creada'), new OA\Response(response: 403, description: 'No es Administrador')]
    )]
    public function crearVariedadCafe(Request $request)
    {
        $this->verificarAdministrador($request);

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100|unique:variedad_cafe,nombre',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $variedad = VariedadCafe::create(['nombre' => $request->nombre, 'activo' => true]);

        return response()->json($variedad, 201);
    }

    /**
     * Activa/desactiva o renombra una variedad existente (Administrador).
     * No se borra en duro para no afectar lotes ya registrados con ese
     * nombre; desactivarla solo la saca de las opciones para lotes nuevos.
     */
    #[OA\Put(
        path: '/administrador/catalogos/variedades-cafe/{id}',
        summary: 'Renombra o activa/desactiva una variedad existente (rol Administrador)',
        tags: ['Administrador'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['nombre', 'activo'],
            properties: [
                new OA\Property(property: 'nombre', type: 'string'),
                new OA\Property(property: 'activo', type: 'boolean'),
            ]
        )),
        responses: [new OA\Response(response: 200, description: 'Variedad actualizada'), new OA\Response(response: 403, description: 'No es Administrador')]
    )]
    public function actualizarVariedadCafe(Request $request, int $id)
    {
        $this->verificarAdministrador($request);

        $variedad = VariedadCafe::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100|unique:variedad_cafe,nombre,' . $id,
            'activo' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $variedad->update(['nombre' => $request->nombre, 'activo' => $request->boolean('activo')]);

        return response()->json($variedad);
    }

    private function verificarAdministrador(Request $request)
    {
        if ($request->user()->rol !== 'administrador') {
            abort(response()->json(['message' => 'No autorizado.'], 403));
        }
    }
}
