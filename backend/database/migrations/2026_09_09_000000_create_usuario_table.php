<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NOTA: la tabla `usuario` ya existia en la base de datos (fue creada
 * directamente en MySQL/phpMyAdmin antes de programar el backend, junto con
 * productor/parcela/cosecha). Esta migracion documenta su estructura real
 * dentro del proyecto Laravel -- antes no existia NINGUNA migracion para
 * esta tabla, asi que un entorno nuevo (`php artisan migrate` desde cero)
 * no podia recrearla. No se ejecuta en la BD real porque la tabla ya existe
 * (el guard de abajo la salta).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('usuario')) {
            return;
        }

        // Solo las columnas ORIGINALES: estado/notificar_*/telefono/
        // entidad_verificadora/acreditacion los agregan, en orden, las
        // migraciones reales que ya existian (add_estado_to_usuario_table,
        // add_preferencias_notificacion_to_usuario_table,
        // add_perfil_fields_to_usuario_table) -- se dejan que corran ellas,
        // en vez de duplicar esas columnas aca.
        Schema::create('usuario', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('nombre', 150);
            $table->string('email', 150)->unique();
            $table->string('password', 255);
            $table->enum('rol', ['productor', 'verificador', 'administrador']);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario');
    }
};
