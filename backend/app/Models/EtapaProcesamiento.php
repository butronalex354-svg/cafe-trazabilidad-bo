<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EtapaProcesamiento extends Model
{
    protected $table = 'etapaprocesamiento';

    public $timestamps = false;

    protected $fillable = [
        'cosecha_id',
        'etapa',
        'fecha',
        'responsable',
        'observaciones',
    ];

    // Orden natural del proceso, para armar la linea de tiempo (RF12) aunque
    // las etapas no se hayan registrado en ese orden.
    public const ORDEN_ETAPAS = ['despulpado', 'fermentacion', 'lavado', 'secado', 'tostado', 'envasado', 'embalaje'];

    public function cosecha(): BelongsTo
    {
        return $this->belongsTo(Cosecha::class, 'cosecha_id');
    }
}
