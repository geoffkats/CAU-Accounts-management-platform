<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CurrencyService
{
    /**
     * Get the latest exchange rate between two currencies
     */
    public function getRate(string $from, string $to, ?\Carbon\Carbon $date = null): ?float
    {
        return ExchangeRate::getRate($from, $to, $date);
    }

    /**
     * Convert amount from one currency to another
     */
    public function convert(float $amount, string $from, string $to, ?\Carbon\Carbon $date = null): ?float
    {
        return ExchangeRate::convert($amount, $from, $to, $date);
    }

    /**
     * Convert to base currency
     */
    public function convertToBase(float $amount, string $fromCurrency, ?\Carbon\Carbon $date = null): ?float
    {
        $baseCurrency = Currency::getBaseCurrency();
        
        if (!$baseCurrency) {
            return null;
        }

        if ($fromCurrency === $baseCurrency->code) {
            return $amount;
        }

        return $this->convert($amount, $fromCurrency, $baseCurrency->code, $date);
    }

    /**
     * Fetch and update exchange rates from external API
     * Using exchangerate-api.com (free tier available)
     */
    public function updateRatesFromAPI(): array
    {
        $baseCurrency = Currency::getBaseCurrency();
        $activeCurrencies = Currency::getActive();
        
        if (!$baseCurrency) {
            return ['success' => false, 'message' => 'No base currency configured'];
        }

        $updated = 0;
        $failed = 0;
        $date = now()->toDateString();

        // Free API endpoint (no key required for basic usage)
        // For production, use: https://v6.exchangerate-api.com/v6/YOUR-API-KEY/latest/{$baseCurrency->code}
        $apiUrl = "https://open.er-api.com/v6/latest/{$baseCurrency->code}";

        try {
            // Disable SSL verification for local development (Windows cURL SSL issue)
            // For production, ensure proper SSL certificate configuration
            $response = Http::withOptions([
                'verify' => false, // Disable SSL verification
            ])->timeout(10)->get($apiUrl);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['rates'])) {
                    foreach ($activeCurrencies as $currency) {
                        if ($currency->code === $baseCurrency->code) {
                            continue;
                        }

                        $rate = $data['rates'][$currency->code] ?? null;
                        
                        if ($rate) {
                            // Rate from base to target
                            ExchangeRate::upsertRate(
                                $baseCurrency->code,
                                $currency->code,
                                $rate,
                                $date,
                                'api'
                            );

                            // Inverse rate (target to base)
                            ExchangeRate::upsertRate(
                                $currency->code,
                                $baseCurrency->code,
                                1 / $rate,
                                $date,
                                'api'
                            );

                            $updated++;
                        } else {
                            $failed++;
                        }
                    }

                    // Clear cache
                    Cache::forget('exchange_rates_latest');

                    return [
                        'success' => true,
                        'updated' => $updated,
                        'failed' => $failed,
                        'message' => "Updated {$updated} exchange rates successfully",
                    ];
                }
            }

            return ['success' => false, 'message' => 'Failed to fetch rates from API'];
        } catch (\Exception $e) {
            Log::error('Currency rate update failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get all current exchange rates (cached)
     */
    public function getCurrentRates(): array
    {
        return Cache::remember('exchange_rates_latest', 3600, function () {
            $baseCurrency = Currency::getBaseCurrency();
            $currencies = Currency::getActive();
            $rates = [];

            foreach ($currencies as $currency) {
                if ($currency->code === $baseCurrency->code) {
                    continue;
                }

                $rate = $this->getRate($baseCurrency->code, $currency->code);
                if ($rate) {
                    $rates[$currency->code] = [
                        'rate' => $rate,
                        'symbol' => $currency->symbol,
                        'name' => $currency->name,
                    ];
                }
            }

            return $rates;
        });
    }

    /**
     * Summarize staleness and missing rates for active currencies vs base.
     * Returns [ 'stale' => [codes], 'missing' => [codes], 'latestDate' => Carbon|null ]
     */
    public function getRateStalenessSummary(int $maxAgeDays = 3): array
    {
        $base = Currency::getBaseCurrency();
        if (!$base) {
            return ['stale' => [], 'missing' => [], 'latestDate' => null];
        }

        $stale = [];
        $missing = [];
        $latest = null;
        $today = now()->startOfDay();

        foreach (Currency::getActive() as $currency) {
            if ($currency->code === $base->code) {
                continue;
            }
            $rate = ExchangeRate::where('from_currency', $currency->code)
                ->where('to_currency', $base->code)
                ->latest('effective_date')
                ->first();

            if (!$rate) {
                $missing[] = $currency->code;
                continue;
            }

            $latest = $latest ? max($latest, $rate->effective_date) : $rate->effective_date;

            $age = $today->diffInDays(optional($rate->effective_date)->startOfDay() ?? $today, false);
            // If latest effective date is older than maxAgeDays
            if (now()->diffInDays($rate->effective_date) > $maxAgeDays) {
                $stale[] = $currency->code;
            }
        }

        return [
            'stale' => array_values(array_unique($stale)),
            'missing' => array_values(array_unique($missing)),
            'latestDate' => $latest,
        ];
    }

    /**
     * Format amount with currency symbol
     */
    public function formatAmount(float $amount, string $currencyCode): string
    {
        $currency = Currency::where('code', $currencyCode)->first();
        
        if (!$currency) {
            return number_format($amount, 2);
        }

        return $currency->formatAmount($amount);
    }

    /**
     * Get currency breakdown for a set of transactions
     */
    public function getCurrencyBreakdown(array $transactions): array
    {
        $breakdown = [];

        foreach ($transactions as $transaction) {
            $currency = $transaction['currency'] ?? 'UGX';
            $amount = $transaction['amount'] ?? 0;

            if (!isset($breakdown[$currency])) {
                $breakdown[$currency] = [
                    'total' => 0,
                    'count' => 0,
                ];
            }

            $breakdown[$currency]['total'] += $amount;
            $breakdown[$currency]['count']++;
        }

        return $breakdown;
    }
}
