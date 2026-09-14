<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RF02 - Registro de parcelas.
 *
 * NOTA: la tabla `parcela` ya existia en la base de datos (creada directamente
 * en MySQL/phpMyAdmin durante el diseno del modelo ER). Esta migracion
 * documenta esa estructura original; el campo `tipo_suelo` se agrega aparte,
 * en la migracion `add_tipo_suelo_to_parcela_table`, que si se ejecuta de
 * verdad porque es una columna nueva.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('parcela')) {
            return;
        }

        Schema::create('parcela', function (Blueprint $table) {
            $table->id();
            $table->foreignId('productor_id')->constrained('productor');
            $table->string('nombre_parcela', 100)->nullable();
            $table->string('ubicacion', 255)->nullable();
            $table->decimal('tamano_terreno', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcela');
    }
};
