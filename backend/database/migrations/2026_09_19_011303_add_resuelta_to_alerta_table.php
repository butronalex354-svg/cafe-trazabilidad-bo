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
        // "leida" ya existia (solo significa "el productor la vio"). "resuelta"
        // es nueva: significa "el productor ya atendio la planta" — la alerta
        // sigue destacada aunque ya este leida, hasta que se marque resuelta.
        Schema::table('alerta', function (Blueprint $table) {
            $table->boolean('resuelta')->default(false)->after('leida');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alerta', function (Blueprint $table) {
            $table->dropColumn('resuelta');
        });
    }
};
