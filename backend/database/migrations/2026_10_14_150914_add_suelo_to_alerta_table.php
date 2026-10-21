<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La tabla alerta ya cubre plaga y stock; se generaliza para que tambien
     * pueda avisar sobre parametros de suelo fuera de rango (ej. pH muy alto),
     * comparando el control de calidad de la cosecha contra los parametros
     * ideales definidos por el Verificador en la parcela.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE alerta MODIFY tipo ENUM('plaga','stock','suelo') DEFAULT 'plaga'");

        Schema::table('alerta', function (Blueprint $table) {
            // parcela.id es int(11) con signo (tabla creada fuera de Laravel).
            $table->integer('parcela_id')->nullable()->after('inventario_id');
            $table->foreign('parcela_id')->references('id')->on('parcela')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('alerta', function (Blueprint $table) {
            $table->dropForeign(['parcela_id']);
            $table->dropColumn('parcela_id');
        });

        DB::statement("ALTER TABLE alerta MODIFY tipo ENUM('plaga','stock') DEFAULT 'plaga'");
    }
};
