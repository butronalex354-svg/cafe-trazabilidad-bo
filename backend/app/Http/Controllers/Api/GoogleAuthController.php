<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use OpenApi\Attributes as OA;

class GoogleAuthController extends Controller
{
    /**
     * Manda al usuario a la pantalla de consentimiento de Google. Es una
     * navegacion de pagina completa (no una llamada axios/JSON), por eso
     * el boton del frontend hace window.location = esta URL en vez de un
     * fetch/axios.
     */
    #[OA\Get(
        path: '/auth/google/redirect',
        summary: 'Redirige a la pantalla de login de Google',
        description: 'Navegación de página completa (no JSON): el frontend hace window.location a esta URL.',
        tags: ['Autenticación'],
        responses: [new OA\Response(response: 302, description: 'Redirección a accounts.google.com')]
    )]
    public function redirect()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Google vuelve aca despues de que el usuario acepta. Busca la cuenta
     * por google_id; si no existe, busca por email (para vincular una
     * cuenta ya registrada con email+password); si tampoco existe, crea un
     * Productor nuevo -- igual que el registro normal, queda "pendiente"
     * hasta que el Administrador lo apruebe (RF25), para no saltarse ese
     * control solo porque entro por Google.
     *
     * Termina con un redirect de vuelta al frontend (SPA), nunca con JSON:
     * es la misma navegacion de pagina completa que empezo en redirect().
     * El token (o el motivo de error) va en la URL para que el frontend lo
     * recoja.
     */
    #[OA\Get(
        path: '/auth/google/callback',
        summary: 'Callback de Google OAuth (uso interno, no se llama a mano)',
        tags: ['Autenticación'],
        responses: [new OA\Response(response: 302, description: 'Redirección de vuelta al frontend con el token o un error')]
    )]
    public function callback()
    {
        $frontend = rtrim(config('services.google.frontend_url'), '/');

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            Log::warning('Fallo el login con Google: ' . $e->getMessage());

            return redirect("{$frontend}/#/login?google_error=fallo");
        }

        $usuario = User::where('google_id', $googleUser->getId())->first();

        if (! $usuario) {
            $usuario = User::where('email', $googleUser->getEmail())->first();

            if ($usuario) {
                // Cuenta que ya existia con email+password: se vincula, sin
                // tocar su estado actual (si ya estaba aprobada, sigue
                // aprobada; si estaba pendiente/rechazada, sigue igual).
                $usuario->update(['google_id' => $googleUser->getId()]);
            }
        }

        if (! $usuario) {
            // Cuenta nueva: mismo flujo que el registro normal (RF25), solo
            // que los datos personales (CI, telefono, direccion) quedan
            // vacios -- el productor los completa despues en su perfil.
            $usuario = User::create([
                'nombre' => $googleUser->getName() ?: $googleUser->getEmail(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'rol' => 'productor',
                'estado' => 'pendiente',
            ]);

            Productor::create([
                'usuario_id' => $usuario->id,
                'nombre_completo' => $usuario->nombre,
            ]);
        }

        if ($usuario->estado !== 'aprobado') {
            return redirect("{$frontend}/#/login?google_error={$usuario->estado}");
        }

        $token = $usuario->createToken('auth_token')->plainTextToken;

        return redirect("{$frontend}/#/auth/google/callback?token={$token}");
    }
}
