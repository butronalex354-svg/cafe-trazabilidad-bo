<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RF05 - Observaciones periodicas del cultivo (fotos y notas).
 *
 * NOTA: la tabla `observacion` ya existia en la base de datos (creada
 * directamente en MySQL/phpMyAdmin durante el diseno del modelo ER). Esta
 * migracion documenta esa estructura dentro del proyecto Laravel; no se
 * ejecuta con `php artisan migrate` porque la tabla ya existe.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('observacion')) {
            return;
        }

        Schema::create('observacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcela_id')->constrained('parcela');
            $table->date('fecha')->nullable();
            $table->text('nota')->nullable();
            $table->string('foto_url')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observacion');
    }
};
