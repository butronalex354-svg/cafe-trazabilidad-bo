<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResuelveProductorAutenticado;
use App\Http\Controllers\Controller;
use App\Models\Alerta;
use App\Models\Cosecha;
use App\Models\Parcela;
use App\Models\Verificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class VerificacionController extends Controller
{
    use ResuelveProductorAutenticado;

    /**
     * RF14: el productor solicita que su lote sea verificado. Si el lote
     * nunca se solicito, crea el registro; si quedo "observado" (el
     * verificador pidio una correccion), lo reinicia a "pendiente" para que
     * lo revise de nuevo. Un lote "rechazado" NO se puede reenviar: el
     * rechazo es una decision final, a diferencia de "observado" que es
     * justamente una invitacion a corregir y volver a intentar - si ambos
     * estados permitieran reenvio, dejarian de tener una diferencia real.
     */
    #[OA\Post(
        path: '/cosechas/{id}/solicitar-verificacion',
        summary: 'El productor solicita verificación de su lote',
        description: 'Un lote "observado" se puede reenviar (vuelve a pendiente). Un lote "rechazado" no se puede reenviar.',
        tags: ['Verificación'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'ID de la cosecha (lote)')],
        responses: [
            new OA\Response(response: 201, description: 'Solicitud enviada'),
            new OA\Response(response: 404, description: 'Lote no encontrado'),
            new OA\Response(response: 422, description: 'Ya tiene una solicitud pendiente/verificada/rechazada'),
        ]
    )]
    public function solicitar(Request $request, int $cosechaId)
    {
        $productor = $this->productorAutenticado($request);
        $cosecha = $this->cosechaPropia($productor->id, $cosechaId);

        if (! $cosecha) {
            return response()->json(['message' => 'Lote no encontrado.'], 404);
        }

        $verificacion = $cosecha->verificacion;

        if ($verificacion && $verificacion->estado !== 'observado') {
            $mensajes = [
                'pendiente' => 'Este lote ya tiene una solicitud de verificación pendiente.',
                'verificado' => 'Este lote ya fue verificado.',
                'rechazado' => 'Este lote fue rechazado por el verificador y no se puede volver a enviar. Si corresponde, registra un nuevo lote.',
            ];

            return response()->json(['message' => $mensajes[$verificacion->estado]], 422);
        }

        if ($verificacion) {
            $verificacion->update([
                'estado' => 'pendiente',
                'verificador_id' => null,
                'observaciones' => null,
                'fecha_solicitud' => now(),
                'fecha_resolucion' => null,
            ]);
        } else {
            $verificacion = Verificacion::create([
                'cosecha_id' => $cosecha->id,
                'estado' => 'pendiente',
                'fecha_solicitud' => now(),
            ]);
        }

        return response()->json(['message' => 'Solicitud de verificación enviada.', 'verificacion' => $verificacion], 201);
    }

    /**
     * Corta el paso a cualquiera que no sea verificador.
     */
    private function verificarVerificador(Request $request)
    {
        if ($request->user()->rol !== 'verificador') {
            abort(response()->json(['message' => 'No autorizado.'], 403));
        }
    }

    /**
     * RF14: bandeja de solicitudes, para el Verificador. Filtro opcional por
     * estado (por defecto muestra todas, mas nuevas primero).
     */
    #[OA\Get(
        path: '/verificador/solicitudes',
        summary: 'Bandeja de solicitudes de verificación (rol Verificador)',
        tags: ['Verificación'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'estado', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pendiente', 'observado', 'verificado', 'rechazado']))],
        responses: [new OA\Response(response: 200, description: 'Listado de solicitudes'), new OA\Response(response: 403, description: 'No es Verificador')]
    )]
    public function index(Request $request)
    {
        $this->verificarVerificador($request);

        $query = Verificacion::with([
                'cosecha.parcela.productor',
                'verificador:id,nombre',
            ])
            ->orderByDesc('fecha_solicitud');

        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        return response()->json($query->get());
    }

    /**
     * RF15: evidencia completa del lote para que el verificador compruebe
     * origen (parcela), produccion, procesamiento y calidad.
     */
    #[OA\Get(
        path: '/verificador/solicitudes/{id}',
        summary: 'Evidencia completa del lote para revisión técnica',
        description: 'Origen, producción, procesamiento, calidad y diagnósticos de IA recientes de la parcela.',
        tags: ['Verificación'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Detalle de la solicitud'), new OA\Response(response: 403, description: 'No es Verificador')]
    )]
    public function detalle(Request $request, int $id)
    {
        $this->verificarVerificador($request);

        $verificacion = Verificacion::with([
                'cosecha.parcela.productor',
                'cosecha.etapasProcesamiento',
                'cosecha.controlesCalidad',
                'cosecha.inventario',
                'verificador:id,nombre',
            ])
            ->findOrFail($id);

        // Diagnosticos de IA recientes de la parcela (evidencia de calidad del
        // cultivo, no solo del lote puntual).
        $diagnosticos = $verificacion->cosecha->parcela
            ->diagnosticos()
            ->orderByDesc('fecha')
            ->limit(10)
            ->get();

        return response()->json([
            'verificacion' => $verificacion,
            'diagnosticos_parcela' => $diagnosticos,
        ]);
    }

    /**
     * RF16-17: el verificador cambia el estado del lote (Observado, Verificado
     * o Rechazado) y puede dejar observaciones/incidencias para el productor.
     */
    #[OA\Put(
        path: '/verificador/solicitudes/{id}',
        summary: 'Resuelve una solicitud: observado, verificado o rechazado',
        tags: ['Verificación'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['estado'],
            properties: [
                new OA\Property(property: 'estado', type: 'string', enum: ['observado', 'verificado', 'rechazado']),
                new OA\Property(property: 'observaciones', type: 'string', nullable: true),
            ]
        )),
        responses: [new OA\Response(response: 200, description: 'Verificación actualizada'), new OA\Response(response: 403, description: 'No es Verificador')]
    )]
    public function resolver(Request $request, int $id)
    {
        $this->verificarVerificador($request);

        $verificacion = Verificacion::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'estado' => 'required|in:observado,verificado,rechazado',
            'observaciones' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $verificacion->update([
            'estado' => $request->estado,
            'observaciones' => $request->observaciones,
            'verificador_id' => $request->user()->id,
            'fecha_resolucion' => now(),
        ]);

        return response()->json(['message' => 'Verificación actualizada.', 'verificacion' => $verificacion->fresh()]);
    }

    /**
     * "Mi historial" del Verificador: las verificaciones que el resolvio
     * personalmente, mas recientes primero. Distinto de la bandeja general
     * (index), que muestra TODAS las solicitudes sin importar quien las vio.
     */
    #[OA\Get(
        path: '/verificador/mi-historial',
        summary: 'Verificaciones resueltas personalmente por el Verificador autenticado',
        tags: ['Verificación'],
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Historial propio')]
    )]
    public function miHistorial(Request $request)
    {
        $this->verificarVerificador($request);

        $historial = Verificacion::where('verificador_id', $request->user()->id)
            ->whereNotNull('fecha_resolucion')
            ->with(['cosecha.parcela.productor'])
            ->orderByDesc('fecha_resolucion')
            ->get();

        return response()->json($historial);
    }

    /**
     * Historial de verificacion de los propios lotes del Productor: a que
     * resultado llego cada solicitud que hizo, y quien la reviso.
     */
    #[OA\Get(
        path: '/productor/historial-verificaciones',
        summary: 'Historial de verificación de los lotes propios del Productor',
        tags: ['Verificación'],
        security: [['sanctum' => []]],
        responses: [new OA\Response(response: 200, description: 'Historial de verificaciones')]
    )]
    public function historialProductor(Request $request)
    {
        $productor = $this->productorAutenticado($request);

        $historial = Verificacion::whereHas('cosecha.parcela', function ($q) use ($productor) {
                $q->where('productor_id', $productor->id);
            })
            ->whereNotNull('fecha_resolucion')
            ->with(['cosecha:id,codigo_trazabilidad,variedad_cafe', 'verificador:id,nombre'])
            ->orderByDesc('fecha_resolucion')
            ->get();

        return response()->json($historial);
    }

    /**
     * El Verificador (experto agronomo) define los parametros ideales del
     * suelo de una parcela, para que no sea el propio productor quien
     * declara esos valores. Si el pH queda fuera del rango ideal para el
     * cafe (5.0 a 6.5), el sistema genera automaticamente una alerta para
     * el productor con una recomendacion concreta, en vez de limitarse a
     * guardar el dato.
     */
    #[OA\Put(
        path: '/verificador/parcelas/{id}/parametros-suelo',
        summary: 'Define los parámetros ideales de suelo de una parcela',
        description: 'Si el pH queda fuera de 5.0–6.5, genera automáticamente una alerta para el productor.',
        tags: ['Verificación'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'ID de la parcela')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['ph_suelo', 'fertilidad'],
            properties: [
                new OA\Property(property: 'ph_suelo', type: 'number', minimum: 0, maximum: 14),
                new OA\Property(property: 'humedad_suelo', type: 'number', nullable: true),
                new OA\Property(property: 'fertilidad', type: 'string', enum: ['baja', 'media', 'alta']),
            ]
        )),
        responses: [new OA\Response(response: 200, description: 'Parámetros registrados'), new OA\Response(response: 403, description: 'No es Verificador')]
    )]
    public function definirParametrosSuelo(Request $request, int $parcelaId)
    {
        $this->verificarVerificador($request);

        $parcela = Parcela::findOrFail($parcelaId);

        $validator = Validator::make($request->all(), [
            'ph_suelo' => 'required|numeric|between:0,14',
            'humedad_suelo' => 'nullable|numeric|min:0|max:100',
            'fertilidad' => 'required|in:baja,media,alta',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $parcela->update([
            'ph_suelo' => $request->ph_suelo,
            'humedad_suelo' => $request->humedad_suelo,
            'fertilidad' => $request->fertilidad,
            'verificador_id' => $request->user()->id,
        ]);

        if ($request->ph_suelo < 5.0 || $request->ph_suelo > 6.5) {
            $tratamiento = $request->ph_suelo < 5.0
                ? 'Aplica cal agrícola para subir el pH.'
                : 'Aplica azufre o materia orgánica para bajar el pH.';

            Alerta::create([
                'tipo' => 'suelo',
                'parcela_id' => $parcela->id,
                'productor_id' => $parcela->productor_id,
                'mensaje' => "El pH de tu parcela \"{$parcela->nombre_parcela}\" está en {$request->ph_suelo}, fuera del rango ideal para el café (5.0 a 6.5). {$tratamiento}",
                'fecha' => now()->toDateString(),
                'leida' => false,
                'resuelta' => false,
            ]);
        }

        return response()->json(['message' => 'Parámetros de suelo registrados.', 'parcela' => $parcela->fresh()]);
    }

    private function cosechaPropia(int $productorId, int $cosechaId): ?Cosecha
    {
        return Cosecha::whereHas('parcela', function ($q) use ($productorId) {
                $q->where('productor_id', $productorId);
            })
            ->find($cosechaId);
    }
}
