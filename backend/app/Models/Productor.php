<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Productor extends Model
{
    /**
     * La tabla real ya existente en la base de datos (creada fuera de Laravel).
     */
    protected $table = 'productor';

    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'nombre_completo',
        'ci',
        'telefono',
        'whatsapp',
        'direccion',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function parcelas(): HasMany
    {
        return $this->hasMany(Parcela::class, 'productor_id');
    }
}
