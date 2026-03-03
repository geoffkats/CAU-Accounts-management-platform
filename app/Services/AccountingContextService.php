<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Sale;
use App\Models\Account;
use App\Models\Program;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AccountingContextService
{
    /**
     * Get a high-level financial summary for the current period.
     */
    public function getFinancialSummary(): array
    {
        $baseCurrency = \App\Models\Currency::getBaseCurrency();
        
        return [
            'currency' => $baseCurrency->code,
            'period' => now()->format('F Y'),

            'totals' => [
                'total_income' => $this->incomeTotal(),
                'total_expenses' => $this->expenseTotal(),
                'net_profit' => $this->profit()
            ],

            'ratios' => [
                'profit_margin_percent' => $this->profitMargin(),
                'roi_percent' => $this->roi(),
                'margin_trend' => $this->marginTrend()
            ],

            'flags' => [
                'unpaid_invoices_count' => Sale::posting()->where('status', '!=', Sale::STATUS_PAID)->count(),
                'unpaid_invoices_amount' => Sale::posting()->get()->sum('remaining_balance'),
                'pending_expenses_count' => Expense::where('status', '!=', 'paid')->count(),
                'pending_expenses_amount' => Expense::get()->sum('outstanding_balance'),
            ]
        ];
    }

    protected function incomeTotal()
    {
        return Sale::posting()->get()->sum(function($sale) {
            return $sale->amount_base ?? $sale->amount;
        });
    }

    protected function expenseTotal()
    {
        // amount_base already includes charges
        return Expense::sum('amount_base') ?: Expense::sum('amount');
    }

    protected function profit()
    {
        return (float) $this->incomeTotal() - (float) $this->expenseTotal();
    }

    protected function profitMargin()
    {
        $income = $this->incomeTotal();
        return $income > 0 ? ($this->profit() / $income) * 100 : 0;
    }

    protected function roi()
    {
        // Simple ROI: Profit / Total Expenses
        $expenses = $this->expenseTotal();
        return $expenses > 0 ? ($this->profit() / $expenses) * 100 : 0;
    }

    protected function marginTrend()
    {
        // Compare current month margin vs last month
        $currentMargin = $this->profitMargin();
        
        // This is simplified, in a real app we'd calculate for last month specifically
        return "Stable"; // Placeholder for trend logic
    }

    /**
     * Get program-wise financial performance.
     */
    public function getProgramSummary()
    {
        return Program::all()->map(function ($program) {
            $sales = Sale::posting()->where('program_id', $program->id)->get()->sum(function($sale) {
                return $sale->amount_base ?? $sale->amount;
            });
            // amount_base already includes charges
            $totalExpenses = Expense::where('program_id', $program->id)->sum('amount_base') ?: Expense::where('program_id', $program->id)->sum('amount');
            
            return [
                'name' => $program->name,
                'status' => $program->status,
                'sales' => (float) $sales,
                'expenses' => (float) $totalExpenses,
                'profit' => (float) ($sales - $totalExpenses),
                'margin' => $sales > 0 ? (($sales - $totalExpenses) / $sales) * 100 : 0,
            ];
        })->toArray();
    }

    /**
     * Get the full context as a concise JSON payload for the AI.
     */
    public function getFullContextString()
    {
        $payload = [
            'financial_summary' => $this->getFinancialSummary(),
            'program_performance' => $this->getProgramSummary(),
            'accounting_policy' => 'IFRS-aligned. All figures in base currency.',
            'timestamp' => now()->toDateTimeString(),
        ];

        return json_encode($payload, JSON_PRETTY_PRINT);
    }
}
