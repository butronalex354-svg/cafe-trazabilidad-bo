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
        // RF13: stock disponible de cada lote, con su estado dentro del ciclo
        // (en proceso -> terminado -> exportado). Se crea automaticamente un
        // registro por cada cosecha nueva (ver Cosecha::booted()).
        Schema::create('inventario', function (Blueprint $table) {
            $table->id();
            // cosecha.id es int(11) CON signo (tabla creada fuera de Laravel),
            // igual que en controlcalidad/etapaprocesamiento.
            $table->integer('cosecha_id');
            $table->foreign('cosecha_id')->references('id')->on('cosecha')->cascadeOnDelete();
            $table->decimal('cantidad_disponible', 10, 2);
            $table->enum('estado', ['en_proceso', 'terminado', 'exportado'])->default('en_proceso');
            $table->date('fecha_actualizacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario');
    }
};
