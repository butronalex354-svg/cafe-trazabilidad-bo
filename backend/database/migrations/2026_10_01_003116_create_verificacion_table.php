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
        // Modulo 6 - Verificacion (RF14-17). Un lote (cosecha) solo tiene fila
        // aqui cuando el productor ya solicito su verificacion (no se crea
        // automaticamente como Inventario, porque no todo lote se verifica
        // de inmediato).
        Schema::create('verificacion', function (Blueprint $table) {
            $table->id();
            // cosecha.id es int(11) con signo (tabla creada fuera de Laravel).
            $table->integer('cosecha_id');
            $table->foreign('cosecha_id')->references('id')->on('cosecha')->cascadeOnDelete();
            $table->enum('estado', ['pendiente', 'observado', 'verificado', 'rechazado'])->default('pendiente');
            // usuario.id tambien es int(11) con signo.
            $table->integer('verificador_id')->nullable();
            $table->foreign('verificador_id')->references('id')->on('usuario')->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->dateTime('fecha_solicitud');
            $table->dateTime('fecha_resolucion')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verificacion');
    }
};
