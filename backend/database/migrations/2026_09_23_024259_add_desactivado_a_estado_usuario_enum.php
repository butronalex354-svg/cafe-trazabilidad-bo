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
        // Laravel no soporta modificar un enum sin doctrine/dbal, se hace con SQL directo.
        DB::statement("ALTER TABLE usuario MODIFY estado ENUM('pendiente','aprobado','rechazado','desactivado') NOT NULL DEFAULT 'aprobado'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE usuario MODIFY estado ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'aprobado'");
    }
};
