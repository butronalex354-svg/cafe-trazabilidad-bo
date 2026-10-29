<?php

namespace Tests\Feature;

use App\Models\Parcela;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProduccionTest extends TestCase
{
    use RefreshDatabase;

    private function productorAutenticado(): User
    {
        $usuario = User::factory()->create();
        Productor::create(['usuario_id' => $usuario->id, 'nombre_completo' => $usuario->nombre]);

        return $usuario;
    }

    public function test_productor_puede_crear_y_listar_su_parcela(): void
    {
        $usuario = $this->productorAutenticado();

        $this->actingAs($usuario, 'sanctum')
            ->postJson('/api/parcelas', [
                'nombre_parcela' => 'Parcela Alta',
                'ubicacion' => '-16.1, -67.5',
                'tamano_terreno' => 2.5,
            ])->assertStatus(201)->assertJsonFragment(['nombre_parcela' => 'Parcela Alta']);

        $this->actingAs($usuario, 'sanctum')
            ->getJson('/api/parcelas')
            ->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_cosecha_genera_codigo_de_trazabilidad_y_entra_a_inventario(): void
    {
        $usuario = $this->productorAutenticado();
        $productor = $usuario->productor;
        $parcela = Parcela::create([
            'productor_id' => $productor->id,
            'nombre_parcela' => 'Parcela Baja',
            'tamano_terreno' => 1,
        ]);

        $response = $this->actingAs($usuario, 'sanctum')->postJson('/api/cosechas', [
            'parcela_id' => $parcela->id,
            'fecha' => now()->toDateString(),
            'cantidad' => 100,
            'variedad_cafe' => 'Caturra',
        ]);

        $response->assertStatus(201);
        $codigo = $response->json('codigo_trazabilidad');
        $this->assertStringStartsWith('LOTE-', $codigo);

        $this->assertDatabaseHas('inventario', [
            'cosecha_id' => $response->json('id'),
            'estado' => 'en_proceso',
            'cantidad_disponible' => 100,
        ]);
    }

    public function test_productor_no_puede_ver_ni_editar_parcelas_de_otro_productor(): void
    {
        $dueno = $this->productorAutenticado();
        $parcelaAjena = Parcela::create([
            'productor_id' => $dueno->productor->id,
            'nombre_parcela' => 'No es tuya',
            'tamano_terreno' => 1,
        ]);

        $otro = $this->productorAutenticado();

        $this->actingAs($otro, 'sanctum')
            ->putJson("/api/parcelas/{$parcelaAjena->id}", ['nombre_parcela' => 'Hackeada'])
            ->assertStatus(404);

        $this->actingAs($otro, 'sanctum')
            ->getJson('/api/parcelas')
            ->assertStatus(200)
            ->assertJsonCount(0);
    }

    public function test_no_se_puede_eliminar_parcela_con_cosechas_registradas(): void
    {
        $usuario = $this->productorAutenticado();
        $parcela = Parcela::create([
            'productor_id' => $usuario->productor->id,
            'nombre_parcela' => 'Con lotes',
            'tamano_terreno' => 1,
        ]);

        $this->actingAs($usuario, 'sanctum')->postJson('/api/cosechas', [
            'parcela_id' => $parcela->id,
            'fecha' => now()->toDateString(),
            'cantidad' => 50,
            'variedad_cafe' => 'Typica',
        ])->assertStatus(201);

        $this->actingAs($usuario, 'sanctum')
            ->deleteJson("/api/parcelas/{$parcela->id}")
            ->assertStatus(422);
    }

    public function test_rutas_de_produccion_requieren_autenticacion(): void
    {
        $this->getJson('/api/parcelas')->assertStatus(401);
    }
}
