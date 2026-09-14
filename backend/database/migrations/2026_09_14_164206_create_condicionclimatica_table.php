<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RF04 - Prediccion de clima por parcela.
 *
 * NOTA: la tabla `condicionclimatica` ya existia en la base de datos (creada
 * directamente en MySQL/phpMyAdmin durante el diseno del modelo ER). Esta
 * migracion documenta esa estructura original dentro del proyecto Laravel;
 * no se ejecuta con `php artisan migrate` porque la tabla ya existe. El campo
 * `probabilidad_lluvia` se agrega aparte, en una migracion que si es nueva
 * de verdad y si se ejecuta.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('condicionclimatica')) {
            return;
        }

        Schema::create('condicionclimatica', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcela_id')->constrained('parcela');
            $table->date('fecha')->nullable();
            $table->decimal('temperatura', 5, 2)->nullable();
            $table->decimal('humedad', 5, 2)->nullable();
            $table->string('fuente', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condicionclimatica');
    }
};
