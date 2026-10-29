<?php

namespace Tests\Feature;

use App\Models\Cosecha;
use App\Models\Parcela;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrazabilidadPublicaTest extends TestCase
{
    use RefreshDatabase;

    public function test_cualquiera_puede_ver_el_historial_de_un_lote_sin_login(): void
    {
        $usuario = User::factory()->create();
        $productor = Productor::create(['usuario_id' => $usuario->id, 'nombre_completo' => $usuario->nombre]);
        $parcela = Parcela::create(['productor_id' => $productor->id, 'nombre_parcela' => 'Parcela', 'tamano_terreno' => 1]);
        $cosecha = Cosecha::create([
            'parcela_id' => $parcela->id,
            'fecha' => now()->toDateString(),
            'cantidad' => 50,
            'variedad_cafe' => 'Caturra',
        ]);

        $this->getJson("/api/trazabilidad/{$cosecha->codigo_trazabilidad}")
            ->assertStatus(200)
            ->assertJsonFragment(['codigo_trazabilidad' => $cosecha->codigo_trazabilidad]);
    }

    public function test_codigo_de_lote_inexistente_devuelve_404(): void
    {
        $this->getJson('/api/trazabilidad/LOTE-NO-EXISTE')->assertStatus(404);
    }

    public function test_comprador_puede_registrar_un_reclamo_sin_login(): void
    {
        $usuario = User::factory()->create();
        $productor = Productor::create(['usuario_id' => $usuario->id, 'nombre_completo' => $usuario->nombre]);
        $parcela = Parcela::create(['productor_id' => $productor->id, 'nombre_parcela' => 'Parcela', 'tamano_terreno' => 1]);
        $cosecha = Cosecha::create([
            'parcela_id' => $parcela->id,
            'fecha' => now()->toDateString(),
            'cantidad' => 50,
            'variedad_cafe' => 'Caturra',
        ]);

        $this->postJson("/api/trazabilidad/{$cosecha->codigo_trazabilidad}/reclamos", [
            'nombre_comprador' => 'Cliente Paraguay',
            'mensaje' => 'El lote llegó con menos peso del indicado.',
        ])->assertStatus(201);

        $this->assertDatabaseHas('reclamo', [
            'cosecha_id' => $cosecha->id,
            'estado' => 'pendiente',
        ]);
    }

    public function test_reclamo_sin_mensaje_es_rechazado(): void
    {
        $usuario = User::factory()->create();
        $productor = Productor::create(['usuario_id' => $usuario->id, 'nombre_completo' => $usuario->nombre]);
        $parcela = Parcela::create(['productor_id' => $productor->id, 'nombre_parcela' => 'Parcela', 'tamano_terreno' => 1]);
        $cosecha = Cosecha::create([
            'parcela_id' => $parcela->id,
            'fecha' => now()->toDateString(),
            'cantidad' => 50,
            'variedad_cafe' => 'Caturra',
        ]);

        $this->postJson("/api/trazabilidad/{$cosecha->codigo_trazabilidad}/reclamos", [])
            ->assertStatus(422);
    }
}
