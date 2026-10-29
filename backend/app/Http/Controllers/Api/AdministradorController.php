<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alerta;
use App\Models\Auditoria;
use App\Models\Cosecha;
use App\Models\Envio;
use App\Models\Inventario;
use App\Models\Parcela;
use App\Models\Reclamo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class AdministradorController extends Controller
{
    /**
     * Corta el paso a cualquiera que no sea administrador, ya que estos
     * endpoints ven datos de TODOS los productores (no estan filtrados
     * por dueño como el resto del sistema).
     */
    private function verificarAdministrador(Request $request)
    {
        if ($request->user()->rol !== 'administrador') {
            abort(response()->json(['message' => 'No autorizado.'], 403));
        }
    }

    /**
     * Lista los usuarios del sistema (RF25), con filtro opcional por
     * rol y estado. Trae los datos de Productor cuando aplica.
     */
    #[OA\Get(
        path: '/administrador/usuarios',
        summary: 'Lista los usuarios del sistema (rol Administrador)',
        tags: ['Administrador'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'rol', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['productor', 'verificador', 'administrador'])),
            new OA\Parameter(name: 'estado', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pendiente', 'aprobado', 'rechazado', 'desactivado'])),
        ],
        responses: [new OA\Response(response: 200, description: 'Listado de usuarios'), new OA\Response(response: 403, description: 'No es Administrador')]
    )]
    public function usuarios(Request $request)
    {
        $this->verificarAdministrador($request);

        $query = User::with('productor')->orderByDesc('created_at');

        if ($request->filled('rol')) {
            $query->where('rol', $request->input('rol'));
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        return response()->json($query->get());
    }

    /**
     * Aprueba una cuenta pendiente (productor autoregistrado, RF25).
     */
    #[OA\Put(
        path: '/administrador/usuarios/{id}/aprobar',
        summary: 'Aprueba una cuenta pendiente (RF25)',
        tags: ['Administrador'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Cuenta aprobada'), new OA\Response(response: 403, description: 'No es Administrador')]
    )]
    public function aprobarUsuario(Request $request, $id)
    {
        $this->verificarAdministrador($request);

        $usuario = User::findOrFail($id);
        $usuario->estado = 'aprobado';
        $usuario->save();

        Auditoria::registrar($request->user()->id, $usuario->id, 'aprobar_usuario', "Aprobó la cuenta de {$usuario->nombre} ({$usuario->email}).");

        return response()->json(['message' => 'Cuenta aprobada.', 'usuario' => $usuario]);
    }

    /**
     * Rechaza una cuenta pendiente.
     */
    #[OA\Put(
        path: '/administrador/usuarios/{id}/rechazar',
        summary: 'Rechaza una cuenta pendiente',
        tags: ['Administrador'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Cuenta rechazada'), new OA\Response(response: 403, description: 'No es Administrador')]
    )]
    public function rechazarUsuario(Request $request, $id)
    {
        $this->verificarAdministrador($request);

        $usuario = User::findOrFail($id);
        $usuario->estado = 'rechazado';
        $usuario->save();

        Auditoria::registrar($request->user()->id, $usuario->id, 'rechazar_usuario', "Rechazó la cuenta de {$usuario->nombre} ({$usuario->email}).");

        return response()->json(['message' => 'Cuenta rechazada.', 'usuario' => $usuario]);
    }

    /**
     * Crea directamente una cuenta de Verificador o Administrador (RF25).
     * Estas no se auto-registran desde el formulario publico (ese solo crea
     * Productores), asi que el Administrador es quien las da de alta, ya
     * aprobadas (no pasan por el flujo de pendiente/aprobacion).
     */
    #[OA\Post(
        path: '/administrador/usuarios',
        summary: 'Crea directamente una cuenta de Verificador o Administrador',
        description: 'Estas cuentas no se auto-registran: se crean ya aprobadas, sin pasar por el flujo de pendiente/aprobación.',
        tags: ['Administrador'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['nombre', 'email', 'password', 'rol'],
            properties: [
                new OA\Property(property: 'nombre', type: 'string'),
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 6),
                new OA\Property(property: 'rol', type: 'string', enum: ['verificador', 'administrador']),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Cuenta creada'), new OA\Response(response: 403, description: 'No es Administrador')]
    )]
    public function crearUsuario(Request $request)
    {
        $this->verificarAdministrador($request);

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:usuario,email',
            'password' => 'required|string|min:6',
            'rol' => 'required|in:verificador,administrador',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $usuario = User::create([
            'nombre' => $request->nombre,
            'email' => $request->email,
            'password' => $request->password,
            'rol' => $request->rol,
            'estado' => 'aprobado',
        ]);

        Auditoria::registrar($request->user()->id, $usuario->id, 'crear_usuario', "Creó la cuenta de {$usuario->nombre} ({$usuario->email}) con rol {$usuario->rol}.");

        return response()->json(['message' => 'Cuenta creada correctamente.', 'usuario' => $usuario], 201);
    }

    /**
     * Desactiva una cuenta ya aprobada (ej. un verificador que dejo el
     * equipo, o un productor que incumple). Se puede reactivar despues.
     */
    #[OA\Put(
        path: '/administrador/usuarios/{id}/desactivar',
        summary: 'Desactiva una cuenta ya aprobada',
        tags: ['Administrador'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Cuenta desactivada'), new OA\Response(response: 403, description: 'No es Administrador')]
    )]
    public function desactivarUsuario(Request $request, $id)
    {
        $this->verificarAdministrador($request);

        $usuario = User::findOrFail($id);
        $usuario->estado = 'desactivado';
        $usuario->save();

        Auditoria::registrar($request->user()->id, $usuario->id, 'desactivar_usuario', "Desactivó la cuenta de {$usuario->nombre} ({$usuario->email}).");

        return response()->json(['message' => 'Cuenta desactivada.', 'usuario' => $usuario]);
    }

    /**
     * Reactiva una cuenta que estaba desactivada.
     */
    #[OA\Put(
        path: '/administrador/usuarios/{id}/reactivar',
        summary: 'Reactiva una cuenta desactivada',
        tags: ['Administrador'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Cuenta reactivada'), new OA\Response(response: 403, description: 'No es Administrador')]
    )]
    public function reactivarUsuario(Request $request, $id)
    {
        $this->verificarAdministrador($request);

        $usuario = User::findOrFail($id);
        $usuario->estado = 'aprobado';
        $usuario->save();

        Auditoria::registrar($request->user()->id, $usuario->id, 'reactivar_usuario', "Reactivó la cuenta de {$usuario->nombre} ({$usuario->email}).");

        return response()->json(['message' => 'Cuenta reactivada.', 'usuario' => $usuario]);
    }

    /**
     * Reportes generales para el panel del Administrador (supervision
     * general del sistema, segun la tabla de actores del documento de
     * requerimientos).
     */
    #[OA\Get(
        path: '/administrador/reportes',
        summary: 'Reportes generales del sistema',
        tags: ['Administrador'],
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Cuentas pendientes, productores, parcelas, lotes por estado, alertas sin resolver'), new OA\Response(response: 403, description: 'No es Administrador')]
    )]
    public function reportes(Request $request)
    {
        $this->verificarAdministrador($request);

        return response()->json([
            'cuentas_pendientes' => User::where('estado', 'pendiente')->count(),
            'productores_aprobados' => User::where('rol', 'productor')->where('estado', 'aprobado')->count(),
            'total_parcelas' => Parcela::count(),
            'total_lotes' => Cosecha::count(),
            'lotes_por_estado' => Inventario::selectRaw('estado, count(*) as total')
                ->groupBy('estado')
                ->pluck('total', 'estado'),
            'alertas_sin_resolver' => Alerta::where('resuelta', false)->count(),
        ]);
    }

    /**
     * Historial de auditoria (RF27): quien modifico que informacion y cuando.
     * Por ahora cubre las acciones sobre cuentas de usuario (crear, aprobar,
     * rechazar, desactivar, reactivar, editar datos propios).
     */
    #[OA\Get(
        path: '/administrador/auditoria',
        summary: 'Historial de auditoría (RF27)',
        description: 'Quién modificó qué y cuándo: altas, aprobaciones, rechazos, desactivaciones y ediciones de cuenta.',
        tags: ['Administrador'],
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Últimos 200 registros de auditoría'), new OA\Response(response: 403, description: 'No es Administrador')]
    )]
    public function auditoria(Request $request)
    {
        $this->verificarAdministrador($request);

        $registros = Auditoria::with(['usuario:id,nombre,email', 'usuarioAfectado:id,nombre,email'])
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        return response()->json($registros);
    }

    /**
     * RF23: lotes ya verificados por el Verificador, listos para que el
     * Administrador autorice su exportacion final. Se excluyen los que ya
     * fueron exportados.
     */
    #[OA\Get(
        path: '/administrador/lotes-para-exportar',
        summary: 'Lotes ya verificados, listos para autorizar exportación (RF23)',
        tags: ['Administrador'],
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Lotes verificados aún no exportados'), new OA\Response(response: 403, description: 'No es Administrador')]
    )]
    public function lotesParaExportar(Request $request)
    {
        $this->verificarAdministrador($request);

        $cosechas = Cosecha::whereHas('verificacion', function ($q) {
                $q->where('estado', 'verificado');
            })
            ->whereHas('inventario', function ($q) {
                $q->where('estado', '!=', 'exportado');
            })
            ->with(['parcela.productor', 'verificacion', 'inventario'])
            ->orderByDesc('id')
            ->get();

        return response()->json($cosechas);
    }

    /**
     * RF23: autoriza la exportacion final de un lote ya verificado. Marca el
     * inventario como "exportado" (la misma mecanica que ya existia, pero
     * ahora es una accion exclusiva del Administrador, no del productor).
     */
    #[OA\Put(
        path: '/administrador/lotes/{id}/autorizar-exportacion',
        summary: 'Autoriza la exportación final de un lote verificado (RF23)',
        tags: ['Administrador'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'ID de la cosecha (lote)')],
        responses: [
            new OA\Response(response: 200, description: 'Exportación autorizada'),
            new OA\Response(response: 403, description: 'No es Administrador'),
            new OA\Response(response: 422, description: 'El lote todavía no fue verificado'),
        ]
    )]
    public function autorizarExportacion(Request $request, int $cosechaId)
    {
        $this->verificarAdministrador($request);

        $cosecha = Cosecha::with(['verificacion', 'inventario'])->findOrFail($cosechaId);

        if (! $cosecha->verificacion || $cosecha->verificacion->estado !== 'verificado') {
            return response()->json(['message' => 'Este lote todavía no fue verificado.'], 422);
        }

        if (! $cosecha->inventario) {
            return response()->json(['message' => 'Este lote no tiene registro de inventario.'], 422);
        }

        $cosecha->inventario->update([
            'estado' => 'exportado',
            'cantidad_disponible' => 0,
            'fecha_actualizacion' => now()->toDateString(),
        ]);

        Auditoria::registrar($request->user()->id, null, 'autorizar_exportacion', "Autorizó la exportación del lote {$cosecha->codigo_trazabilidad}.");

        return response()->json(['message' => 'Exportación autorizada.', 'inventario' => $cosecha->inventario->fresh()]);
    }

    /**
     * Lotes ya exportados, con su linea de tiempo de envio, para que el
     * Administrador le vaya agregando el estado del transporte hasta que
     * llegue al comprador.
     */
    #[OA\Get(
        path: '/administrador/lotes-exportados',
        summary: 'Lotes ya exportados, con su línea de tiempo de envío',
        tags: ['Administrador'],
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Lotes exportados con sus envíos'), new OA\Response(response: 403, description: 'No es Administrador')]
    )]
    public function lotesExportados(Request $request)
    {
        $this->verificarAdministrador($request);

        $cosechas = Cosecha::whereHas('inventario', function ($q) {
                $q->where('estado', 'exportado');
            })
            ->with([
                'parcela.productor',
                'envios' => fn ($q) => $q->orderBy('fecha')->orderBy('id'),
            ])
            ->orderByDesc('id')
            ->get();

        return response()->json($cosechas);
    }

    /**
     * Agrega un nuevo estado a la linea de tiempo de envio de un lote ya
     * exportado (no reemplaza los anteriores, los va acumulando). No
     * requiere GPS ni una empresa de transporte real: el Administrador lo
     * carga a mano a medida que avanza el envio.
     */
    #[OA\Post(
        path: '/administrador/lotes/{id}/envio',
        summary: 'Agrega un nuevo estado a la línea de tiempo de envío de un lote exportado',
        description: 'Se acumula (no reemplaza los anteriores): listo_transporte -> en_transito -> en_aduana -> entregado.',
        tags: ['Administrador'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'ID de la cosecha (lote)')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['estado', 'fecha'],
            properties: [
                new OA\Property(property: 'estado', type: 'string', enum: ['listo_transporte', 'en_transito', 'en_aduana', 'entregado']),
                new OA\Property(property: 'fecha', type: 'string', format: 'date'),
                new OA\Property(property: 'nota', type: 'string', nullable: true),
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: 'Estado de envío agregado'),
            new OA\Response(response: 403, description: 'No es Administrador'),
            new OA\Response(response: 422, description: 'El lote todavía no fue exportado'),
        ]
    )]
    public function agregarEstadoEnvio(Request $request, int $cosechaId)
    {
        $this->verificarAdministrador($request);

        $cosecha = Cosecha::with('inventario')->findOrFail($cosechaId);

        if (! $cosecha->inventario || $cosecha->inventario->estado !== 'exportado') {
            return response()->json(['message' => 'Este lote todavía no fue exportado.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'estado' => 'required|in:listo_transporte,en_transito,en_aduana,entregado',
            'fecha' => 'required|date',
            'nota' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $envio = Envio::create([
            'cosecha_id' => $cosecha->id,
            'estado' => $request->estado,
            'fecha' => $request->fecha,
            'nota' => $request->nota,
            'created_at' => now(),
        ]);

        Auditoria::registrar($request->user()->id, null, 'agregar_estado_envio', "Actualizó el envío del lote {$cosecha->codigo_trazabilidad} a \"{$request->estado}\".");

        return response()->json(['message' => 'Estado de envío agregado.', 'envio' => $envio], 201);
    }

    /**
     * RF22: bandeja de reclamos registrados por compradores desde la pagina
     * publica de trazabilidad.
     */
    #[OA\Get(
        path: '/administrador/reclamos',
        summary: 'Bandeja de reclamos registrados por compradores (RF22)',
        tags: ['Administrador'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'estado', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pendiente', 'atendido']))],
        responses: [new OA\Response(response: 200, description: 'Listado de reclamos'), new OA\Response(response: 403, description: 'No es Administrador')]
    )]
    public function reclamos(Request $request)
    {
        $this->verificarAdministrador($request);

        $query = Reclamo::with('cosecha:id,codigo_trazabilidad,variedad_cafe')
            ->orderByDesc('created_at');

        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        return response()->json($query->get());
    }

    /**
     * Marca un reclamo como atendido.
     */
    #[OA\Put(
        path: '/administrador/reclamos/{id}/atender',
        summary: 'Marca un reclamo como atendido',
        tags: ['Administrador'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Reclamo actualizado'), new OA\Response(response: 403, description: 'No es Administrador')]
    )]
    public function atenderReclamo(Request $request, int $id)
    {
        $this->verificarAdministrador($request);

        $reclamo = Reclamo::findOrFail($id);
        $reclamo->update(['estado' => 'atendido']);

        Auditoria::registrar($request->user()->id, null, 'atender_reclamo', "Marcó como atendido el reclamo #{$reclamo->id} del lote {$reclamo->cosecha->codigo_trazabilidad}.");

        return response()->json(['message' => 'Reclamo marcado como atendido.', 'reclamo' => $reclamo->fresh()]);
    }

    /**
     * RF26: busqueda y filtrado general de lotes por productor, fecha o
     * estado, para la supervision general del Administrador.
     */
    #[OA\Get(
        path: '/administrador/lotes/buscar',
        summary: 'Búsqueda y filtrado general de lotes (RF26)',
        tags: ['Administrador'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Código, variedad, parcela o productor'),
            new OA\Parameter(name: 'estado_inventario', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'estado_verificacion', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'desde', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'hasta', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [new OA\Response(response: 200, description: 'Hasta 200 lotes coincidentes'), new OA\Response(response: 403, description: 'No es Administrador')]
    )]
    public function buscarLotes(Request $request)
    {
        $this->verificarAdministrador($request);

        $query = Cosecha::with(['parcela.productor', 'verificacion', 'inventario', 'etapasProcesamiento', 'controlesCalidad']);

        if ($request->filled('q')) {
            $texto = $request->input('q');
            $query->where(function ($q) use ($texto) {
                $q->where('codigo_trazabilidad', 'like', "%{$texto}%")
                    ->orWhere('variedad_cafe', 'like', "%{$texto}%")
                    ->orWhereHas('parcela', function ($q2) use ($texto) {
                        $q2->where('nombre_parcela', 'like', "%{$texto}%");
                    })
                    ->orWhereHas('parcela.productor', function ($q2) use ($texto) {
                        $q2->where('nombre_completo', 'like', "%{$texto}%");
                    });
            });
        }

        if ($request->filled('estado_inventario')) {
            $query->whereHas('inventario', function ($q) use ($request) {
                $q->where('estado', $request->input('estado_inventario'));
            });
        }

        if ($request->filled('estado_verificacion')) {
            $query->whereHas('verificacion', function ($q) use ($request) {
                $q->where('estado', $request->input('estado_verificacion'));
            });
        }

        if ($request->filled('desde')) {
            $query->where('fecha', '>=', $request->input('desde'));
        }

        if ($request->filled('hasta')) {
            $query->where('fecha', '<=', $request->input('hasta'));
        }

        $lotes = $query->orderByDesc('id')->limit(200)->get();

        return response()->json($lotes);
    }
}
