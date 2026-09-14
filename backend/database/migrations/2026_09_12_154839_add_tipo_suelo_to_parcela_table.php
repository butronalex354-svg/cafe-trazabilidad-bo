<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RF02 - agrega el tipo de suelo al registro de la parcela.
 * Esta migracion si es nueva de verdad y se ejecuta con `php artisan migrate`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parcela', function (Blueprint $table) {
            $table->string('tipo_suelo', 100)->nullable()->after('ubicacion');
        });
    }

    public function down(): void
    {
        Schema::table('parcela', function (Blueprint $table) {
            $table->dropColumn('tipo_suelo');
        });
    }
};
