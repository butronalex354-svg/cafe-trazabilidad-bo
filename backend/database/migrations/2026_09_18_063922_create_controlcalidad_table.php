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
        // RF10: control de calidad del lote (humedad, defectos, clasificacion).
        Schema::create('controlcalidad', function (Blueprint $table) {
            $table->id();
            // cosecha.id es int(11) CON signo (no bigint unsigned, tabla creada fuera de
            // Laravel), por eso se usa integer() normal en vez de foreignId()/unsignedInteger()
            // para que el tipo calce exacto y la FK no truene con error 150.
            $table->integer('cosecha_id');
            $table->foreign('cosecha_id')->references('id')->on('cosecha')->cascadeOnDelete();
            $table->date('fecha');
            $table->decimal('humedad', 5, 2)->nullable(); // porcentaje de humedad del grano
            $table->integer('defectos')->nullable(); // cantidad de granos defectuosos encontrados
            $table->enum('clasificacion', ['primera', 'segunda', 'tercera', 'exportacion'])->nullable();
            $table->text('observaciones')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('controlcalidad');
    }
};
