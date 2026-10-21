<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VariedadCafe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CatalogoController extends Controller
{
    /**
     * Cualquier usuario autenticado puede leer el catalogo (lo necesita el
     * Productor para elegir la variedad al registrar una cosecha).
     */
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
