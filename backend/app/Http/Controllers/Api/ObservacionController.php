<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResuelveProductorAutenticado;
use App\Http\Controllers\Controller;
use App\Models\Observacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ObservacionController extends Controller
{
    use ResuelveProductorAutenticado;

    /**
     * RF05: listar las observaciones del productor autenticado.
     * Filtro opcional por parcela: ?parcela_id=3
     */
    public function index(Request $request)
    {
        $productor = $this->productorAutenticado($request);

        $observaciones = Observacion::whereHas('parcela', function ($q) use ($productor) {
                $q->where('productor_id', $productor->id);
            })
            ->when($request->filled('parcela_id'), function ($q) use ($request) {
                $q->where('parcela_id', $request->integer('parcela_id'));
            })
            ->with('parcela:id,nombre_parcela')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        return response()->json($observaciones);
    }

    /**
     * RF05: registrar una observacion del cultivo (fecha, nota y foto opcional).
     */
    public function store(Request $request)
    {
        $productor = $this->productorAutenticado($request);

        $validator = Validator::make($request->all(), [
            'parcela_id' => 'required|integer',
            'fecha' => 'required|date_format:Y-m-d|after_or_equal:2000-01-01|before_or_equal:2100-12-31',
            'nota' => 'required|string|max:2000',
            'foto' => 'nullable|image|max:5120', // 5MB
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

        $fotoUrl = null;
        if ($request->hasFile('foto')) {
            $ruta = $request->file('foto')->store('observaciones', 'public');
            $fotoUrl = Storage::url($ruta);
        }

        $observacion = $parcela->observaciones()->create([
            'fecha' => $request->fecha,
            'nota' => $request->nota,
            'foto_url' => $fotoUrl,
        ]);

        return response()->json($observacion, 201);
    }

    /**
     * Eliminar una observacion propia (y su foto, si tiene).
     */
    public function destroy(Request $request, int $id)
    {
        $productor = $this->productorAutenticado($request);

        $observacion = Observacion::whereHas('parcela', function ($q) use ($productor) {
                $q->where('productor_id', $productor->id);
            })
            ->find($id);

        if (! $observacion) {
            return response()->json(['message' => 'Observación no encontrada.'], 404);
        }

        if ($observacion->foto_url) {
            $rutaRelativa = str_replace('/storage/', '', $observacion->foto_url);
            Storage::disk('public')->delete($rutaRelativa);
        }

        $observacion->delete();

        return response()->json(['message' => 'Observación eliminada.']);
    }
}
