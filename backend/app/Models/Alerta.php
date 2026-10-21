<?php

namespace App\Models;

use App\Mail\AlertaGenerada;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Alerta extends Model
{
    protected $table = 'alerta';

    public $timestamps = false;

    protected $fillable = [
        'tipo',
        'diagnostico_id',
        'inventario_id',
        'parcela_id',
        'productor_id',
        'mensaje',
        'fecha',
        'leida',
        'resuelta',
    ];

    protected $casts = [
        'leida' => 'boolean',
        'resuelta' => 'boolean',
    ];

    public function diagnostico(): BelongsTo
    {
        return $this->belongsTo(Diagnostico::class, 'diagnostico_id');
    }

    public function inventario(): BelongsTo
    {
        return $this->belongsTo(Inventario::class, 'inventario_id');
    }

    public function productor(): BelongsTo
    {
        return $this->belongsTo(Productor::class, 'productor_id');
    }

    public function parcela(): BelongsTo
    {
        return $this->belongsTo(Parcela::class, 'parcela_id');
    }

    protected static function booted(): void
    {
        // Notifica por correo al productor cuando se genera una alerta nueva.
        // Si el envio falla (SMTP no configurado, sin internet, etc.) solo se
        // registra en el log: nunca debe tumbar la peticion que genero la alerta.
        static::created(function (Alerta $alerta) {
            try {
                $email = $alerta->productor?->usuario?->email;

                if ($email) {
                    Mail::to($email)->send(new AlertaGenerada($alerta));
                }
            } catch (\Throwable $e) {
                Log::warning("No se pudo enviar el correo de la alerta #{$alerta->id}: " . $e->getMessage());
            }
        });
    }
}
