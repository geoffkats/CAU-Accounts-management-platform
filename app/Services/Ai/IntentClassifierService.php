<?php

namespace App\Services\Ai;

use Illuminate\Support\Str;

class IntentClassifierService
{
    const INTENT_PROFIT_LOSS = 'profit_loss';
    const INTENT_BALANCE_SHEET = 'balance_sheet';
    const INTENT_EXPENSES = 'expenses';
    const INTENT_TRIAL_BALANCE = 'trial_balance';
    const INTENT_UNKNOWN = 'unknown';

    public function classify(string $query): array
    {
        $normalizedQuery = Str::lower($query);

        // Profit & Loss / Income Statement
        if (Str::contains($normalizedQuery, ['profit', 'loss', 'income', 'making money', 'earning'])) {
            return [
                'intent' => self::INTENT_PROFIT_LOSS,
                'period' => $this->extractPeriod($normalizedQuery),
                'requires_export' => Str::contains($normalizedQuery, ['download', 'pdf', 'excel', 'export'])
            ];
        }

        // Expenses
        if (Str::contains($normalizedQuery, ['expense', 'cost', 'spending', 'spent'])) {
            return [
                'intent' => self::INTENT_EXPENSES,
                'period' => $this->extractPeriod($normalizedQuery),
                'requires_export' => Str::contains($normalizedQuery, ['download', 'pdf', 'excel', 'export'])
            ];
        }

        // Balance Sheet
        if (Str::contains($normalizedQuery, ['balance sheet', 'assets', 'liabilities', 'equity'])) {
            return [
                'intent' => self::INTENT_BALANCE_SHEET,
                'period' => 'current', // Balance sheets are usually "as of today" unless specified
                'requires_export' => Str::contains($normalizedQuery, ['download', 'pdf', 'excel', 'export'])
            ];
        }

        // Trial Balance
        if (Str::contains($normalizedQuery, ['trial balance', 'ledger balance'])) {
            return [
                'intent' => self::INTENT_TRIAL_BALANCE,
                'period' => 'current',
                'requires_export' => Str::contains($normalizedQuery, ['download', 'pdf', 'excel', 'export'])
            ];
        }

        return [
            'intent' => self::INTENT_UNKNOWN,
            'period' => 'current',
            'requires_export' => false
        ];
    }

    protected function extractPeriod(string $query): string
    {
        if (Str::contains($query, ['last month', 'previous month'])) {
            return 'last_month';
        }
        if (Str::contains($query, ['last year', 'previous year', '2024'])) {
            return 'last_year';
        }
        if (Str::contains($query, ['yesterday'])) {
            return 'yesterday';
        }
        if (Str::contains($query, ['today'])) {
            return 'today';
        }
        
        return 'current_month'; // Default
    }
}
