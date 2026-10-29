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
        // Seguimiento del envio de un lote ya exportado: cada fila es un
        // estado nuevo en la linea de tiempo (listo_transporte -> en_transito
        // -> en_aduana -> entregado), cargado manualmente por el Administrador.
        Schema::create('envio', function (Blueprint $table) {
            $table->id();
            // cosecha.id es int(11) con signo (tabla creada fuera de Laravel).
            $table->integer('cosecha_id');
            $table->foreign('cosecha_id')->references('id')->on('cosecha')->cascadeOnDelete();
            $table->enum('estado', ['listo_transporte', 'en_transito', 'en_aduana', 'entregado']);
            $table->date('fecha');
            $table->string('nota', 255)->nullable();
            $table->dateTime('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('envio');
    }
};
