<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Envio extends Model
{
    protected $table = 'envio';

    public $timestamps = false;

    protected $fillable = [
        'cosecha_id',
        'estado',
        'fecha',
        'nota',
        'created_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'created_at' => 'datetime',
    ];

    public function cosecha(): BelongsTo
    {
        return $this->belongsTo(Cosecha::class, 'cosecha_id');
    }
}
