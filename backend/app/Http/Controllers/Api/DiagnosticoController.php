<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResuelveProductorAutenticado;
use App\Http\Controllers\Controller;
use App\Models\Alerta;
use App\Models\Diagnostico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DiagnosticoController extends Controller
{
    use ResuelveProductorAutenticado;

    /**
     * RF08: historial de diagnosticos del productor autenticado.
     * Filtro opcional por parcela: ?parcela_id=3
     */
    public function index(Request $request)
    {
        $productor = $this->productorAutenticado($request);

        $diagnosticos = Diagnostico::whereHas('parcela', function ($q) use ($productor) {
                $q->where('productor_id', $productor->id);
            })
            ->when($request->filled('parcela_id'), function ($q) use ($request) {
                $q->where('parcela_id', $request->integer('parcela_id'));
            })
            ->with('parcela:id,nombre_parcela')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        return response()->json($diagnosticos);
    }

    /**
     * RF06+RF07: sube la foto de la planta y genera el diagnostico.
     *
     * El diagnostico ahora lo hace un modelo de IA REAL (entrenado con el
     * dataset RoCoLe, fotos reales de hojas de cafe con roya/acaros/sanas),
     * servido por el microservicio FastAPI en ia-service/. Si ese servicio
     * no esta corriendo, se devuelve un error claro en vez de inventar un
     * resultado — nunca se simula silenciosamente.
     */
    public function store(Request $request)
    {
        $productor = $this->productorAutenticado($request);

        $validator = Validator::make($request->all(), [
            'parcela_id' => 'required|integer',
            'foto' => 'required|image|max:5120', // 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $parcela = $productor->parcelas()->find($request->parcela_id);

        if (! $parcela) {
            return response()->json([
                'message' => 'La parcela indicada no existe o no te pertenece.',
            ], 422);
        }

        $archivoFoto = $request->file('foto');

        try {
            $respuestaIA = Http::timeout(20)
                ->attach('foto', file_get_contents($archivoFoto->getRealPath()), $archivoFoto->getClientOriginalName())
                ->post(config('services.ia.url') . '/diagnosticar');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'No se pudo conectar con el servicio de IA. Verifica que esté corriendo.',
            ], 502);
        }

        if (! $respuestaIA->successful()) {
            return response()->json([
                'message' => 'El servicio de IA no pudo procesar la imagen: ' . ($respuestaIA->json('detail') ?? 'error desconocido'),
            ], 502);
        }

        $datosIA = $respuestaIA->json();
        $resultado = $datosIA['resultado'];
        $confianza = $datosIA['confianza'];

        // El modelo tiene una clase especial "no_es_cafe" (entrenada con fotos
        // genericas ajenas al cafe) ademas de sana/plaga/enfermedad — si sale
        // esa clase, o si ninguna clase alcanzo confianza suficiente, no se
        // guarda un diagnostico enganoso.
        if (! ($datosIA['reconocido'] ?? true)) {
            $mensaje = $resultado === 'no_es_cafe'
                ? "La imagen no parece ser una hoja o fruto de café ({$confianza}% de seguridad). Sube una foto clara del cultivo."
                : "No se pudo determinar el diagnóstico con confianza (solo {$confianza}%). Intenta con una foto más clara.";

            return response()->json(['message' => $mensaje], 422);
        }

        $ruta = $archivoFoto->store('diagnosticos', 'public');
        $imagenUrl = Storage::url($ruta);

        $diagnostico = Diagnostico::create([
            'parcela_id' => $parcela->id,
            'imagen_url' => $imagenUrl,
            'fecha' => now()->toDateString(),
            'resultado' => $resultado,
            'confianza' => $confianza,
        ]);

        // RF09: si el diagnostico detecto plaga o enfermedad, se genera una alerta automatica.
        if ($resultado !== 'sana') {
            Alerta::create([
                'diagnostico_id' => $diagnostico->id,
                'productor_id' => $productor->id,
                'mensaje' => "Se detectó posible {$resultado} en la parcela \"{$parcela->nombre_parcela}\" (confianza {$confianza}%). Revisa el cultivo cuanto antes.",
                'fecha' => now()->toDateString(),
                'leida' => false,
            ]);
        }

        return response()->json($diagnostico, 201);
    }

    /**
     * Eliminar un diagnostico propio (y su foto y alerta asociada, si tiene).
     */
    public function destroy(Request $request, int $id)
    {
        $productor = $this->productorAutenticado($request);

        $diagnostico = Diagnostico::whereHas('parcela', function ($q) use ($productor) {
                $q->where('productor_id', $productor->id);
            })
            ->find($id);

        if (! $diagnostico) {
            return response()->json(['message' => 'Diagnóstico no encontrado.'], 404);
        }

        // La FK de alerta.diagnostico_id es RESTRICT, asi que hay que borrar
        // primero la(s) alerta(s) que este diagnostico haya generado (RF09).
        $diagnostico->alertas()->delete();

        if ($diagnostico->imagen_url) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $diagnostico->imagen_url));
        }

        $diagnostico->delete();

        return response()->json(['message' => 'Diagnóstico eliminado.']);
    }
}
