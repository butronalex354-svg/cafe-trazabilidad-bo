<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CondicionClimatica extends Model
{
    /**
     * La tabla real ya existente en la base de datos (creada fuera de Laravel).
     */
    protected $table = 'condicionclimatica';

    public $timestamps = false;

    protected $fillable = [
        'parcela_id',
        'fecha',
        'temperatura',
        'humedad',
        'probabilidad_lluvia',
        'fuente',
    ];

    public function parcela(): BelongsTo
    {
        return $this->belongsTo(Parcela::class, 'parcela_id');
    }
}
