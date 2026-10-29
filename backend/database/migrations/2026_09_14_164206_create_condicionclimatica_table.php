<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RF04 - Prediccion de clima por parcela.
 *
 * NOTA: la tabla `condicionclimatica` ya existia en la base de datos (creada
 * directamente en MySQL/phpMyAdmin durante el diseno del modelo ER). Esta
 * migracion documenta esa estructura original dentro del proyecto Laravel;
 * no se ejecuta con `php artisan migrate` porque la tabla ya existe. El campo
 * `probabilidad_lluvia` se agrega aparte, en una migracion que si es nueva
 * de verdad y si se ejecuta.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('condicionclimatica')) {
            return;
        }

        Schema::create('condicionclimatica', function (Blueprint $table) {
            // int(11) con signo, igual que parcela (ver nota en
            // create_productor_table.php).
            $table->integer('id', true);
            $table->integer('parcela_id');
            $table->foreign('parcela_id')->references('id')->on('parcela');
            $table->date('fecha')->nullable();
            $table->decimal('temperatura', 5, 2)->nullable();
            $table->decimal('humedad', 5, 2)->nullable();
            // Normalmente la agrega add_probabilidad_lluvia_to_condicionclimatica_table,
            // pero esa migracion quedo fechada antes que esta (ver su propio
            // comentario) y en un entorno nuevo corre primero sin poder
            // hacer nada, asi que se incluye directo aca tambien.
            $table->decimal('probabilidad_lluvia', 5, 2)->nullable();
            $table->string('fuente', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condicionclimatica');
    }
};
