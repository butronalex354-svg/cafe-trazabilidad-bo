<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alerta extends Model
{
    protected $table = 'alerta';

    public $timestamps = false;

    protected $fillable = [
        'diagnostico_id',
        'productor_id',
        'mensaje',
        'fecha',
        'leida',
        'resuelta',
    ];

    protected $casts = [
        'leida' => 'boolean',
        'resuelta' => 'boolean',
    ];

    public function diagnostico(): BelongsTo
    {
        return $this->belongsTo(Diagnostico::class, 'diagnostico_id');
    }

    public function productor(): BelongsTo
    {
        return $this->belongsTo(Productor::class, 'productor_id');
    }
}
