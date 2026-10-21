<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rendimiento por hectarea de la cosecha (cantidad_kg / tamano_terreno de
     * la parcela), para evaluar la productividad del lote.
     */
    public function up(): void
    {
        Schema::table('cosecha', function (Blueprint $table) {
            $table->decimal('rendimiento_kg_ha', 10, 2)->nullable()->after('cantidad');
        });
    }

    public function down(): void
    {
        Schema::table('cosecha', function (Blueprint $table) {
            $table->dropColumn('rendimiento_kg_ha');
        });
    }
};
