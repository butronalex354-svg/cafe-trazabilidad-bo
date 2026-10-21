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
        // Catalogo del sistema (administrable por el Administrador): variedades
        // de cafe disponibles para elegir al registrar una cosecha. El campo
        // "variedad_cafe" de la tabla cosecha sigue siendo texto libre (para no
        // romper lo ya registrado), este catalogo solo alimenta el selector.
        Schema::create('variedad_cafe', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->boolean('activo')->default(true);
        });

        // Variedades reales ya mencionadas en el documento de requerimientos
        // y usadas en los lotes de prueba del sistema.
        DB::table('variedad_cafe')->insert([
            ['nombre' => 'Caturra', 'activo' => true],
            ['nombre' => 'Típica', 'activo' => true],
            ['nombre' => 'Catuaí', 'activo' => true],
            ['nombre' => 'Bourbon', 'activo' => true],
            ['nombre' => 'Geisha', 'activo' => true],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variedad_cafe');
    }
};
