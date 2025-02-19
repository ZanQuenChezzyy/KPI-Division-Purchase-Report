<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    public static function getRate($from = 'IDR', $to = 'USD', $amount = 1)
    {
        $cacheKey = "exchange_rate_{$from}_{$to}_{$amount}";

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($from, $to, $amount) {
            $apiKey = config('services.exchange_rate.api_key');
            $baseUrl = config('services.exchange_rate.base_url');

            try {
                $response = Http::withHeaders([
                    'apikey' => $apiKey
                ])->timeout(10)
                    ->get("$baseUrl/latest", [
                        'symbols' => $to,
                        'base' => $from
                    ]);

                Log::info('Exchange Rate API Response', ['response' => $response->json()]);

                if ($response->successful()) {
                    return $response->json()['rates'][$to] ?? null;
                }

                Log::warning('Exchange Rate API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            } catch (\Exception $e) {
                Log::error('Exchange Rate API Request Failed: ' . $e->getMessage());
            }

            return null;
        });
    }
}
