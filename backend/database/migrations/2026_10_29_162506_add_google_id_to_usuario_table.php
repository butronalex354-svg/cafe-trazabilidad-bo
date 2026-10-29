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
        Schema::table('usuario', function (Blueprint $table) {
            // Id de cuenta de Google (sub), para vincular/identificar cuentas
            // que inician sesion con Google en vez de email+password.
            $table->string('google_id', 191)->nullable()->unique()->after('email');
        });

        // Un usuario que SOLO usa Google nunca define una contrasena propia,
        // asi que la columna ya no puede exigir NOT NULL. "change()" de
        // Laravel necesitaria doctrine/dbal (no instalado), se usa SQL directo
        // igual que en el resto de migraciones de este proyecto.
        DB::statement('ALTER TABLE usuario MODIFY password VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE usuario MODIFY password VARCHAR(255) NOT NULL');

        Schema::table('usuario', function (Blueprint $table) {
            $table->dropColumn('google_id');
        });
    }
};
