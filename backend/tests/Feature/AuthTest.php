<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registro_crea_productor_pendiente_sin_token(): void
    {
        $response = $this->postJson('/api/register', [
            'nombre' => 'Juan Mamani',
            'email' => 'juan@cafe.bo',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'ci' => '1234567 LP',
            'telefono' => '70000000',
            'direccion' => 'Caranavi, La Paz',
        ]);

        $response->assertStatus(201)->assertJsonMissing(['token']);

        $usuario = User::where('email', 'juan@cafe.bo')->first();
        $this->assertNotNull($usuario);
        $this->assertSame('pendiente', $usuario->estado);
        $this->assertSame('productor', $usuario->rol);
        $this->assertNotNull($usuario->productor);
    }

    public function test_login_bloqueado_mientras_la_cuenta_esta_pendiente(): void
    {
        User::factory()->pendiente()->create(['email' => 'pendiente@cafe.bo', 'password' => 'password123']);

        $response = $this->postJson('/api/login', [
            'email' => 'pendiente@cafe.bo',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
    }

    public function test_login_bloqueado_si_la_cuenta_esta_rechazada_o_desactivada(): void
    {
        User::factory()->rechazado()->create(['email' => 'rechazado@cafe.bo', 'password' => 'password123']);
        User::factory()->desactivado()->create(['email' => 'desactivado@cafe.bo', 'password' => 'password123']);

        $this->postJson('/api/login', ['email' => 'rechazado@cafe.bo', 'password' => 'password123'])
            ->assertStatus(403);

        $this->postJson('/api/login', ['email' => 'desactivado@cafe.bo', 'password' => 'password123'])
            ->assertStatus(403);
    }

    public function test_login_exitoso_devuelve_token_para_cuenta_aprobada(): void
    {
        User::factory()->create(['email' => 'productor@cafe.bo', 'password' => 'password123']);

        $response = $this->postJson('/api/login', [
            'email' => 'productor@cafe.bo',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)->assertJsonStructure(['token', 'user' => ['id', 'rol']]);
    }

    public function test_login_rechaza_password_incorrecta(): void
    {
        User::factory()->create(['email' => 'productor@cafe.bo', 'password' => 'password123']);

        $this->postJson('/api/login', [
            'email' => 'productor@cafe.bo',
            'password' => 'otra-password',
        ])->assertStatus(401);
    }

    public function test_login_se_bloquea_despues_de_6_intentos_por_minuto(): void
    {
        User::factory()->create(['email' => 'productor@cafe.bo', 'password' => 'password123']);

        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/login', ['email' => 'productor@cafe.bo', 'password' => 'mala'])
                ->assertStatus(401);
        }

        $this->postJson('/api/login', ['email' => 'productor@cafe.bo', 'password' => 'password123'])
            ->assertStatus(429);
    }

    public function test_login_normal_no_explota_si_la_cuenta_no_tiene_password_google(): void
    {
        User::factory()->create(['email' => 'solo_google@cafe.bo', 'password' => null, 'google_id' => '123456789']);

        $this->postJson('/api/login', [
            'email' => 'solo_google@cafe.bo',
            'password' => 'cualquiera123',
        ])->assertStatus(401);
    }

    public function test_registro_rechaza_password_debil(): void
    {
        $response = $this->postJson('/api/register', [
            'nombre' => 'Juan Mamani',
            'email' => 'juan@cafe.bo',
            'password' => 'sololetras',
            'password_confirmation' => 'sololetras',
            'ci' => '1234567 LP',
            'telefono' => '70000000',
            'direccion' => 'Caranavi, La Paz',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('password');
    }
}
