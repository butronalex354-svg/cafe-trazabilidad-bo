<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parcela extends Model
{
    /**
     * La tabla real ya existente en la base de datos (creada fuera de Laravel).
     */
    protected $table = 'parcela';

    public $timestamps = false;

    protected $fillable = [
        'productor_id',
        'nombre_parcela',
        'ubicacion',
        'tipo_suelo',
        'tamano_terreno',
    ];

    public function productor(): BelongsTo
    {
        return $this->belongsTo(Productor::class, 'productor_id');
    }

    public function cosechas(): HasMany
    {
        return $this->hasMany(Cosecha::class, 'parcela_id');
    }

    public function condicionesClimaticas(): HasMany
    {
        return $this->hasMany(CondicionClimatica::class, 'parcela_id');
    }

    public function observaciones(): HasMany
    {
        return $this->hasMany(Observacion::class, 'parcela_id');
    }
}
