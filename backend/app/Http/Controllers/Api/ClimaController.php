<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResuelveProductorAutenticado;
use App\Http\Controllers\Controller;
use App\Models\CondicionClimatica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use OpenApi\Attributes as OA;

class ClimaController extends Controller
{
    use ResuelveProductorAutenticado;

    /**
     * RF04: consulta el clima actual y el pronostico de los proximos dias
     * para una parcela, usando su ubicacion GPS. Usa la API gratuita de
     * Open-Meteo (no requiere clave de acceso). De paso guarda un registro
     * en `condicionclimatica` con la lectura del dia, para tener historial.
     */
    #[OA\Get(
        path: '/parcelas/{id}/clima',
        summary: 'Clima actual y pronóstico de 7 días para una parcela',
        description: 'Usa la ubicación GPS de la parcela contra la API de Open-Meteo. Guarda la lectura del día como historial.',
        tags: ['Monitoreo'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'ID de la parcela')],
        responses: [
            new OA\Response(response: 200, description: 'Clima actual + pronóstico de 7 días'),
            new OA\Response(response: 404, description: 'Parcela no encontrada'),
            new OA\Response(response: 422, description: 'La parcela no tiene coordenadas GPS'),
            new OA\Response(response: 502, description: 'El servicio de clima no respondió'),
        ]
    )]
    public function porParcela(Request $request, int $id)
    {
        $productor = $this->productorAutenticado($request);
        $parcela = $productor->parcelas()->find($id);

        if (! $parcela) {
            return response()->json(['message' => 'Parcela no encontrada.'], 404);
        }

        $coords = $this->extraerCoordenadas($parcela->ubicacion);

        if (! $coords) {
            return response()->json([
                'message' => 'Esta parcela no tiene coordenadas GPS guardadas. Edítala y marca su ubicación en el mapa para poder consultar el clima.',
            ], 422);
        }

        $respuesta = Http::timeout(10)->get('https://api.open-meteo.com/v1/forecast', [
            'latitude' => $coords['lat'],
            'longitude' => $coords['lng'],
            'current' => 'temperature_2m,relative_humidity_2m,precipitation_probability',
            'daily' => 'temperature_2m_max,temperature_2m_min,apparent_temperature_max,apparent_temperature_min,'
                . 'precipitation_probability_max,precipitation_sum,windspeed_10m_max,uv_index_max,sunrise,sunset',
            'timezone' => 'auto',
            'forecast_days' => 7,
        ]);

        if (! $respuesta->successful()) {
            return response()->json([
                'message' => 'No se pudo consultar el clima en este momento. Intenta de nuevo más tarde.',
            ], 502);
        }

        $datos = $respuesta->json();
        $actual = $datos['current'] ?? null;

        if (! $actual) {
            return response()->json(['message' => 'El servicio de clima no devolvió datos.'], 502);
        }

        // Guarda (o actualiza) la lectura de hoy para esta parcela, como historial.
        CondicionClimatica::updateOrCreate(
            ['parcela_id' => $parcela->id, 'fecha' => now()->toDateString()],
            [
                'temperatura' => $actual['temperature_2m'] ?? null,
                'humedad' => $actual['relative_humidity_2m'] ?? null,
                'probabilidad_lluvia' => $actual['precipitation_probability'] ?? null,
                'fuente' => 'Open-Meteo',
            ]
        );

        $pronostico = [];
        $dias = $datos['daily']['time'] ?? [];
        foreach ($dias as $i => $fecha) {
            $pronostico[] = [
                'fecha' => $fecha,
                'temperatura_max' => $datos['daily']['temperature_2m_max'][$i] ?? null,
                'temperatura_min' => $datos['daily']['temperature_2m_min'][$i] ?? null,
                'sensacion_max' => $datos['daily']['apparent_temperature_max'][$i] ?? null,
                'sensacion_min' => $datos['daily']['apparent_temperature_min'][$i] ?? null,
                'probabilidad_lluvia' => $datos['daily']['precipitation_probability_max'][$i] ?? null,
                'lluvia_mm' => $datos['daily']['precipitation_sum'][$i] ?? null,
                'viento_max' => $datos['daily']['windspeed_10m_max'][$i] ?? null,
                'indice_uv' => $datos['daily']['uv_index_max'][$i] ?? null,
                'amanecer' => $datos['daily']['sunrise'][$i] ?? null,
                'atardecer' => $datos['daily']['sunset'][$i] ?? null,
            ];
        }

        return response()->json([
            'actual' => [
                'temperatura' => $actual['temperature_2m'] ?? null,
                'humedad' => $actual['relative_humidity_2m'] ?? null,
                'probabilidad_lluvia' => $actual['precipitation_probability'] ?? null,
            ],
            'pronostico' => $pronostico,
        ]);
    }

    /**
     * El campo `ubicacion` de la parcela es texto libre tipo "lat, lng".
     * Si no tiene ese formato (o esta vacio), no se puede consultar el clima.
     */
    private function extraerCoordenadas(?string $ubicacion): ?array
    {
        if (! $ubicacion) {
            return null;
        }

        $partes = array_map('trim', explode(',', $ubicacion));

        if (count($partes) !== 2 || ! is_numeric($partes[0]) || ! is_numeric($partes[1])) {
            return null;
        }

        return ['lat' => (float) $partes[0], 'lng' => (float) $partes[1]];
    }
}
