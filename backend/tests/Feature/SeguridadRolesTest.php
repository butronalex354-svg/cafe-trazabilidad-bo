<?php

namespace Tests\Feature;

use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guardia central de roles (middleware "rol") y corte de sesion de cuentas
 * desactivadas (middleware "cuenta.activa").
 */
class SeguridadRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_verificador_no_puede_usar_rutas_del_productor(): void
    {
        $verificador = User::factory()->verificador()->create();

        $this->actingAs($verificador, 'sanctum')->getJson('/api/parcelas')->assertStatus(403);
        $this->actingAs($verificador, 'sanctum')->getJson('/api/productor/perfil')->assertStatus(403);

        // Antes se le creaba una fila de productor "fantasma" al entrar a estas rutas.
        $this->assertFalse(Productor::where('usuario_id', $verificador->id)->exists());
    }

    public function test_administrador_no_puede_usar_rutas_del_productor(): void
    {
        $administrador = User::factory()->administrador()->create();

        $this->actingAs($administrador, 'sanctum')
            ->postJson('/api/parcelas', ['nombre_parcela' => 'X', 'tamano_terreno' => 1])
            ->assertStatus(403);
    }

    public function test_productor_no_puede_usar_rutas_de_verificador_ni_de_administrador(): void
    {
        $productor = User::factory()->create();

        $this->actingAs($productor, 'sanctum')->getJson('/api/verificador/solicitudes')->assertStatus(403);
        $this->actingAs($productor, 'sanctum')->getJson('/api/administrador/usuarios')->assertStatus(403);
        $this->actingAs($productor, 'sanctum')->putJson('/api/configuracion', [])->assertStatus(403);
    }

    public function test_rutas_comunes_siguen_abiertas_para_los_tres_roles(): void
    {
        foreach ([User::factory()->create(), User::factory()->verificador()->create(), User::factory()->administrador()->create()] as $usuario) {
            $this->actingAs($usuario, 'sanctum')->getJson('/api/me')->assertOk();
            $this->actingAs($usuario, 'sanctum')->getJson('/api/catalogos/variedades-cafe')->assertOk();
        }
    }

    public function test_cuenta_desactivada_pierde_la_sesion_al_instante(): void
    {
        $productor = User::factory()->create();
        $administrador = User::factory()->administrador()->create();
        $cabecera = ['Authorization' => 'Bearer '.$productor->createToken('test')->plainTextToken];

        $this->getJson('/api/me', $cabecera)->assertOk();
        $this->app['auth']->forgetGuards();

        $this->actingAs($administrador, 'sanctum')
            ->putJson("/api/administrador/usuarios/{$productor->id}/desactivar")
            ->assertOk();
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/me', $cabecera)->assertStatus(401);
        $this->assertSame(0, $productor->tokens()->count());
    }

    public function test_cuenta_marcada_como_desactivada_con_token_vigente_recibe_401(): void
    {
        $productor = User::factory()->create();
        $cabecera = ['Authorization' => 'Bearer '.$productor->createToken('test')->plainTextToken];
        $productor->forceFill(['estado' => 'desactivado'])->save();

        $this->getJson('/api/parcelas', $cabecera)->assertStatus(401);
        $this->assertSame(0, $productor->tokens()->count());
    }

    public function test_administrador_no_puede_desactivarse_a_si_mismo(): void
    {
        $administrador = User::factory()->administrador()->create();

        $this->actingAs($administrador, 'sanctum')
            ->putJson("/api/administrador/usuarios/{$administrador->id}/desactivar")
            ->assertStatus(422);

        $this->assertSame('aprobado', $administrador->fresh()->estado);
    }
}
