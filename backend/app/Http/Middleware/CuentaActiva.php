<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Si el Administrador desactiva (o rechaza) una cuenta, su sesion se corta en
 * la siguiente peticion, sin esperar a que el token venza (7 dias). Se borran
 * sus tokens y se responde 401 para que el frontend lo mande al login.
 */
class CuentaActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && $usuario->estado !== 'aprobado') {
            $usuario->tokens()->delete();

            return response()->json([
                'message' => 'Tu cuenta ya no está activa. Comunícate con el administrador.',
            ], 401);
        }

        return $next($request);
    }
}
