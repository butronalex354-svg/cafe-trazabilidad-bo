<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Cosecha extends Model
{
    /**
     * La tabla real ya existente en la base de datos (creada fuera de Laravel).
     */
    protected $table = 'cosecha';

    public $timestamps = false;

    protected $fillable = [
        'parcela_id',
        'fecha',
        'cantidad',
        'variedad_cafe',
        'codigo_trazabilidad',
    ];

    protected static function booted(): void
    {
        // RF03: cada cosecha (lote) debe tener un codigo de trazabilidad unico,
        // generado automaticamente si no se envio uno desde el frontend.
        static::creating(function (Cosecha $cosecha) {
            if (empty($cosecha->codigo_trazabilidad)) {
                $cosecha->codigo_trazabilidad = static::generarCodigoUnico();
            }
        });

        // RF13: cada lote nuevo entra automaticamente al inventario, con toda
        // su cantidad disponible y estado "en_proceso" (recien cosechado).
        static::created(function (Cosecha $cosecha) {
            $cosecha->inventario()->create([
                'cantidad_disponible' => $cosecha->cantidad,
                'estado' => 'en_proceso',
                'fecha_actualizacion' => now()->toDateString(),
            ]);
        });
    }

    public static function generarCodigoUnico(): string
    {
        do {
            $codigo = 'LOTE-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));
        } while (static::where('codigo_trazabilidad', $codigo)->exists());

        return $codigo;
    }

    public function parcela(): BelongsTo
    {
        return $this->belongsTo(Parcela::class, 'parcela_id');
    }

    public function controlesCalidad(): HasMany
    {
        return $this->hasMany(ControlCalidad::class, 'cosecha_id');
    }

    public function etapasProcesamiento(): HasMany
    {
        return $this->hasMany(EtapaProcesamiento::class, 'cosecha_id');
    }

    public function inventario(): HasOne
    {
        return $this->hasOne(Inventario::class, 'cosecha_id');
    }
}
