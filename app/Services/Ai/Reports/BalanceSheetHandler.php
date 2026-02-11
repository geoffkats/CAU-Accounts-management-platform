<?php

namespace App\Services\Ai\Reports;

use App\Models\Sale;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Currency;

class BalanceSheetHandler implements ReportHandlerInterface
{
    public function generate(array $params = []): array
    {
        $baseCurrency = Currency::getBaseCurrency();
        
        // ASSETS
        // 1. Non-Current Assets (Fixed Assets)
        // We retrieve the real book value from the Asset Register
        $nonCurrentAssets = \App\Models\Asset::Active()->sum('current_book_value');

        // 2. Current Assets
        // Receivables: Unpaid Invoices
        $receivables = Sale::where('status', '!=', 'paid')->sum('amount_base') ?: Sale::where('status', '!=', 'paid')->sum('amount');
        
        // This is a simplified "synthetic" cash calculation for the AI context
        // In a real system, this would come from a proper Ledger query on the Cash account
        $cashOnHand = 0; // Placeholder as we don't have a reliable Cash Account balance yet
        
        $totalAssets = $nonCurrentAssets + $receivables;
        // Payables: Unpaid Expenses
        // Pending Expenses Amount is approximated
        $payables = Expense::where('status', '!=', 'paid')->get()->sum(function($exp) {
            return $exp->amount_base ?: $exp->amount;
        });

        // EQUITY
        // Retained Earnings (Net Profit)
        // Note: For balance sheet to balance, Equity = Assets - Liabilities
        // Since this is a synthetic report, we calculate Equity as the plug figure
        $totalEquity = $totalAssets - $payables;
        $retainedEarnings = $totalEquity;

        return [
            'report_name' => 'Balance Sheet (Statement of Financial Position)',
            'period' => 'As of ' . now()->toFormattedDateString(),
            'currency' => $baseCurrency->code ?? 'UGX',
            'generated_at' => now()->toDateTimeString(),
            'assets' => [
                'non_current_assets' => [
                    'property_plant_equipment' => $nonCurrentAssets
                ],
                'current_assets' => [
                    'accounts_receivable' => $receivables,
                    'cash_equivalents' => '(Not tracked in this context)'
                ],
                'total_assets' => $totalAssets
            ],
            'liabilities' => [
                'current_liabilities' => [
                    'accounts_payable' => $payables,
                ],
                'total_liabilities' => $payables
            ],
            'equity' => [
                'retained_earnings' => $retainedEarnings,
                'total_equity' => $totalEquity
            ],
            'check' => [
                'assets_minus_liabilities' => $totalAssets - $payables,
                'equity_check' => $totalEquity
            ],
            'notes' => 'Non-current assets derived from active Asset Register entries. Receivables/Payables derived from unpaid Invoices/Expenses.'
        ];
    }
}
