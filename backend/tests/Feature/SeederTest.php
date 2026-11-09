<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_el_primer_administrador_una_sola_vez(): void
    {
        config(['app.admin_inicial' => ['nombre' => 'Admin Café', 'email' => 'jefe@cafe.bo', 'password' => 'Clave-Segura-2026']]);

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'jefe@cafe.bo')->sole();
        $this->assertSame('administrador', $admin->rol);
        $this->assertSame('aprobado', $admin->estado);
        $this->assertTrue(Hash::check('Clave-Segura-2026', $admin->password));
    }

    public function test_sin_datos_en_env_no_crea_nada(): void
    {
        config(['app.admin_inicial' => ['nombre' => 'Administrador', 'email' => null, 'password' => null]]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, User::count());
    }
}
