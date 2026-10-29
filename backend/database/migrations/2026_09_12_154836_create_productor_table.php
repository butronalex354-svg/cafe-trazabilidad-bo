<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RF01 - Registro de productores.
 *
 * NOTA: la tabla `productor` ya existia en la base de datos (fue creada
 * directamente en MySQL/phpMyAdmin durante el diseno inicial del modelo ER,
 * antes de programar el backend). Esta migracion documenta su estructura
 * dentro del proyecto Laravel; no se ejecuta con `php artisan migrate` porque
 * la tabla ya existe (se registro manualmente en la tabla `migrations`).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('productor')) {
            return;
        }

        Schema::create('productor', function (Blueprint $table) {
            // id/usuario_id son int(11) con signo (igual que `usuario`), no
            // bigint -- si no, la FK no se puede crear (errno 150).
            $table->integer('id', true);
            $table->integer('usuario_id');
            $table->foreign('usuario_id')->references('id')->on('usuario');
            $table->string('nombre_completo', 150);
            $table->string('ci', 20)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('direccion', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productor');
    }
};
