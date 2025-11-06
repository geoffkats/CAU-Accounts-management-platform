<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportOpeningBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounts:opening-balances {--file=} {--date=} {--currency=UGX} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import per-account opening balances and post a balanced opening journal entry';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->option('file');
        $date = $this->option('date') ?: now()->toDateString();
        $currency = $this->option('currency');
        $dry = (bool) $this->option('dry-run');

        if (!$file) {
            $this->error('Please provide --file=/path/to/opening_balances.csv');
            return Command::INVALID;
        }
        if (!file_exists($file)) {
            $this->error("File not found: $file");
            return Command::INVALID;
        }

        $rows = $this->readCsv($file);
        if (empty($rows)) {
            $this->error('No rows found in CSV. Expect headers: code,amount');
            return Command::INVALID;
        }

        $lines = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($rows as $row) {
            $code = trim((string)($row['code'] ?? ''));
            $amount = (float)($row['amount'] ?? 0);
            if ($code === '' || $amount == 0.0) {
                $this->warn('Skipping row with empty code or zero amount.');
                continue;
            }
            $account = Account::where('code', $code)->first();
            if (!$account) {
                $this->error("Account code not found: $code");
                return Command::FAILURE;
            }

            // Determine normal balance: assets/expenses -> debit; liabilities/equity/income -> credit
            $isDebitNormal = in_array($account->type, ['asset', 'expense'], true);
            $debit = 0.0; $credit = 0.0;
            if ($isDebitNormal) {
                if ($amount > 0) { $debit = $amount; } else { $credit = abs($amount); }
            } else {
                if ($amount > 0) { $credit = $amount; } else { $debit = abs($amount); }
            }

            $lines[] = [
                'account_id' => $account->id,
                'debit' => round($debit, 2),
                'credit' => round($credit, 2),
                'description' => 'Opening balance',
            ];
            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        // Ensure we have an offset equity account
        $offset = Account::firstOrCreate(
            ['code' => '3999'],
            ['name' => 'Opening Balance Equity', 'type' => 'equity', 'description' => 'Offset for opening balances', 'is_active' => true]
        );

        // Balance the entry if needed
        $diff = round($totalDebit - $totalCredit, 2);
        if (abs($diff) > 0.004) {
            if ($diff > 0) {
                // More debits; add credit to equity offset
                $lines[] = ['account_id' => $offset->id, 'debit' => 0.0, 'credit' => abs($diff), 'description' => 'Balancing line'];
                $totalCredit += abs($diff);
            } else {
                // More credits; add debit to equity offset
                $lines[] = ['account_id' => $offset->id, 'debit' => abs($diff), 'credit' => 0.0, 'description' => 'Balancing line'];
                $totalDebit += abs($diff);
            }
        }

        // Summary
        $this->table(['Lines','Total Debit','Total Credit'], [[count($lines), number_format($totalDebit,2), number_format($totalCredit,2)]]);
        if ($dry) {
            $this->info('Dry run complete. No journal created.');
            return Command::SUCCESS;
        }

        // Create the journal entry
        $entry = JournalEntry::createEntry(
            [
                'date' => $date,
                'type' => 'opening_balance',
                'description' => 'Opening balances import',
                'created_by' => auth()->id() ?: 1,
                'status' => 'posted',
                'posted_at' => now(),
            ],
            $lines
        );

        $this->info('Opening balances journal posted: #'.$entry->id);
        return Command::SUCCESS;
    }

    private function readCsv(string $path): array
    {
        $fh = fopen($path, 'r');
        if (!$fh) return [];
        $headers = fgetcsv($fh);
        if (!$headers) return [];
        $headers = array_map(fn($h) => strtolower(trim($h)), $headers);
        $rows = [];
        while (($data = fgetcsv($fh)) !== false) {
            $row = [];
            foreach ($headers as $i => $h) {
                $row[$h] = $data[$i] ?? null;
            }
            $rows[] = $row;
        }
        fclose($fh);
        return $rows;
    }
}
