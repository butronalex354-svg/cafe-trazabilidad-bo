<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // RF11-12: etapas de post-cosecha de un lote (despulpado, fermentacion,
        // lavado, secado, tostado, envasado, embalaje), para armar la linea de
        // tiempo del procesamiento (RF12).
        Schema::create('etapaprocesamiento', function (Blueprint $table) {
            $table->id();
            // cosecha.id es int(11) CON signo (tabla creada fuera de Laravel), igual
            // que en controlcalidad: se usa integer() normal, no foreignId().
            $table->integer('cosecha_id');
            $table->foreign('cosecha_id')->references('id')->on('cosecha')->cascadeOnDelete();
            $table->enum('etapa', ['despulpado', 'fermentacion', 'lavado', 'secado', 'tostado', 'envasado', 'embalaje']);
            $table->date('fecha');
            $table->string('responsable', 150);
            $table->text('observaciones')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etapaprocesamiento');
    }
};
