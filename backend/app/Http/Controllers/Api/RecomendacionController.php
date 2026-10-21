<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResuelveProductorAutenticado;
use App\Http\Controllers\Controller;
use App\Models\Diagnostico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RecomendacionController extends Controller
{
    use ResuelveProductorAutenticado;

    /**
     * Motor de recomendaciones de manejo del cultivo: combina el pH y la
     * fertilidad del suelo (definidos por el Verificador), el pronostico del
     * clima de Open-Meteo y el ultimo diagnostico de IA de la parcela, para
     * darle al productor acciones concretas en vez de solo datos sueltos.
     */
    public function porParcela(Request $request, int $id)
    {
        $productor = $this->productorAutenticado($request);
        $parcela = $productor->parcelas()->find($id);

        if (! $parcela) {
            return response()->json(['message' => 'Parcela no encontrada.'], 404);
        }

        $recomendaciones = [];

        // --- Suelo (RF: parametros definidos por el Verificador) ---
        if ($parcela->ph_suelo !== null) {
            if ($parcela->ph_suelo < 5.0) {
                $recomendaciones[] = [
                    'tipo' => 'suelo',
                    'mensaje' => "El pH de tu parcela está en {$parcela->ph_suelo}, por debajo del rango ideal para el café (5.0 a 6.5). Aplica cal agrícola para subir el pH.",
                ];
            } elseif ($parcela->ph_suelo > 6.5) {
                $recomendaciones[] = [
                    'tipo' => 'suelo',
                    'mensaje' => "El pH de tu parcela está en {$parcela->ph_suelo}, por encima del rango ideal para el café (5.0 a 6.5). Aplica azufre o materia orgánica para bajarlo.",
                ];
            }
        }

        if ($parcela->fertilidad === 'baja') {
            $recomendaciones[] = [
                'tipo' => 'suelo',
                'mensaje' => 'La fertilidad de tu parcela es baja. Refuerza con abono orgánico o compost antes de la próxima cosecha.',
            ];
        }

        // --- Clima (pronostico de Open-Meteo) ---
        $coords = $this->extraerCoordenadas($parcela->ubicacion);

        if ($coords) {
            $respuesta = Http::timeout(10)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $coords['lat'],
                'longitude' => $coords['lng'],
                'daily' => 'precipitation_probability_max,temperature_2m_max',
                'timezone' => 'auto',
                'forecast_days' => 3,
            ]);

            if ($respuesta->successful()) {
                $daily = $respuesta->json('daily') ?? [];
                $fechas = $daily['time'] ?? [];
                $lluvia = $daily['precipitation_probability_max'] ?? [];
                $tempMax = $daily['temperature_2m_max'] ?? [];

                // Lluvia alta hoy o mañana: no es buen momento para fumigar ni fertilizar.
                foreach (array_slice($fechas, 0, 2) as $i => $fecha) {
                    if (($lluvia[$i] ?? 0) >= 60) {
                        $cuando = $i === 0 ? 'hoy' : 'mañana';
                        $recomendaciones[] = [
                            'tipo' => 'clima',
                            'mensaje' => "Se espera {$lluvia[$i]}% de probabilidad de lluvia {$cuando}. Evita fumigar o fertilizar, el producto se lavaría con la lluvia.",
                        ];
                        break;
                    }
                }

                // Calor fuerte y sin lluvia en los proximos 3 dias: riesgo de estres hidrico.
                $sinLluvia = count(array_filter($lluvia, fn ($p) => $p < 20)) === count($lluvia) && count($lluvia) > 0;
                $calor = count(array_filter($tempMax, fn ($t) => $t >= 30)) > 0;
                if ($sinLluvia && $calor) {
                    $recomendaciones[] = [
                        'tipo' => 'clima',
                        'mensaje' => 'Se esperan varios días sin lluvia y con temperaturas altas. Considera regar la parcela para evitar estrés hídrico en las plantas.',
                    ];
                }
            }
        }

        // --- Ultimo diagnostico de IA de la parcela ---
        $ultimoDiagnostico = Diagnostico::where('parcela_id', $parcela->id)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->first();

        if ($ultimoDiagnostico && $ultimoDiagnostico->resultado !== 'sana') {
            $recomendaciones[] = [
                'tipo' => 'plaga',
                'mensaje' => "Tu último diagnóstico detectó posible {$ultimoDiagnostico->resultado} (confianza {$ultimoDiagnostico->confianza}%). Si todavía no trataste el cultivo, hazlo cuanto antes para evitar que se propague.",
            ];
        }

        return response()->json(['recomendaciones' => $recomendaciones]);
    }

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
