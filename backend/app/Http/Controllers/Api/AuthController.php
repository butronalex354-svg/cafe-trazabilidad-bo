<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    /**
     * Registra un productor nuevo y devuelve un token de acceso (Sanctum).
     */
    #[OA\Post(
        path: '/register',
        summary: 'Registra una cuenta nueva de Productor',
        description: 'La cuenta queda en estado "pendiente" hasta que un Administrador la aprueba (RF25). No requiere login.',
        tags: ['Autenticación'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['nombre', 'email', 'password', 'password_confirmation', 'ci', 'telefono', 'direccion'],
            properties: [
                new OA\Property(property: 'nombre', type: 'string', example: 'Juan Mamani'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'juan@cafe.bo'),
                new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 6),
                new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
                new OA\Property(property: 'ci', type: 'string', example: '1234567 LP'),
                new OA\Property(property: 'telefono', type: 'string', example: '70000000'),
                new OA\Property(property: 'whatsapp', type: 'string', nullable: true),
                new OA\Property(property: 'direccion', type: 'string', example: 'Caranavi, La Paz'),
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: 'Cuenta creada, pendiente de aprobación'),
            new OA\Response(response: 422, description: 'Datos inválidos (ej. email ya registrado)'),
        ]
    )]
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:usuario,email',
            'password' => 'required|string|min:6|confirmed',
            // RF01: el registro de un productor exige tambien sus datos personales.
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

        $user = User::create([
            'nombre' => $request->nombre,
            'email' => $request->email,
            'password' => $request->password,
            'rol' => 'productor',
            // RF25: el productor queda pendiente hasta que el Administrador lo apruebe.
            'estado' => 'pendiente',
        ]);

        Productor::create([
            'usuario_id' => $user->id,
            'nombre_completo' => $request->nombre,
            'ci' => $request->ci,
            'telefono' => $request->telefono,
            'whatsapp' => $request->whatsapp ?: $request->telefono,
            'direccion' => $request->direccion,
        ]);

        // No se entrega token aca: la cuenta queda "pendiente" hasta que el
        // Administrador la apruebe, asi que no debe poder entrar todavia.
        return response()->json([
            'message' => 'Cuenta creada correctamente. Tu cuenta debe ser aprobada por un administrador antes de que puedas iniciar sesión.',
        ], 201);
    }

    /**
     * Inicia sesion y devuelve un token de acceso (Sanctum).
     */
    #[OA\Post(
        path: '/login',
        summary: 'Inicia sesión',
        description: 'Devuelve un token Sanctum si la cuenta existe, la contraseña es correcta y el estado es "aprobado".',
        tags: ['Autenticación'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'productor@cafe.bo'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: 'Login correcto', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'token', type: 'string'),
                    new OA\Property(property: 'user', type: 'object'),
                ]
            )),
            new OA\Response(response: 401, description: 'Credenciales incorrectas'),
            new OA\Response(response: 403, description: 'Cuenta pendiente, rechazada o desactivada'),
        ]
    )]
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        if ($user->estado === 'pendiente') {
            return response()->json([
                'message' => 'Tu cuenta todavía no fue aprobada por un administrador.',
            ], 403);
        }

        if ($user->estado === 'rechazado') {
            return response()->json([
                'message' => 'Tu cuenta fue rechazada. Contacta al administrador.',
            ], 403);
        }

        if ($user->estado === 'desactivado') {
            return response()->json([
                'message' => 'Tu cuenta fue desactivada por un administrador.',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Sesion iniciada correctamente.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'email' => $user->email,
                'rol' => $user->rol,
                'notificar_plagas' => $user->notificar_plagas,
                'notificar_stock' => $user->notificar_stock,
                'telefono' => $user->telefono,
                'entidad_verificadora' => $user->entidad_verificadora,
                'acreditacion' => $user->acreditacion,
            ],
        ]);
    }

    /**
     * Devuelve el usuario autenticado actual.
     */
    #[OA\Get(
        path: '/me',
        summary: 'Datos del usuario autenticado',
        tags: ['Autenticación'],
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Usuario actual')]
    )]
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Cierra la sesion (revoca el token actual).
     */
    #[OA\Post(
        path: '/logout',
        summary: 'Cierra sesión (revoca el token actual)',
        tags: ['Autenticación'],
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Sesión cerrada')]
    )]
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesion cerrada correctamente.',
        ]);
    }

    /**
     * Actualiza el nombre/email/telefono de la cuenta autenticada (nombre
     * que se ve en el header/menu, distinto del "nombre completo" del
     * registro de Productor que se edita en Parcelas). El Verificador ademas
     * puede guardar su entidad y numero de acreditacion.
     */
    #[OA\Put(
        path: '/me',
        summary: 'Actualiza nombre/email/teléfono de la cuenta propia',
        tags: ['Autenticación'],
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Cuenta actualizada'), new OA\Response(response: 422, description: 'Datos inválidos')]
    )]
    public function actualizarCuenta(Request $request)
    {
        $usuario = $request->user();

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:usuario,email,' . $usuario->id,
            'telefono' => 'nullable|string|max:20',
            'entidad_verificadora' => 'nullable|string|max:150',
            'acreditacion' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $datos = [
            'nombre' => $request->nombre,
            'email' => $request->email,
            'telefono' => $request->input('telefono'),
        ];

        // Los campos de acreditacion solo tienen sentido para el Verificador;
        // se ignoran si los manda otro rol, para no guardar datos sin sentido.
        if ($usuario->rol === 'verificador') {
            $datos['entidad_verificadora'] = $request->input('entidad_verificadora');
            $datos['acreditacion'] = $request->input('acreditacion');
        }

        $usuario->update($datos);

        \App\Models\Auditoria::registrar($usuario->id, $usuario->id, 'editar_cuenta', "Editó sus propios datos de cuenta.");

        return response()->json(['message' => 'Cuenta actualizada.', 'user' => $usuario]);
    }

    /**
     * Preferencias de notificacion: que tipos de alerta quiere ver el
     * usuario autenticado (hoy aplica al Productor: plagas y stock bajo).
     */
    #[OA\Put(
        path: '/me/preferencias',
        summary: 'Actualiza preferencias de notificación (plagas/stock)',
        tags: ['Autenticación'],
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Preferencias actualizadas')]
    )]
    public function actualizarPreferencias(Request $request)
    {
        $usuario = $request->user();

        $validator = Validator::make($request->all(), [
            'notificar_plagas' => 'required|boolean',
            'notificar_stock' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $usuario->update([
            'notificar_plagas' => $request->boolean('notificar_plagas'),
            'notificar_stock' => $request->boolean('notificar_stock'),
        ]);

        return response()->json(['message' => 'Preferencias actualizadas.', 'user' => $usuario]);
    }

    /**
     * Cambia la contraseña de la cuenta autenticada, pidiendo la actual
     * para confirmar que es el dueño de la cuenta quien la cambia.
     */
    #[OA\Put(
        path: '/me/password',
        summary: 'Cambia la contraseña de la cuenta propia',
        tags: ['Autenticación'],
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Contraseña actualizada'), new OA\Response(response: 422, description: 'Contraseña actual incorrecta o datos inválidos')]
    )]
    public function cambiarPassword(Request $request)
    {
        $usuario = $request->user();

        $validator = Validator::make($request->all(), [
            'password_actual' => 'required|string',
            'password_nueva' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (! Hash::check($request->password_actual, $usuario->password)) {
            return response()->json(['message' => 'La contraseña actual no es correcta.'], 422);
        }

        $usuario->update(['password' => $request->password_nueva]);

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }
}
