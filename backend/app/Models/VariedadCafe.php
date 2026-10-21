<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariedadCafe extends Model
{
    protected $table = 'variedad_cafe';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
