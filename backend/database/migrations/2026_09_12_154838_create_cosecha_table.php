<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RF03 - Registro de cosechas (lotes) y su codigo de trazabilidad.
 *
 * NOTA: la tabla `cosecha` ya existia en la base de datos (creada directamente
 * en MySQL/phpMyAdmin durante el diseno del modelo ER). Esta migracion
 * documenta esa estructura dentro del proyecto Laravel; no se ejecuta con
 * `php artisan migrate` porque la tabla ya existe.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cosecha')) {
            return;
        }

        Schema::create('cosecha', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcela_id')->constrained('parcela');
            $table->date('fecha')->nullable();
            $table->decimal('cantidad', 10, 2)->nullable();
            $table->string('variedad_cafe', 100)->nullable();
            $table->string('codigo_trazabilidad', 50)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cosecha');
    }
};
