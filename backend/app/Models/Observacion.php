<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Observacion extends Model
{
    /**
     * La tabla real ya existente en la base de datos (creada fuera de Laravel).
     */
    protected $table = 'observacion';

    public $timestamps = false;

    protected $fillable = [
        'parcela_id',
        'fecha',
        'nota',
        'foto_url',
    ];

    public function parcela(): BelongsTo
    {
        return $this->belongsTo(Parcela::class, 'parcela_id');
    }
}
