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
        $expenses = Expense::has('journalEntry')->get();

        $this->info("Rebuilding journal entries for {$expenses->count()} expenses...");
        $bar = $this->output->createProgressBar($expenses->count());
        $bar->start();

        foreach ($expenses as $expense) {
            try {
                // Find and void the existing entry
                $entry = JournalEntry::where('expense_id', $expense->id)->latest('id')->first();
                $oldId = $entry?->id;

                if ($entry) {
                    $entry->void();
                }

                // Create new entry with correct base currency amounts
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
    }

    private function rebuildSaleEntries()
    {
        $sales = Sale::has('journalEntry')->get();

        $this->info("Rebuilding journal entries for {$sales->count()} sales...");
        $bar = $this->output->createProgressBar($sales->count());
        $bar->start();

        foreach ($sales as $sale) {
            try {
                // Find and void the existing entry
                $entry = JournalEntry::where('sales_id', $sale->id)
                    ->orWhere('income_id', $sale->id)
                    ->latest('id')
                    ->first();
                $oldId = $entry?->id;

                if ($entry) {
                    $entry->void();
                }

                // Create new entry with correct base currency amounts
                $newEntry = $sale->createJournalEntry();

                if ($oldId) {
                    $newEntry->update(['replaces_entry_id' => $oldId]);
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
