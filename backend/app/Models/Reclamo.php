<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reclamo extends Model
{
    protected $table = 'reclamo';

    public $timestamps = false;

    protected $fillable = [
        'cosecha_id',
        'nombre_comprador',
        'contacto_comprador',
        'mensaje',
        'estado',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function cosecha(): BelongsTo
    {
        return $this->belongsTo(Cosecha::class, 'cosecha_id');
    }
}
