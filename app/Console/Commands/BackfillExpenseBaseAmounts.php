<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Expense;
use App\Models\Currency;
use App\Models\ExchangeRate;

class BackfillExpenseBaseAmounts extends Command
{
    protected $signature = 'expenses:backfill-base-amounts';
    protected $description = 'Backfill amount_base for all expenses that are missing it';

    public function handle()
    {
        $baseCurrency = Currency::getBaseCurrency();
        if (!$baseCurrency) {
            $this->error('No base currency configured. Please set a base currency first.');
            return 1;
        }

        $this->info("Base currency: {$baseCurrency->code}");

        $expenses = Expense::whereNull('amount_base')
            ->orWhere('amount_base', 0)
            ->get();

        $this->info("Found {$expenses->count()} expenses without amount_base.");

        $bar = $this->output->createProgressBar($expenses->count());
        $bar->start();

        foreach ($expenses as $expense) {
            if ($expense->currency && $expense->currency !== $baseCurrency->code) {
                $rate = ExchangeRate::getRate($expense->currency, $baseCurrency->code);
                if ($rate) {
                    $expense->exchange_rate = $rate;
                    $expense->amount_base = $expense->amount * $rate;
                } else {
                    $this->warn("\nNo exchange rate found for {$expense->currency} → {$baseCurrency->code} on expense #{$expense->id}. Using amount as-is.");
                    $expense->amount_base = $expense->amount;
                    $expense->exchange_rate = 1.0;
                }
            } else {
                $expense->amount_base = $expense->amount;
                $expense->exchange_rate = 1.0;
            }

            $expense->saveQuietly(); // Skip observers to avoid recreating journal entries
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✓ Backfilled amount_base for {$expenses->count()} expenses.");

        return 0;
    }
}
