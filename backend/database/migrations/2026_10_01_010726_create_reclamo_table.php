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
        // RF22: el Comprador (sin cuenta) registra un reclamo sobre un lote
        // indicando su codigo de trazabilidad, desde la pagina publica del QR.
        Schema::create('reclamo', function (Blueprint $table) {
            $table->id();
            // cosecha.id es int(11) con signo (tabla creada fuera de Laravel).
            $table->integer('cosecha_id');
            $table->foreign('cosecha_id')->references('id')->on('cosecha')->cascadeOnDelete();
            $table->string('nombre_comprador', 150)->nullable();
            $table->string('contacto_comprador', 150)->nullable();
            $table->text('mensaje');
            $table->enum('estado', ['pendiente', 'atendido'])->default('pendiente');
            $table->dateTime('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reclamo');
    }
};
