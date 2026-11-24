<?php

namespace Tests\Feature;

use App\Models\Parcela;
use App\Models\Productor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DiagnosticoTest extends TestCase
{
    use RefreshDatabase;

    private function productorConParcela(): array
    {
        $usuario = User::factory()->create();
        $productor = Productor::create(['usuario_id' => $usuario->id, 'nombre_completo' => $usuario->nombre]);
        $parcela = Parcela::create(['productor_id' => $productor->id, 'nombre_parcela' => 'Parcela', 'tamano_terreno' => 1]);

        return [$usuario, $parcela];
    }

    public function test_diagnostico_de_plaga_genera_una_alerta_automatica(): void
    {
        Storage::fake('public');
        Http::fake([
            '*/diagnosticar' => Http::response([
                'resultado' => 'plaga',
                'confianza' => 92.5,
                'reconocido' => true,
            ], 200),
        ]);

        [$usuario, $parcela] = $this->productorConParcela();

        $response = $this->actingAs($usuario, 'sanctum')->postJson('/api/diagnosticos', [
            'parcela_id' => $parcela->id,
            'foto' => UploadedFile::fake()->image('hoja.jpg'),
        ]);

        $response->assertStatus(201)->assertJsonFragment(['resultado' => 'plaga']);

        $this->assertDatabaseHas('alerta', [
            'diagnostico_id' => $response->json('id'),
        ]);
    }

    public function test_diagnostico_de_noche_usa_la_fecha_de_bolivia(): void
    {
        // 00:30 en UTC del 24/11 son las 20:30 del 23/11 en Bolivia (UTC-4):
        // la fecha guardada debe ser la del 23, no la del dia siguiente.
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-24 00:30:00', 'UTC'));
        Storage::fake('public');
        Http::fake([
            '*/diagnosticar' => Http::response([
                'resultado' => 'enfermedad',
                'confianza' => 80,
                'reconocido' => true,
            ], 200),
        ]);

        [$usuario, $parcela] = $this->productorConParcela();

        $response = $this->actingAs($usuario, 'sanctum')->postJson('/api/diagnosticos', [
            'parcela_id' => $parcela->id,
            'foto' => UploadedFile::fake()->image('hoja.jpg'),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('diagnostico', ['id' => $response->json('id'), 'fecha' => '2026-11-23']);
        $this->assertDatabaseHas('alerta', ['diagnostico_id' => $response->json('id'), 'fecha' => '2026-11-23']);
    }

    public function test_diagnostico_sano_no_genera_alerta(): void
    {
        Storage::fake('public');
        Http::fake([
            '*/diagnosticar' => Http::response([
                'resultado' => 'sana',
                'confianza' => 88,
                'reconocido' => true,
            ], 200),
        ]);

        [$usuario, $parcela] = $this->productorConParcela();

        $response = $this->actingAs($usuario, 'sanctum')->postJson('/api/diagnosticos', [
            'parcela_id' => $parcela->id,
            'foto' => UploadedFile::fake()->image('hoja.jpg'),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseMissing('alerta', ['diagnostico_id' => $response->json('id')]);
    }

    public function test_imagen_que_no_es_de_cafe_no_guarda_diagnostico(): void
    {
        Storage::fake('public');
        Http::fake([
            '*/diagnosticar' => Http::response([
                'resultado' => 'no_es_cafe',
                'confianza' => 97,
                'reconocido' => false,
            ], 200),
        ]);

        [$usuario, $parcela] = $this->productorConParcela();

        $this->actingAs($usuario, 'sanctum')->postJson('/api/diagnosticos', [
            'parcela_id' => $parcela->id,
            'foto' => UploadedFile::fake()->image('gato.jpg'),
        ])->assertStatus(422);

        $this->assertDatabaseCount('diagnostico', 0);
    }

    public function test_si_el_servicio_de_ia_esta_caido_devuelve_error_claro(): void
    {
        Storage::fake('public');
        Http::fake([
            '*/diagnosticar' => Http::response([], 500),
        ]);

        [$usuario, $parcela] = $this->productorConParcela();

        $this->actingAs($usuario, 'sanctum')->postJson('/api/diagnosticos', [
            'parcela_id' => $parcela->id,
            'foto' => UploadedFile::fake()->image('hoja.jpg'),
        ])->assertStatus(502);
    }

    public function test_no_se_puede_diagnosticar_una_parcela_ajena(): void
    {
        Storage::fake('public');
        [, $parcelaAjena] = $this->productorConParcela();
        [$otroUsuario] = $this->productorConParcela();

        $this->actingAs($otroUsuario, 'sanctum')->postJson('/api/diagnosticos', [
            'parcela_id' => $parcelaAjena->id,
            'foto' => UploadedFile::fake()->image('hoja.jpg'),
        ])->assertStatus(422);
    }
}
