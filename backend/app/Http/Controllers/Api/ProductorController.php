<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResuelveProductorAutenticado;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductorController extends Controller
{
    use ResuelveProductorAutenticado;

    /**
     * RF01: perfil del productor autenticado (datos personales).
     */
    public function show(Request $request)
    {
        return response()->json($this->productorAutenticado($request));
    }

    /**
     * RF01: editar los datos personales del productor autenticado.
     */
    public function update(Request $request)
    {
        $productor = $this->productorAutenticado($request);

        $validator = Validator::make($request->all(), [
            'nombre_completo' => 'required|string|max:150',
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

        $productor->update([
            'nombre_completo' => $request->nombre_completo,
            'ci' => $request->ci,
            'telefono' => $request->telefono,
            'whatsapp' => $request->whatsapp ?: $request->telefono,
            'direccion' => $request->direccion,
        ]);

        return response()->json($productor->fresh());
    }
}
