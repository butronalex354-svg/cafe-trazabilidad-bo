<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alerta;
use App\Models\Cosecha;
use App\Models\Inventario;
use App\Models\Parcela;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
    public function aprobarUsuario(Request $request, $id)
    {
        $this->verificarAdministrador($request);

        $usuario = User::findOrFail($id);
        $usuario->estado = 'aprobado';
        $usuario->save();

        return response()->json(['message' => 'Cuenta aprobada.', 'usuario' => $usuario]);
    }

    /**
     * Rechaza una cuenta pendiente.
     */
    public function rechazarUsuario(Request $request, $id)
    {
        $this->verificarAdministrador($request);

        $usuario = User::findOrFail($id);
        $usuario->estado = 'rechazado';
        $usuario->save();

        return response()->json(['message' => 'Cuenta rechazada.', 'usuario' => $usuario]);
    }

    /**
     * Crea directamente una cuenta de Verificador o Administrador (RF25).
     * Estas no se auto-registran desde el formulario publico (ese solo crea
     * Productores), asi que el Administrador es quien las da de alta, ya
     * aprobadas (no pasan por el flujo de pendiente/aprobacion).
     */
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

        return response()->json(['message' => 'Cuenta creada correctamente.', 'usuario' => $usuario], 201);
    }

    /**
     * Desactiva una cuenta ya aprobada (ej. un verificador que dejo el
     * equipo, o un productor que incumple). Se puede reactivar despues.
     */
    public function desactivarUsuario(Request $request, $id)
    {
        $this->verificarAdministrador($request);

        $usuario = User::findOrFail($id);
        $usuario->estado = 'desactivado';
        $usuario->save();

        return response()->json(['message' => 'Cuenta desactivada.', 'usuario' => $usuario]);
    }

    /**
     * Reactiva una cuenta que estaba desactivada.
     */
    public function reactivarUsuario(Request $request, $id)
    {
        $this->verificarAdministrador($request);

        $usuario = User::findOrFail($id);
        $usuario->estado = 'aprobado';
        $usuario->save();

        return response()->json(['message' => 'Cuenta reactivada.', 'usuario' => $usuario]);
    }

    /**
     * Reportes generales para el panel del Administrador (supervision
     * general del sistema, segun la tabla de actores del documento de
     * requerimientos).
     */
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
}
