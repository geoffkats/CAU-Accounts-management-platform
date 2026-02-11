<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\JournalEntry;
use App\Models\Currency;

class FixCurrencyConversion extends Command
{
    protected $signature = 'journal:fix-currency {--dry-run : Show what would be fixed without making changes}';
    protected $description = 'Fix expenses/sales that were incorrectly converted from UGX to USD';

    public function handle()
    {
        $baseCurrency = Currency::getBaseCurrency();
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $this->info("Base currency: {$baseCurrency->code}");
        $this->newLine();

        // Find expenses where currency is not UGX but amount_base seems incorrectly large
        // (indicating UGX was marked as foreign currency and wrongly multiplied)
        $suspectExpenses = Expense::where('currency', '!=', 'UGX')
            ->whereNotNull('amount_base')
            ->whereNotNull('exchange_rate')
            ->where('exchange_rate', '>', 1)
            ->get()
            ->filter(function ($expense) {
                // If amount_base = amount × exchange_rate and rate is 3700 (USD→UGX),
                // but the original was already in UGX, then amount_base is too large
                $calculated = $expense->amount * $expense->exchange_rate;
                return abs($calculated - $expense->amount_base) < 1;
            });

        if ($suspectExpenses->isEmpty()) {
            $this->info('✓ No suspect expenses found.');
            return 0;
        }

        $this->warn("Found {$suspectExpenses->count()} expenses that may have been incorrectly converted:");
        $this->newLine();

        $this->table(
            ['ID', 'Amount', 'Currency', 'Rate', 'Amount Base', 'Description'],
            $suspectExpenses->map(fn($e) => [
                $e->id,
                number_format($e->amount, 2),
                $e->currency,
                $e->exchange_rate,
                number_format($e->amount_base, 2),
                substr($e->description, 0, 40)
            ])
        );

        $this->newLine();
        $this->info('These expenses appear to have been in UGX originally but were marked as foreign currency.');
        $this->info('The fix will:');
        $this->info('  1. Set currency = UGX');
        $this->info('  2. Set amount_base = amount (original UGX value)');
        $this->info('  3. Set exchange_rate = 1.0');
        $this->info('  4. Rebuild journal entries with correct amounts');
        $this->newLine();

        if ($dryRun) {
            $this->info('Run without --dry-run to apply fixes.');
            return 0;
        }

        if (!$this->confirm('Apply these fixes?', true)) {
            $this->info('Aborted.');
            return 0;
        }

        $bar = $this->output->createProgressBar($suspectExpenses->count());
        $bar->start();

        foreach ($suspectExpenses as $expense) {
            // Restore original UGX values
            $expense->currency = 'UGX';
            $expense->amount_base = $expense->amount; // Original amount was in UGX
            $expense->exchange_rate = 1.0;
            $expense->saveQuietly();

            // Rebuild journal entry
            try {
                $entry = JournalEntry::where('expense_id', $expense->id)->latest('id')->first();
                $oldId = $entry?->id;
                if ($entry) {
                    $entry->void();
                }
                $newEntry = $expense->createJournalEntry();
                if ($oldId) {
                    $newEntry->update(['replaces_entry_id' => $oldId]);
                }
            } catch (\Exception $e) {
                $this->warn("\nFailed to rebuild entry for expense #{$expense->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✓ Fixed {$suspectExpenses->count()} expenses and rebuilt their journal entries.");

        return 0;
    }
}
