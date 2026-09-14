<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RF04 - la prediccion de clima debe incluir probabilidad de lluvia, ademas
 * de temperatura y humedad (que ya existian en la tabla).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('condicionclimatica', function (Blueprint $table) {
            $table->decimal('probabilidad_lluvia', 5, 2)->nullable()->after('humedad');
        });
    }

    public function down(): void
    {
        Schema::table('condicionclimatica', function (Blueprint $table) {
            $table->dropColumn('probabilidad_lluvia');
        });
    }
};
