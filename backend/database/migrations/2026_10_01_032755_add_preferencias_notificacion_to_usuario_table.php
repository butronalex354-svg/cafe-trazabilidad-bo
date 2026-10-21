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
        // Preferencias de notificacion del propio usuario (que tipos de
        // alerta quiere ver). Por defecto todo activado, para no cambiar el
        // comportamiento de ninguna cuenta ya existente.
        Schema::table('usuario', function (Blueprint $table) {
            $table->boolean('notificar_plagas')->default(true)->after('estado');
            $table->boolean('notificar_stock')->default(true)->after('notificar_plagas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->dropColumn(['notificar_plagas', 'notificar_stock']);
        });
    }
};
