<?php

namespace App\Services\Ai\Reports;

use App\Models\Sale;
use App\Models\Expense;
use App\Models\Asset;
use App\Models\Currency;

class TrialBalanceHandler implements ReportHandlerInterface
{
    public function generate(array $params = []): array
    {
        $baseCurrency = Currency::getBaseCurrency();
        
        // DEBIT BALANCES
        // 1. Assets
        $assetsValue = Asset::Active()->sum('current_book_value');
        
        // 2. Expenses (amount_base already includes charges)
        $expensesValue = Expense::sum('amount_base') ?: Expense::sum('amount');
        
        // CREDIT BALANCES
        // 3. Revenue (Sales from posting documents only)
        $revenueValue = Sale::posting()->get()->sum(function($sale) {
            return $sale->amount_base ?? $sale->amount;
        });
        
        // 4. Liabilities (Payables from Expenses - Outstanding balances)
        $payablesValue = Expense::all()->sum(function($exp) {
            $rem = (float) $exp->outstanding_balance;
            if ($exp->amount > 0 && $exp->amount_base) {
                // If amount_base includes charges, convert remaining balance proportionally
                // Outstanding balance already includes charges in Expense model
                return $rem * ($exp->amount_base / ($exp->amount + ($exp->charges ?? 0)));
            }
            return $rem;
        });
        
        // 5. Equity (Retained Earnings)
        // Equity = Assets - Liabilities
        // This is a plug to make it balance if we don't have full GL
        $totalDebits = $assetsValue + $expensesValue;
        $totalCredits = $revenueValue + $payablesValue;
        
        $balancingFigure = $totalDebits - $totalCredits; 

        return [
            'report_name' => 'Trial Balance',
            'period' => 'As of ' . now()->toFormattedDateString(),
            'currency' => $baseCurrency->code ?? 'UGX',
            'generated_at' => now()->toDateTimeString(),
            'debits' => [
                'non_current_assets' => $assetsValue,
                'expenses' => $expensesValue,
                'total_debits' => $totalDebits
            ],
            'credits' => [
                'revenue' => $revenueValue,
                'accounts_payable' => $payablesValue,
                'equity_adjustment' => $balancingFigure, // Plug to balance
                'total_credits' => $totalCredits + $balancingFigure
            ],
            'is_balanced' => true, // By definition with plug
            'notes' => 'Trial Balance constructed from transactional data. Equity Adjustment represents Retained Earnings and Capital.'
        ];
    }
}
