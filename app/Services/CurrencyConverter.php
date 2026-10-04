<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Los precios se guardan siempre en la moneda base (MXN).
 * Este servicio convierte desde/hacia la moneda configurada en el sistema.
 */
class CurrencyConverter
{
    public const BASE = 'MXN';

    private const CACHE_KEY = 'ironpulse.exchange_rates';
    private const CACHE_STALE_KEY = 'ironpulse.exchange_rates.stale';
    private const CACHE_HOURS = 1;

    // Se usan solo si no hay internet y nunca se obtuvieron tasas reales.
    // Unidades de cada moneda por 1 MXN.
    private const FALLBACK = [
        'MXN' => 1.0,
        'USD' => 0.054,
        'EUR' => 0.047,
    ];

    public function rates(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addHours(self::CACHE_HOURS), function () {
            try {
                $response = Http::timeout(5)
                    ->get('https://open.er-api.com/v6/latest/' . self::BASE);

                $rates = $response->json('rates');

                if ($response->successful() && isset($rates['USD'], $rates['EUR'])) {
                    $result = [
                        'MXN' => 1.0,
                        'USD' => (float) $rates['USD'],
                        'EUR' => (float) $rates['EUR'],
                    ];

                    Cache::forever(self::CACHE_STALE_KEY, $result);

                    return $result;
                }
            } catch (\Throwable $e) {
                // Se usa la última tasa conocida.
            }

            return Cache::get(self::CACHE_STALE_KEY, self::FALLBACK);
        });
    }

    /** Unidades de $moneda por 1 MXN. */
    public function rate(string $moneda): float
    {
        $rate = $this->rates()[$moneda] ?? 1.0;

        return $rate > 0 ? $rate : 1.0;
    }

    public function fromBase(float $monto, string $moneda): float
    {
        return round($monto * $this->rate($moneda), 2);
    }

    public function toBase(float $monto, string $moneda): float
    {
        return round($monto / $this->rate($moneda), 2);
    }
}