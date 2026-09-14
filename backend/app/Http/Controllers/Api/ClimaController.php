<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResuelveProductorAutenticado;
use App\Http\Controllers\Controller;
use App\Models\CondicionClimatica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ClimaController extends Controller
{
    use ResuelveProductorAutenticado;

    /**
     * RF04: consulta el clima actual y el pronostico de los proximos dias
     * para una parcela, usando su ubicacion GPS. Usa la API gratuita de
     * Open-Meteo (no requiere clave de acceso). De paso guarda un registro
     * en `condicionclimatica` con la lectura del dia, para tener historial.
     */
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
            'daily' => 'temperature_2m_max,temperature_2m_min,precipitation_probability_max',
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
                'probabilidad_lluvia' => $datos['daily']['precipitation_probability_max'][$i] ?? null,
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
