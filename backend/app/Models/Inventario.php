<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventario extends Model
{
    protected $table = 'inventario';

    public $timestamps = false;

    protected $fillable = [
        'cosecha_id',
        'cantidad_disponible',
        'stock_minimo',
        'stock_maximo',
        'estado',
        'fecha_actualizacion',
    ];

    public function cosecha(): BelongsTo
    {
        return $this->belongsTo(Cosecha::class, 'cosecha_id');
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class, 'inventario_id');
    }
}
