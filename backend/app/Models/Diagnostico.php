<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Diagnostico extends Model
{
    protected $table = 'diagnostico';

    public $timestamps = false;

    protected $fillable = [
        'parcela_id',
        'imagen_url',
        'fecha',
        'resultado',
        'confianza',
    ];

    public function parcela(): BelongsTo
    {
        return $this->belongsTo(Parcela::class, 'parcela_id');
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class, 'diagnostico_id');
    }
}
