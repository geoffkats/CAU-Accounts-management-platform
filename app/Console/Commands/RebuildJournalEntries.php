<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\JournalEntry;

class RebuildJournalEntries extends Command
{
    protected $signature = 'journal:rebuild {--type=all : Type of transactions to rebuild (all, expenses, sales)}';
    protected $description = 'Rebuild journal entries for all transactions to ensure base currency amounts';

    public function handle()
    {
        $type = $this->option('type');

        $this->info("Rebuilding journal entries for: {$type}");
        $this->newLine();

        if (in_array($type, ['all', 'expenses'])) {
            $this->rebuildExpenseEntries();
        }

        if (in_array($type, ['all', 'sales'])) {
            $this->rebuildSaleEntries();
        }

        $this->newLine();
        $this->info('✓ Journal entry rebuild complete.');

        return 0;
    }

    private function rebuildExpenseEntries()
    {
        $expenses = Expense::all();

        $this->info("Rebuilding journal entries for {$expenses->count()} expenses...");
        $bar = $this->output->createProgressBar($expenses->count());
        $bar->start();

        foreach ($expenses as $expense) {
            try {
                // Find and void ALL existing accrual entries (type 'expense') for this record
                // This ensures we don't leave duplicates if multiple existed
                $entries = JournalEntry::where('expense_id', $expense->id)
                    ->where('type', 'expense')
                    ->where('status', '!=', 'void')
                    ->get();

                $replacesId = null;
                foreach ($entries as $entry) {
                    $replacesId = $entry->id;
                    $entry->void();
                }

                // Create new entry with correct logic
                $newEntry = $expense->createJournalEntry();

                if ($replacesId) {
                    $newEntry->update(['replaces_entry_id' => $replacesId]);
                }
            } catch (\Exception $e) {
                $this->warn("\nFailed to rebuild entry for expense #{$expense->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function rebuildSaleEntries()
    {
        $sales = Sale::all();

        $this->info("Rebuilding journal entries for {$sales->count()} sales...");
        $bar = $this->output->createProgressBar($sales->count());
        $bar->start();

        foreach ($sales as $sale) {
            try {
                // Find and void ALL existing income entries (type 'income') for this record
                $entries = JournalEntry::where('sales_id', $sale->id)
                    ->where('type', 'income')
                    ->where('status', '!=', 'void')
                    ->get();

                $replacesId = null;
                foreach ($entries as $entry) {
                    $replacesId = $entry->id;
                    $entry->void();
                }

                // Create new entry with correct logic
                if ($sale->postsToLedger()) {
                    $newEntry = $sale->createJournalEntry();
                    if ($replacesId) {
                        $newEntry->update(['replaces_entry_id' => $replacesId]);
                    }
                }
            } catch (\Exception $e) {
                $this->warn("\nFailed to rebuild entry for sale #{$sale->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }
}
