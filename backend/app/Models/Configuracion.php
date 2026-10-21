<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    protected $table = 'configuracion';

    public $timestamps = false;

    protected $fillable = [
        'color_primario',
    ];

    public static function actual(): self
    {
        return static::firstOrCreate(['id' => 1], ['color_primario' => '#e0a84a']);
    }
}
