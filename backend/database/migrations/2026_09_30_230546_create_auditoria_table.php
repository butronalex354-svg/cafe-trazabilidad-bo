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
        Schema::create('auditoria', function (Blueprint $table) {
            $table->id();
            // usuario.id es int(11) firmado (tabla creada fuera de Laravel), no
            // bigint unsigned, asi que se usa integer() en vez de foreignId()
            // para que el tipo coincida y la FK no tire error 150.
            // Quien ejecuto la accion (el administrador). Nullable por si algun
            // dia el sistema mismo registra una accion automatica.
            $table->integer('usuario_id')->nullable();
            $table->foreign('usuario_id')->references('id')->on('usuario')->nullOnDelete();
            // Sobre quien se aplico la accion (ej. la cuenta aprobada/desactivada).
            // Puede ser la misma persona que ejecuta (ej. edita su propia cuenta).
            $table->integer('usuario_afectado_id')->nullable();
            $table->foreign('usuario_afectado_id')->references('id')->on('usuario')->nullOnDelete();
            $table->string('accion', 60);
            $table->text('descripcion');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditoria');
    }
};
