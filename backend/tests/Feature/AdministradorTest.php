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

    public function test_un_verificador_no_puede_acceder_a_los_endpoints_de_administrador(): void
    {
        $verificador = User::factory()->verificador()->create();

        $this->actingAs($verificador, 'sanctum')
            ->getJson('/api/administrador/reportes')
            ->assertStatus(403);
    }
}
