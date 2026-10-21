<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Verificacion extends Model
{
    protected $table = 'verificacion';

    public $timestamps = false;

    protected $fillable = [
        'cosecha_id',
        'estado',
        'verificador_id',
        'observaciones',
        'fecha_solicitud',
        'fecha_resolucion',
    ];

    protected $casts = [
        'fecha_solicitud' => 'datetime',
        'fecha_resolucion' => 'datetime',
    ];

    public function cosecha(): BelongsTo
    {
        return $this->belongsTo(Cosecha::class, 'cosecha_id');
    }

    public function verificador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verificador_id');
    }
}
