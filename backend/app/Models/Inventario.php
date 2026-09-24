<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventario extends Model
{
    protected $table = 'inventario';

    public $timestamps = false;

    protected $fillable = [
        'cosecha_id',
        'cantidad_disponible',
        'estado',
        'fecha_actualizacion',
    ];

    public function cosecha(): BelongsTo
    {
        return $this->belongsTo(Cosecha::class, 'cosecha_id');
    }
}
