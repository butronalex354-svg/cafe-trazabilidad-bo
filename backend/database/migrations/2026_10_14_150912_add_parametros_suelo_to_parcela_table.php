<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Parametros ideales del suelo de la parcela, definidos por el Verificador
     * (experto agronomo) y usados por el sistema para comparar contra el
     * control de calidad real de cada cosecha y generar alertas (RF observado
     * en la revision de la ingeniera: el productor no debe ser el unico que
     * define estos valores).
     */
    public function up(): void
    {
        Schema::table('parcela', function (Blueprint $table) {
            $table->decimal('ph_suelo', 4, 2)->nullable()->after('tipo_suelo');
            $table->decimal('humedad_suelo', 5, 2)->nullable()->after('ph_suelo');
            $table->enum('fertilidad', ['baja', 'media', 'alta'])->nullable()->after('humedad_suelo');
            // usuario.id es int(11) con signo (tabla creada fuera de Laravel).
            $table->integer('verificador_id')->nullable()->after('fertilidad');
            $table->foreign('verificador_id')->references('id')->on('usuario')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('parcela', function (Blueprint $table) {
            $table->dropForeign(['verificador_id']);
            $table->dropColumn(['ph_suelo', 'humedad_suelo', 'fertilidad', 'verificador_id']);
        });
    }
};
