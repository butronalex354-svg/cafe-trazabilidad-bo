<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlCalidad extends Model
{
    protected $table = 'controlcalidad';

    public $timestamps = false;

    protected $fillable = [
        'cosecha_id',
        'fecha',
        'humedad',
        'defectos',
        'clasificacion',
        'observaciones',
    ];

    public function cosecha(): BelongsTo
    {
        return $this->belongsTo(Cosecha::class, 'cosecha_id');
    }
}
