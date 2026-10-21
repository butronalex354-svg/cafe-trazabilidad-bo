<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConfiguracionController extends Controller
{
    /**
     * Cualquier usuario autenticado puede leer la configuracion (para
     * pintar el color de marca correcto apenas entra al sistema).
     */
    public function mostrar()
    {
        return response()->json(Configuracion::actual());
    }

    /**
     * Solo el Administrador puede cambiar el color del sistema, ya que
     * afecta la apariencia de todos los usuarios.
     */
    public function actualizar(Request $request)
    {
        if ($request->user()->rol !== 'administrador') {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'color_primario' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $configuracion = Configuracion::actual();
        $configuracion->update(['color_primario' => $request->input('color_primario')]);

        return response()->json($configuracion);
    }
}
