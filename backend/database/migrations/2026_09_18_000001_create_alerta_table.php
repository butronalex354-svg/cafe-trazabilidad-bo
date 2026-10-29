<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RF09 - Alertas automaticas para el productor.
 *
 * NOTA: la tabla `alerta` ya existia en la base de datos (creada directamente
 * en MySQL/phpMyAdmin). No tenia NINGUNA migracion -- las que existen
 * (`add_resuelta_to_alerta_table`, `add_stock_fields_to_alerta_and_inventario`,
 * `add_suelo_to_alerta_table`) solo AGREGAN columnas y asumen que la tabla
 * base ya existe, asi que un entorno nuevo no podia recrearla. Esta
 * migracion documenta su estructura ORIGINAL (antes de esas 3 migraciones,
 * que se siguen ejecutando normalmente despues de esta y agregan
 * resuelta/tipo/inventario_id/parcela_id). No se ejecuta en la BD real
 * porque la tabla ya existe (el guard la salta).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('alerta')) {
            return;
        }

        Schema::create('alerta', function (Blueprint $table) {
            // int(11) con signo, igual que el resto de tablas creadas fuera
            // de Laravel (ver nota en create_productor_table.php).
            $table->integer('id', true);
            $table->integer('diagnostico_id');
            $table->foreign('diagnostico_id')->references('id')->on('diagnostico');
            $table->integer('productor_id');
            $table->foreign('productor_id')->references('id')->on('productor');
            $table->text('mensaje')->nullable();
            $table->date('fecha')->nullable();
            $table->boolean('leida')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerta');
    }
};
