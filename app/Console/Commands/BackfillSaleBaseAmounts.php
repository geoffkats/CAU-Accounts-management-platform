<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\Currency;
use App\Models\ExchangeRate;

class BackfillSaleBaseAmounts extends Command
{
    protected $signature = 'sales:backfill-base-amounts';
    protected $description = 'Backfill amount_base for all sales that are missing it';

    public function handle()
    {
        $baseCurrency = Currency::getBaseCurrency();
        if (!$baseCurrency) {
            $this->error('No base currency configured. Please set a base currency first.');
            return 1;
        }

        $this->info("Base currency: {$baseCurrency->code}");

        $sales = Sale::whereNull('amount_base')
            ->orWhere('amount_base', 0)
            ->get();

        $this->info("Found {$sales->count()} sales without amount_base.");

        $bar = $this->output->createProgressBar($sales->count());
        $bar->start();

        foreach ($sales as $sale) {
            if ($sale->currency && $sale->currency !== $baseCurrency->code) {
                $rate = ExchangeRate::getRate($sale->currency, $baseCurrency->code);
                if ($rate) {
                    $sale->exchange_rate = $rate;
                    $sale->amount_base = $sale->amount * $rate;
                } else {
                    $this->warn("\nNo exchange rate found for {$sale->currency} → {$baseCurrency->code} on sale #{$sale->id}. Using amount as-is.");
                    $sale->amount_base = $sale->amount;
                    $sale->exchange_rate = 1.0;
                }
            } else {
                $sale->amount_base = $sale->amount;
                $sale->exchange_rate = 1.0;
            }

            $sale->saveQuietly(); // Skip observers to avoid recreating journal entries
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✓ Backfilled amount_base for {$sales->count()} sales.");

        return 0;
    }
}
