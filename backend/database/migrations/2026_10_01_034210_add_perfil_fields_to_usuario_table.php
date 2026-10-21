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
            // "Mi perfil": telefono para los 3 roles.
            $table->string('telefono', 20)->nullable()->after('email');
            // "Datos de verificacion": solo tiene sentido para el rol Verificador,
            // pero se deja nullable y generico para no crear una tabla aparte
            // por 2 campos opcionales de un solo rol.
            $table->string('entidad_verificadora', 150)->nullable()->after('telefono');
            $table->string('acreditacion', 100)->nullable()->after('entidad_verificadora');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->dropColumn(['telefono', 'entidad_verificadora', 'acreditacion']);
        });
    }
};
