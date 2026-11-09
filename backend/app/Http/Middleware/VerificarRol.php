<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guardia central de roles: se pone en las rutas (routes/api.php) como
 * `rol:productor`, `rol:verificador` o `rol:administrador`, asi ninguna ruta
 * nueva queda sin proteccion por olvidar el control dentro del controlador.
 * Los controladores mantienen su propio control como segunda barrera.
 */
class VerificarRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user() || ! in_array($request->user()->rol, $roles, true)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        return $next($request);
    }
}
