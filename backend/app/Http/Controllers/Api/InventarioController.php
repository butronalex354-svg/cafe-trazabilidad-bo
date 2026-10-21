<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResuelveProductorAutenticado;
use App\Http\Controllers\Controller;
use App\Models\Alerta;
use App\Models\Inventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InventarioController extends Controller
{
    use ResuelveProductorAutenticado;

    /**
     * RF13: vista de inventario por lote y por estado del productor autenticado.
     * Filtros opcionales: ?parcela_id=3, ?estado=en_proceso
     */
    public function index(Request $request)
    {
        $productor = $this->productorAutenticado($request);

        $inventario = Inventario::whereHas('cosecha.parcela', function ($q) use ($productor) {
                $q->where('productor_id', $productor->id);
            })
            ->when($request->filled('parcela_id'), function ($q) use ($request) {
                $q->whereHas('cosecha', function ($q2) use ($request) {
                    $q2->where('parcela_id', $request->integer('parcela_id'));
                });
            })
            ->when($request->filled('estado'), function ($q) use ($request) {
                $q->where('estado', $request->input('estado'));
            })
            ->with('cosecha:id,parcela_id,codigo_trazabilidad,variedad_cafe,cantidad', 'cosecha.parcela:id,nombre_parcela')
            ->orderByDesc('fecha_actualizacion')
            ->orderByDesc('id')
            ->get();

        return response()->json($inventario);
    }

    /**
     * RF13: cambia el estado de un lote en el inventario (en_proceso -> terminado).
     * El paso a "exportado" ya NO se hace aqui: es una accion exclusiva del
     * Administrador (RF23), despues de que el Verificador aprobo el lote,
     * para que el propio productor no pueda autoexportarse sin control.
     */
    public function update(Request $request, int $id)
    {
        $productor = $this->productorAutenticado($request);

        $inventario = Inventario::whereHas('cosecha.parcela', function ($q) use ($productor) {
                $q->where('productor_id', $productor->id);
            })
            ->find($id);

        if (! $inventario) {
            return response()->json(['message' => 'Registro de inventario no encontrado.'], 404);
        }

        if ($inventario->estado === 'exportado') {
            return response()->json(['message' => 'Este lote ya fue exportado y no se puede modificar.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'estado' => 'required|in:en_proceso,terminado',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // $request->string() devuelve un objeto Stringable, no un string plano,
        // asi que la comparacion "===" de abajo nunca daba true — se usa
        // input() para tener un string real.
        $nuevoEstado = $request->input('estado');

        $inventario->update([
            'estado' => $nuevoEstado,
            'cantidad_disponible' => $inventario->cosecha->cantidad,
            'fecha_actualizacion' => now()->toDateString(),
        ]);

        return response()->json($inventario->fresh('cosecha.parcela'));
    }

    /**
     * Define el stock minimo y maximo de un lote (Marco Teorico - Gestion de
     * Inventarios). El minimo es lo que dispara la alerta de desabastecimiento.
     */
    public function definirLimites(Request $request, int $id)
    {
        $productor = $this->productorAutenticado($request);

        $inventario = Inventario::whereHas('cosecha.parcela', function ($q) use ($productor) {
                $q->where('productor_id', $productor->id);
            })
            ->find($id);

        if (! $inventario) {
            return response()->json(['message' => 'Registro de inventario no encontrado.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'stock_minimo' => 'nullable|numeric|min:0',
            'stock_maximo' => 'nullable|numeric|min:0|gte:stock_minimo',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $inventario->update([
            'stock_minimo' => $request->input('stock_minimo'),
            'stock_maximo' => $request->input('stock_maximo'),
        ]);

        $this->verificarDesabastecimiento($inventario);

        return response()->json($inventario->fresh('cosecha.parcela'));
    }

    /**
     * Registra una salida de almacen (venta, entrega, merma) descontando la
     * cantidad disponible del lote. Si el resultado cae por debajo del stock
     * minimo definido, genera una alerta automatica de desabastecimiento.
     */
    public function registrarSalida(Request $request, int $id)
    {
        $productor = $this->productorAutenticado($request);

        $inventario = Inventario::whereHas('cosecha.parcela', function ($q) use ($productor) {
                $q->where('productor_id', $productor->id);
            })
            ->find($id);

        if (! $inventario) {
            return response()->json(['message' => 'Registro de inventario no encontrado.'], 404);
        }

        if ($inventario->estado === 'exportado') {
            return response()->json(['message' => 'Este lote ya fue exportado y no se puede modificar.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'cantidad' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos invalidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $cantidadSalida = (float) $request->input('cantidad');

        if ($cantidadSalida > $inventario->cantidad_disponible) {
            return response()->json(['message' => 'No podés sacar más stock del que tenés disponible.'], 422);
        }

        $inventario->update([
            'cantidad_disponible' => $inventario->cantidad_disponible - $cantidadSalida,
            'fecha_actualizacion' => now()->toDateString(),
        ]);

        $this->verificarDesabastecimiento($inventario);

        return response()->json($inventario->fresh('cosecha.parcela'));
    }

    /**
     * Si el stock disponible quedo por debajo del minimo definido, crea una
     * alerta de tipo "stock" (si no hay ya una sin resolver para este lote).
     */
    private function verificarDesabastecimiento(Inventario $inventario): void
    {
        if ($inventario->stock_minimo === null) {
            return;
        }

        if ($inventario->cantidad_disponible >= $inventario->stock_minimo) {
            return;
        }

        $yaExiste = Alerta::where('inventario_id', $inventario->id)
            ->where('tipo', 'stock')
            ->where('resuelta', false)
            ->exists();

        if ($yaExiste) {
            return;
        }

        $cosecha = $inventario->cosecha()->with('parcela')->first();

        Alerta::create([
            'tipo' => 'stock',
            'inventario_id' => $inventario->id,
            'productor_id' => $cosecha->parcela->productor_id,
            'mensaje' => "El lote {$cosecha->codigo_trazabilidad} quedó con {$inventario->cantidad_disponible} kg disponibles, por debajo del stock mínimo definido ({$inventario->stock_minimo} kg).",
            'fecha' => now()->toDateString(),
            'leida' => false,
            'resuelta' => false,
        ]);
    }
}
