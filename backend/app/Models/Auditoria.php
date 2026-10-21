<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
{
    protected $table = 'auditoria';

    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'usuario_afectado_id',
        'accion',
        'descripcion',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function usuarioAfectado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_afectado_id');
    }

    /**
     * Atajo para registrar una entrada de auditoria desde cualquier controller.
     */
    public static function registrar(?int $usuarioId, ?int $usuarioAfectadoId, string $accion, string $descripcion): void
    {
        static::create([
            'usuario_id' => $usuarioId,
            'usuario_afectado_id' => $usuarioAfectadoId,
            'accion' => $accion,
            'descripcion' => $descripcion,
            'created_at' => now(),
        ]);
    }
}
