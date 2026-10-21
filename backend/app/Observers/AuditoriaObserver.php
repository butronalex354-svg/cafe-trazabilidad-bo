<?php

namespace App\Observers;

use App\Models\Auditoria;
use Illuminate\Database\Eloquent\Model;

/**
 * RF27: registro automatico de auditoria sobre las tablas principales del
 * sistema (parcela, cosecha, verificacion, inventario, control de calidad y
 * etapa de procesamiento), sin que cada controlador tenga que llamar a
 * Auditoria::registrar() a mano. Se registra en created/updated/deleted de
 * cualquier modelo que use este observer (ver AppServiceProvider::boot()).
 */
class AuditoriaObserver
{
    public function created(Model $model): void
    {
        $this->registrar($model, 'crear', "Se creó el registro #{$model->getKey()} en la tabla {$model->getTable()}.");
    }

    public function updated(Model $model): void
    {
        $cambios = $model->getChanges();
        unset($cambios['updated_at']);

        if (empty($cambios)) {
            return;
        }

        $campos = implode(', ', array_keys($cambios));
        $this->registrar(
            $model,
            'actualizar',
            "Se actualizaron los campos [{$campos}] del registro #{$model->getKey()} en la tabla {$model->getTable()}."
        );
    }

    public function deleted(Model $model): void
    {
        $this->registrar($model, 'eliminar', "Se eliminó el registro #{$model->getKey()} de la tabla {$model->getTable()}.");
    }

    private function registrar(Model $model, string $accion, string $descripcion): void
    {
        try {
            $usuarioId = auth()->id();
            Auditoria::registrar($usuarioId, null, "{$accion}_{$model->getTable()}", $descripcion);
        } catch (\Throwable $e) {
            // La auditoria nunca debe tumbar la operacion real que la origino.
            \Illuminate\Support\Facades\Log::warning('No se pudo registrar auditoria automatica: ' . $e->getMessage());
        }
    }
}
