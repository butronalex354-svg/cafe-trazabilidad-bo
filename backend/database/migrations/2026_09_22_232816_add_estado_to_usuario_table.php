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
        Schema::table('usuario', function (Blueprint $table) {
            // Los productores nuevos quedan "pendiente" hasta que el Administrador
            // los aprueba (RF25). Verificador/Administrador se crean directo como
            // "aprobado" porque no se auto-registran desde el formulario publico.
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('aprobado')->after('rol');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};
