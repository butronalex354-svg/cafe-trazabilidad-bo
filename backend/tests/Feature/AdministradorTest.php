<?php

namespace Tests\Feature;

use App\Models\Cosecha;
use App\Models\Parcela;
use App\Models\Productor;
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

    public function test_administrador_elimina_una_cuenta_sin_datos_y_el_correo_queda_libre(): void
    {
        $administrador = User::factory()->administrador()->create();
        $vacio = User::factory()->pendiente()->create(['email' => 'mal.creada@cafe.bo']);
        $perfil = Productor::create(['usuario_id' => $vacio->id, 'nombre_completo' => $vacio->nombre]);

        $this->actingAs($administrador, 'sanctum')
            ->deleteJson("/api/administrador/usuarios/{$vacio->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('usuario', ['id' => $vacio->id]);
        $this->assertDatabaseMissing('productor', ['id' => $perfil->id]);
        $this->assertDatabaseHas('auditoria', ['usuario_id' => $administrador->id, 'accion' => 'eliminar_usuario']);

        // El mismo correo se puede volver a registrar
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/register', [
            'nombre' => 'De Nuevo', 'email' => 'mal.creada@cafe.bo',
            'password' => 'nueva12345', 'password_confirmation' => 'nueva12345',
            'ci' => '1234567 LP', 'telefono' => '70000000', 'direccion' => 'Caranavi',
        ])->assertStatus(201);
    }

    public function test_no_se_elimina_un_productor_con_parcelas_y_lotes(): void
    {
        $administrador = User::factory()->administrador()->create();
        $productor = User::factory()->create(['nombre' => 'Micaela']);
        $perfil = Productor::create(['usuario_id' => $productor->id, 'nombre_completo' => 'Micaela']);
        $parcela = Parcela::create(['productor_id' => $perfil->id, 'nombre_parcela' => 'La Alta', 'tamano_terreno' => 1]);
        Cosecha::create(['parcela_id' => $parcela->id, 'fecha' => now()->toDateString(), 'cantidad' => 50, 'variedad_cafe' => 'Caturra']);

        $this->actingAs($administrador, 'sanctum')
            ->deleteJson("/api/administrador/usuarios/{$productor->id}")
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'No se puede eliminar a Micaela: tiene 1 parcela y 1 lote registrados. Desactiva la cuenta en su lugar, así sus registros (y los QR de sus lotes) siguen funcionando.']);

        $this->assertDatabaseHas('usuario', ['id' => $productor->id]);
        $this->assertDatabaseHas('parcela', ['id' => $parcela->id]);
    }

    public function test_no_se_elimina_la_cuenta_propia_ni_lo_puede_hacer_un_productor(): void
    {
        $administrador = User::factory()->administrador()->create();
        $productor = User::factory()->create();
        $otro = User::factory()->create();

        $this->actingAs($administrador, 'sanctum')
            ->deleteJson("/api/administrador/usuarios/{$administrador->id}")
            ->assertStatus(422);

        $this->actingAs($productor, 'sanctum')
            ->deleteJson("/api/administrador/usuarios/{$otro->id}")
            ->assertStatus(403);
        $this->assertDatabaseHas('usuario', ['id' => $otro->id]);
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
