<?php

namespace App\Services\Ai\Reports;

use App\Models\Expense;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;

class ExpenseHandler implements ReportHandlerInterface
{
    public function generate(array $params = []): array
    {
        $baseCurrency = Currency::getBaseCurrency();
        
        // Aggregate expenses by category
        // We prioritize 'amount_base' if available, otherwise 'amount'
        $query = Expense::select('category', DB::raw('SUM(COALESCE(amount_base, amount)) as total'))
            ->groupBy('category');

        // Apply basic date filtering if period specific (can be expanded)
        if (isset($params['period'])) {
            if ($params['period'] === 'this_month') {
                $query->whereMonth('expense_date', now()->month)
                      ->whereYear('expense_date', now()->year);
            }
            // Add more filters as needed
        }

        $categories = $query->get()->pluck('total', 'category')->toArray();

        // Get pretty names for categories
        $formattedCategories = [];
        $totalExpenses = 0;
        
        foreach ($categories as $catKey => $amount) {
            $name = Expense::getCategories()[$catKey] ?? ucfirst($catKey);
            $formattedCategories[$name] = (float) $amount;
            $totalExpenses += $amount;
        }

        // Calculate percentages
        $breakdown = [];
        foreach ($formattedCategories as $name => $amount) {
            $breakdown[] = [
                'category' => $name,
                'amount' => $amount,
                'percentage' => $totalExpenses > 0 ? round(($amount / $totalExpenses) * 100, 1) . '%' : '0%'
            ];
        }

        return [
            'report_name' => 'Expense Breakdown Analysis',
            'period' => $params['period'] ?? 'All Time',
            'currency' => $baseCurrency->code ?? 'UGX',
            'generated_at' => now()->toDateTimeString(),
            'total_expenses' => $totalExpenses,
            'breakdown' => $breakdown,
            'highest_spending_category' => $breakdown[0]['category'] ?? 'None',
            'notes' => 'Expenses aggregated by assigned category. Includes all recorded expenses regardless of payment status.'
        ];
    }
}
