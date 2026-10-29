<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RF06/RF08 - Diagnostico de plagas/enfermedades por IA.
 *
 * NOTA: la tabla `diagnostico` ya existia en la base de datos (creada
 * directamente en MySQL/phpMyAdmin durante el diseno del modelo ER, junto
 * con `alerta`). No tenia NINGUNA migracion -- ni siquiera una guardada de
 * documentacion como productor/parcela/cosecha -- asi que un entorno nuevo
 * no podia recrearla. Esta migracion documenta su estructura real; no se
 * ejecuta en la BD real porque la tabla ya existe (el guard la salta).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('diagnostico')) {
            return;
        }

        Schema::create('diagnostico', function (Blueprint $table) {
            // int(11) con signo, igual que el resto de tablas creadas fuera
            // de Laravel (ver nota en create_productor_table.php).
            $table->integer('id', true);
            $table->integer('parcela_id');
            $table->foreign('parcela_id')->references('id')->on('parcela');
            $table->string('imagen_url', 255)->nullable();
            $table->date('fecha')->nullable();
            $table->enum('resultado', ['plaga', 'enfermedad', 'sana'])->nullable();
            $table->decimal('confianza', 5, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostico');
    }
};
