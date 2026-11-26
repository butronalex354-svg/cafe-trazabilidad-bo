<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdministradorTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrador_puede_aprobar_una_cuenta_pendiente(): void
    {
        $administrador = User::factory()->administrador()->create();
        $pendiente = User::factory()->pendiente()->create();

        $this->actingAs($administrador, 'sanctum')
            ->putJson("/api/administrador/usuarios/{$pendiente->id}/aprobar")
            ->assertStatus(200);

        $this->assertDatabaseHas('usuario', ['id' => $pendiente->id, 'estado' => 'aprobado']);
    }

    public function test_cuenta_recien_aprobada_ya_puede_iniciar_sesion(): void
    {
        $administrador = User::factory()->administrador()->create();
        $pendiente = User::factory()->pendiente()->create(['email' => 'nuevo@cafe.bo', 'password' => 'password123']);

        $this->actingAs($administrador, 'sanctum')
            ->putJson("/api/administrador/usuarios/{$pendiente->id}/aprobar")
            ->assertStatus(200);

        $this->postJson('/api/login', ['email' => 'nuevo@cafe.bo', 'password' => 'password123'])
            ->assertStatus(200);
    }

    public function test_administrador_puede_crear_cuenta_de_verificador_ya_aprobada(): void
    {
        $administrador = User::factory()->administrador()->create();

        $response = $this->actingAs($administrador, 'sanctum')->postJson('/api/administrador/usuarios', [
            'nombre' => 'Ing. Verificadora',
            'email' => 'verificadora@cafe.bo',
            'password' => 'password123',
            'rol' => 'verificador',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('usuario', [
            'email' => 'verificadora@cafe.bo',
            'rol' => 'verificador',
            'estado' => 'aprobado',
        ]);
    }

    public function test_un_productor_no_puede_acceder_a_los_endpoints_de_administrador(): void
    {
        $productor = User::factory()->create();

        $this->actingAs($productor, 'sanctum')
            ->getJson('/api/administrador/usuarios')
            ->assertStatus(403);
    }

    public function test_administrador_restablece_la_contrasena_olvidada_de_un_productor(): void
    {
        $administrador = User::factory()->administrador()->create();
        $productor = User::factory()->create(['email' => 'olvido@cafe.bo', 'password' => 'vieja12345']);
        $productor->createToken('celular');

        $this->actingAs($administrador, 'sanctum')
            ->putJson("/api/administrador/usuarios/{$productor->id}/password", ['password' => 'nueva12345'])
            ->assertStatus(200);

        // Las sesiones que tenia abiertas se cierran
        $this->assertSame(0, $productor->tokens()->count());
        // Queda en la auditoria, sin la contraseña
        $this->assertDatabaseHas('auditoria', ['usuario_afectado_id' => $productor->id, 'accion' => 'restablecer_password']);
        $this->assertDatabaseMissing('auditoria', ['descripcion' => 'nueva12345']);

        // Con la nueva entra; con la vieja ya no
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/login', ['email' => 'olvido@cafe.bo', 'password' => 'nueva12345'])->assertStatus(200);
        $this->postJson('/api/login', ['email' => 'olvido@cafe.bo', 'password' => 'vieja12345'])->assertStatus(401);
    }

    public function test_restablecer_contrasena_rechaza_claves_debiles_y_la_cuenta_propia(): void
    {
        $administrador = User::factory()->administrador()->create();
        $productor = User::factory()->create();

        $this->actingAs($administrador, 'sanctum')
            ->putJson("/api/administrador/usuarios/{$productor->id}/password", ['password' => 'micaela'])
            ->assertStatus(422);

        $this->actingAs($administrador, 'sanctum')
            ->putJson("/api/administrador/usuarios/{$administrador->id}/password", ['password' => 'otra12345'])
            ->assertStatus(422);
    }

    public function test_un_productor_no_puede_restablecer_contrasenas_ajenas(): void
    {
        $productor = User::factory()->create();
        $otro = User::factory()->create();

        $this->actingAs($productor, 'sanctum')
            ->putJson("/api/administrador/usuarios/{$otro->id}/password", ['password' => 'robada12345'])
            ->assertStatus(403);
    }

    public function test_un_verificador_no_puede_acceder_a_los_endpoints_de_administrador(): void
    {
        $verificador = User::factory()->verificador()->create();

        $this->actingAs($verificador, 'sanctum')
            ->getJson('/api/administrador/reportes')
            ->assertStatus(403);
    }
}
