<?php

namespace Tests\Feature;

use App\Models\Cosecha;
use App\Models\Parcela;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificacionExportacionTest extends TestCase
{
    use RefreshDatabase;

    private function crearLote(): array
    {
        $usuarioProductor = User::factory()->create();
        $productor = Productor::create(['usuario_id' => $usuarioProductor->id, 'nombre_completo' => $usuarioProductor->nombre]);
        $parcela = Parcela::create(['productor_id' => $productor->id, 'nombre_parcela' => 'Parcela', 'tamano_terreno' => 1]);
        $cosecha = Cosecha::create([
            'parcela_id' => $parcela->id,
            'fecha' => now()->toDateString(),
            'cantidad' => 80,
            'variedad_cafe' => 'Caturra',
        ]);

        return [$usuarioProductor, $cosecha];
    }

    public function test_flujo_completo_solicitar_verificar_y_autorizar_exportacion(): void
    {
        [$usuarioProductor, $cosecha] = $this->crearLote();
        $verificador = User::factory()->verificador()->create();
        $administrador = User::factory()->administrador()->create();

        // El productor solicita verificacion de su lote.
        $this->actingAs($usuarioProductor, 'sanctum')
            ->postJson("/api/cosechas/{$cosecha->id}/solicitar-verificacion")
            ->assertStatus(201);

        $verificacion = $cosecha->verificacion()->first();
        $this->assertSame('pendiente', $verificacion->estado);

        // El verificador la aprueba.
        $this->actingAs($verificador, 'sanctum')
            ->putJson("/api/verificador/solicitudes/{$verificacion->id}", ['estado' => 'verificado'])
            ->assertStatus(200);

        // Ahora el administrador puede ver el lote listo para exportar y autorizarlo.
        $this->actingAs($administrador, 'sanctum')
            ->getJson('/api/administrador/lotes-para-exportar')
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $cosecha->id]);

        $this->actingAs($administrador, 'sanctum')
            ->putJson("/api/administrador/lotes/{$cosecha->id}/autorizar-exportacion")
            ->assertStatus(200);

        $this->assertDatabaseHas('inventario', [
            'cosecha_id' => $cosecha->id,
            'estado' => 'exportado',
        ]);
    }

    public function test_no_se_puede_exportar_un_lote_que_no_fue_verificado(): void
    {
        [, $cosecha] = $this->crearLote();
        $administrador = User::factory()->administrador()->create();

        $this->actingAs($administrador, 'sanctum')
            ->putJson("/api/administrador/lotes/{$cosecha->id}/autorizar-exportacion")
            ->assertStatus(422);
    }

    public function test_lote_rechazado_no_se_puede_reenviar_a_verificacion(): void
    {
        [$usuarioProductor, $cosecha] = $this->crearLote();
        $verificador = User::factory()->verificador()->create();

        $this->actingAs($usuarioProductor, 'sanctum')
            ->postJson("/api/cosechas/{$cosecha->id}/solicitar-verificacion")
            ->assertStatus(201);

        $verificacion = $cosecha->verificacion()->first();

        $this->actingAs($verificador, 'sanctum')
            ->putJson("/api/verificador/solicitudes/{$verificacion->id}", ['estado' => 'rechazado'])
            ->assertStatus(200);

        $this->actingAs($usuarioProductor, 'sanctum')
            ->postJson("/api/cosechas/{$cosecha->id}/solicitar-verificacion")
            ->assertStatus(422);
    }

    public function test_solo_el_rol_verificador_puede_resolver_solicitudes(): void
    {
        [$usuarioProductor, $cosecha] = $this->crearLote();

        $this->actingAs($usuarioProductor, 'sanctum')
            ->postJson("/api/cosechas/{$cosecha->id}/solicitar-verificacion")
            ->assertStatus(201);

        $verificacion = $cosecha->verificacion()->first();

        // El propio productor (no es verificador) no puede resolver.
        $this->actingAs($usuarioProductor, 'sanctum')
            ->putJson("/api/verificador/solicitudes/{$verificacion->id}", ['estado' => 'verificado'])
            ->assertStatus(403);
    }

    public function test_solo_el_rol_administrador_puede_autorizar_exportacion(): void
    {
        [$usuarioProductor, $cosecha] = $this->crearLote();

        $this->actingAs($usuarioProductor, 'sanctum')
            ->putJson("/api/administrador/lotes/{$cosecha->id}/autorizar-exportacion")
            ->assertStatus(403);
    }
}
