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
        // Stock minimo/maximo por lote (Marco Teorico - Gestion de Inventarios).
        Schema::table('inventario', function (Blueprint $table) {
            $table->decimal('stock_minimo', 10, 2)->nullable()->after('cantidad_disponible');
            $table->decimal('stock_maximo', 10, 2)->nullable()->after('stock_minimo');
        });

        // La tabla alerta hoy solo sirve para plagas (diagnostico_id obligatorio).
        // Se generaliza para que tambien pueda avisar desabastecimiento de stock.
        Schema::table('alerta', function (Blueprint $table) {
            $table->enum('tipo', ['plaga', 'stock'])->default('plaga')->after('id');
            // inventario.id SI es bigint unsigned (tabla creada por Laravel,
            // a diferencia de usuario/cosecha que son int(11) con signo).
            $table->unsignedBigInteger('inventario_id')->nullable()->after('diagnostico_id');
            $table->foreign('inventario_id')->references('id')->on('inventario')->cascadeOnDelete();
        });
        // "change()" en diagnostico_id necesitaria doctrine/dbal (no instalado),
        // asi que se usa SQL directo para permitir NULL (ya es int(11) sin mas
        // cambios de tipo, solo se quita el NOT NULL).
        DB::statement('ALTER TABLE alerta MODIFY diagnostico_id INT(11) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alerta', function (Blueprint $table) {
            $table->dropForeign(['inventario_id']);
            $table->dropColumn(['tipo', 'inventario_id']);
        });
        DB::statement('ALTER TABLE alerta MODIFY diagnostico_id INT(11) NOT NULL');

        Schema::table('inventario', function (Blueprint $table) {
            $table->dropColumn(['stock_minimo', 'stock_maximo']);
        });
    }
};
