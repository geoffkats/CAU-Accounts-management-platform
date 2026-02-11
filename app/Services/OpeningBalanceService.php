<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Currency;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

class OpeningBalanceService
{
    /**
     * Ensure the Opening Balance Equity account exists and return it.
     */
    public function ensureOpeningBalanceEquity(): Account
    {
        $account = Account::where('code', '3999')->orWhere(function ($q) {
            $q->where('type', 'equity')->where('name', 'Opening Balance Equity');
        })->first();

        if (!$account) {
            $account = Account::create([
                'code' => '3999',
                'name' => 'Opening Balance Equity',
                'type' => 'equity',
                'description' => 'Auto-created for opening balance postings',
                'is_active' => true,
            ]);
        }

        return $account;
    }

    /**
     * Post a single opening balance journal for the provided lines.
     * Each line must include: account_id, debit, credit (base currency amounts).
     * Will add a balancing line to Opening Balance Equity if needed.
     */
    public function postOpeningBalances(string $date, array $lines, ?int $userId = null): JournalEntry
    {
        $base = Currency::getBaseCurrency();
        if (!$base) {
            throw new \RuntimeException('Base currency not configured.');
        }

        // Normalize lines and compute totals
        $prepared = [];
        $totalDebits = 0.0;
        $totalCredits = 0.0;

        foreach ($lines as $line) {
            if (!isset($line['account_id'])) {
                throw new \InvalidArgumentException('Each line requires account_id.');
            }
            $debit = (float) ($line['debit'] ?? 0);
            $credit = (float) ($line['credit'] ?? 0);
            if ($debit < 0 || $credit < 0) {
                throw new \InvalidArgumentException('Debit/Credit cannot be negative.');
            }
            if ($debit > 0 && $credit > 0) {
                throw new \InvalidArgumentException('A line cannot have both debit and credit amounts');
            }
            if ($debit == 0 && $credit == 0) {
                continue; // skip empty
            }
            $prepared[] = [
                'account_id' => (int) $line['account_id'],
                'debit' => round($debit, 2),
                'credit' => round($credit, 2),
                'description' => 'Opening Balance',
            ];
            $totalDebits += $debit;
            $totalCredits += $credit;
        }

        // Add balancing line if needed
        $difference = round($totalDebits - $totalCredits, 2);
        if (abs($difference) > 0.01) {
            $equity = $this->ensureOpeningBalanceEquity();
            if ($difference > 0) {
                // More debits than credits -> add credit to equity
                $prepared[] = [
                    'account_id' => $equity->id,
                    'debit' => 0,
                    'credit' => abs($difference),
                    'description' => 'Opening Balance Balancing',
                ];
            } else {
                // More credits than debits -> add debit to equity
                $prepared[] = [
                    'account_id' => $equity->id,
                    'debit' => abs($difference),
                    'credit' => 0,
                    'description' => 'Opening Balance Balancing',
                ];
            }
        }

        return DB::transaction(function () use ($date, $prepared, $userId) {
            $entry = JournalEntry::createEntry([
                'date' => $date,
                'reference' => JournalEntry::generateReference('opening_balance'),
                'type' => 'opening_balance',
                'description' => 'Opening Balances',
                'created_by' => $userId,
                'status' => 'draft',
            ], $prepared);

            $entry->post();
            return $entry->fresh('lines');
        });
    }
}
