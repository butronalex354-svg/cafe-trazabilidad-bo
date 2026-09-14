<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Productor;
use Illuminate\Http\Request;

/**
 * Los usuarios de prueba (o cualquier usuario con rol "productor" creado antes
 * de que existiera este modulo) pueden no tener todavia una fila en `productor`.
 * Este trait centraliza como se obtiene/crea esa fila para el usuario autenticado.
 */
trait ResuelveProductorAutenticado
{
    protected function productorAutenticado(Request $request): Productor
    {
        $usuario = $request->user();

        return $usuario->productor ?: Productor::create([
            'usuario_id' => $usuario->id,
            'nombre_completo' => $usuario->nombre,
        ]);
    }
}
