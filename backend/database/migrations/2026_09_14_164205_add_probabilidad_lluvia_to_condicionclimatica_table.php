<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RF04 - la prediccion de clima debe incluir probabilidad de lluvia, ademas
 * de temperatura y humedad (que ya existian en la tabla).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Este archivo quedo fechado (por error) ANTES que
        // create_condicionclimatica_table, asi que en un entorno nuevo
        // Laravel lo ejecuta primero y la tabla todavia no existe. Se
        // protege con estos guards (en la BD real no afecta nada: la tabla
        // y la columna ya existen, asi que de todas formas no haria nada).
        if (! Schema::hasTable('condicionclimatica') || Schema::hasColumn('condicionclimatica', 'probabilidad_lluvia')) {
            return;
        }

        Schema::table('condicionclimatica', function (Blueprint $table) {
            $table->decimal('probabilidad_lluvia', 5, 2)->nullable()->after('humedad');
        });
    }

    public function down(): void
    {
        Schema::table('condicionclimatica', function (Blueprint $table) {
            $table->dropColumn('probabilidad_lluvia');
        });
    }
};
