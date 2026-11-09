<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Crea el primer Administrador del sistema (datos en backend/.env:
     * ADMIN_NOMBRE, ADMIN_EMAIL, ADMIN_PASSWORD). Sin el, en una instalacion
     * nueva nadie podria aprobar las cuentas de los productores.
     * Las variedades de cafe ya las carga su propia migracion.
     */
    public function run(): void
    {
        $admin = config('app.admin_inicial');

        if (empty($admin['email']) || empty($admin['password'])) {
            $this->command->warn('Falta ADMIN_EMAIL o ADMIN_PASSWORD en backend/.env: no se creó el administrador.');

            return;
        }

        $usuario = User::firstOrCreate(
            ['email' => $admin['email']],
            [
                'nombre' => $admin['nombre'] ?: 'Administrador',
                'password' => $admin['password'],
                'rol' => 'administrador',
                'estado' => 'aprobado',
            ]
        );

        $this->command->info($usuario->wasRecentlyCreated
            ? "Administrador creado: {$usuario->email}"
            : "El administrador {$usuario->email} ya existía, no se cambió.");
    }
}
