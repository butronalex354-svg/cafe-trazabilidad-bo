<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Borde de la parcela dibujado en el mapa: lista de puntos [lat, lng].
     * Permite mostrar el area real del terreno (no solo un punto) en la
     * pagina publica del lote y en las demas vistas del mapa.
     */
    public function up(): void
    {
        Schema::table('parcela', function (Blueprint $table) {
            $table->json('poligono')->nullable()->after('ubicacion');
        });
    }

    public function down(): void
    {
        Schema::table('parcela', function (Blueprint $table) {
            $table->dropColumn('poligono');
        });
    }
};
