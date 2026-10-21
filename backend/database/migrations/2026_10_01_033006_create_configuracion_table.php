<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Configuracion global del sistema (una sola fila). Hoy solo guarda
        // el color de marca que el Administrador puede personalizar y que
        // se aplica para todos los usuarios.
        Schema::create('configuracion', function (Blueprint $table) {
            $table->id();
            $table->string('color_primario', 7)->default('#e0a84a');
        });

        DB::table('configuracion')->insert(['id' => 1, 'color_primario' => '#e0a84a']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracion');
    }
};
