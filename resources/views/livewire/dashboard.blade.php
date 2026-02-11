<?php

use App\Models\Program;
use App\Models\Sale;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Account;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\Currency;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public string $period = 'this_month';

    public function with(): array
    {
        $dateRange = $this->getDateRange();
        $baseCurrency = Currency::getBaseCurrency();

        return [
            'totalIncome' => Sale::whereBetween('sale_date', $dateRange)->sum('amount_base') ?: 
                            Sale::whereBetween('sale_date', $dateRange)->sum('amount'),
            'totalExpenses' => Expense::whereBetween('expense_date', $dateRange)->sum('amount_base') ?: 
                              Expense::whereBetween('expense_date', $dateRange)->sum('amount'),
            'profit' => (Sale::whereBetween('sale_date', $dateRange)->sum('amount_base') ?: 
                        Sale::whereBetween('sale_date', $dateRange)->sum('amount')) - 
                       (Expense::whereBetween('expense_date', $dateRange)->sum('amount_base') ?: 
                        Expense::whereBetween('expense_date', $dateRange)->sum('amount')),
            'activePrograms' => Program::where('status', 'active')->count(),
            'unpaidSales' => Sale::where('status', '!=', 'paid')
                            ->whereBetween('sale_date', $dateRange)
                            ->sum('amount_base') ?: 
                            Sale::where('status', '!=', 'paid')
                            ->whereBetween('sale_date', $dateRange)
                            ->sum('amount'),
            'pendingExpenses' => Expense::whereBetween('expense_date', $dateRange)
                                ->get()
                                ->filter(function($expense) {
                                    return $expense->payment_status !== 'paid';
                                })
                                ->sum(function($expense) {
                                    return $expense->amount_base ?: $expense->amount;
                                }),
            'totalPaymentVouchers' => Payment::whereBetween('payment_date', $dateRange)->count(),
            'totalPaymentsAmount' => Payment::whereBetween('payment_date', $dateRange)->sum('amount'),
            'cashBankBalance' => Account::where('type', 'asset')
                                ->where('is_active', true)
                                ->where(function($q) {
                                    $q->where('code', 'like', '10%')
                                      ->orWhere('name', 'like', '%cash%')
                                      ->orWhere('name', 'like', '%bank%');
                                })
                                ->get()
                                ->sum(function($account) {
                                    return $account->calculateBalance('1900-01-01', now()->format('Y-m-d'));
                                }),
            'totalIncomeAccounts' => Account::where('type', 'income')
                                ->where('is_active', true)
                                ->get()
                                ->sum(function($account) use ($dateRange) {
                                    return $account->calculateBalance($dateRange[0], $dateRange[1]);
                                }),
            'programsData' => $this->getProgramsData($dateRange),
            'monthlyTrend' => $this->getMonthlyTrend(),
            'topCustomers' => Customer::withSum(['sales' => function($query) use ($dateRange) {
                    $query->whereBetween('sale_date', $dateRange);
                }], 'amount')
                ->orderByDesc('sales_sum_amount')
                ->limit(5)
                ->get(),
            'topExpenseCategories' => Expense::whereBetween('expense_date', $dateRange)
                ->select('category', DB::raw('SUM(amount_base) as total'))
                ->groupBy('category')
                ->orderByDesc('total')
                ->limit(5)
                ->get(),
            'currencyBreakdown' => $this->getCurrencyBreakdown($dateRange),
            
            // New dashboard data
            'recentExpenses' => Expense::with(['vendor', 'staff', 'program'])->latest()->limit(5)->get(),
            'recentSales' => Sale::with(['customer', 'program'])->latest()->limit(5)->get(),
            'recentPayments' => Payment::with(['expense.vendor', 'expense.staff', 'paymentAccount'])->latest()->limit(5)->get(),
            'topVendors' => Vendor::withSum(['expenses' => function($q) use ($dateRange) {
                    $q->whereBetween('expense_date', $dateRange);
                }], 'amount')
                ->orderByDesc('expenses_sum_amount')
                ->limit(5)
                ->get(),
            'cashFlowIn' => Sale::whereBetween('sale_date', $dateRange)->where('status', 'paid')->sum('amount_base') ?: 
                           Sale::whereBetween('sale_date', $dateRange)->where('status', 'paid')->sum('amount'),
            'cashFlowOut' => Payment::whereBetween('payment_date', $dateRange)->sum('amount'),
            'paymentStatusBreakdown' => $this->getPaymentStatusBreakdown($dateRange),
            'previousPeriodComparison' => $this->getPreviousPeriodComparison($dateRange),
            'lowCashWarning' => $this->checkLowCashBalance(),
            'baseCurrency' => $baseCurrency,
        ];
    }

    private function getDateRange(): array
    {
        return match($this->period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'this_week' => [now()->startOfWeek(), now()->endOfWeek()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'this_quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'this_year' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    private function getProgramsData(array $dateRange): array
    {
        return Program::with(['sales', 'expenses'])
            ->get()
            ->map(function ($program) use ($dateRange) {
                $income = $program->sales()
                    ->whereBetween('sale_date', $dateRange)
                    ->sum('amount_base') ?: $program->sales()
                    ->whereBetween('sale_date', $dateRange)
                    ->sum('amount');
                $expenses = $program->expenses()
                    ->whereBetween('expense_date', $dateRange)
                    ->sum('amount_base') ?: $program->expenses()
                    ->whereBetween('expense_date', $dateRange)
                    ->sum('amount');
                
                return [
                    'name' => $program->name,
                    'income' => $income,
                    'expenses' => $expenses,
                    'profit' => $income - $expenses,
                    'margin' => $income > 0 ? (($income - $expenses) / $income) * 100 : 0,
                ];
            })
            ->sortByDesc('profit')
            ->take(5)
            ->values()
            ->toArray();
    }

    private function getMonthlyTrend(): array
    {
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $startDate = $date->copy()->startOfMonth();
            $endDate = $date->copy()->endOfMonth();

            $income = Sale::whereBetween('sale_date', [$startDate, $endDate])->sum('amount_base') ?: 
                     Sale::whereBetween('sale_date', [$startDate, $endDate])->sum('amount');
            $expenses = Expense::whereBetween('expense_date', [$startDate, $endDate])->sum('amount_base') ?: 
                       Expense::whereBetween('expense_date', [$startDate, $endDate])->sum('amount');

            $months->push([
                'month' => $date->format('M'),
                'income' => $income,
                'expenses' => $expenses,
                'profit' => $income - $expenses,
            ]);
        }

        return $months->toArray();
    }

    private function getCurrencyBreakdown(array $dateRange): array
    {
        $currencies = Currency::where('is_active', true)->get();
        $breakdown = [];

        foreach ($currencies as $currency) {
            $income = Sale::where('currency', $currency->code)
                ->whereBetween('sale_date', $dateRange)
                ->sum('amount');
            
            $expenses = Expense::where('currency', $currency->code)
                ->whereBetween('expense_date', $dateRange)
                ->sum('amount');

            if ($income > 0 || $expenses > 0) {
                $breakdown[] = [
                    'code' => $currency->code,
                    'symbol' => $currency->symbol,
                    'income' => $income,
                    'expenses' => $expenses,
                    'net' => $income - $expenses,
                ];
            }
        }

        return $breakdown;
    }

    private function getPaymentStatusBreakdown(array $dateRange): array
    {
        $expenses = Expense::whereBetween('expense_date', $dateRange)->get();
        
        return [
            'paid' => [
                'count' => $expenses->filter(fn($e) => $e->payment_status === 'paid')->count(),
                'amount' => $expenses->filter(fn($e) => $e->payment_status === 'paid')->sum('amount'),
            ],
            'partial' => [
                'count' => $expenses->filter(fn($e) => $e->payment_status === 'partial')->count(),
                'amount' => $expenses->filter(fn($e) => $e->payment_status === 'partial')->sum('amount'),
            ],
            'unpaid' => [
                'count' => $expenses->filter(fn($e) => $e->payment_status === 'unpaid')->count(),
                'amount' => $expenses->filter(fn($e) => $e->payment_status === 'unpaid')->sum('amount'),
            ],
        ];
    }

    private function getPreviousPeriodComparison($currentRange): array
    {
        $start = \Carbon\Carbon::parse($currentRange[0]);
        $end = \Carbon\Carbon::parse($currentRange[1]);
        $diff = $start->diffInDays($end);
        
        $prevStart = $start->copy()->subDays($diff + 1);
        $prevEnd = $end->copy()->subDays($diff + 1);
        $prevRange = [$prevStart->format('Y-m-d'), $prevEnd->format('Y-m-d')];
        
        $currentIncome = Sale::whereBetween('sale_date', $currentRange)->sum('amount_base') ?: 
                        Sale::whereBetween('sale_date', $currentRange)->sum('amount');
        $prevIncome = Sale::whereBetween('sale_date', $prevRange)->sum('amount_base') ?: 
                     Sale::whereBetween('sale_date', $prevRange)->sum('amount');
        
        $currentExpenses = Expense::whereBetween('expense_date', $currentRange)->sum('amount_base') ?: 
                          Expense::whereBetween('expense_date', $currentRange)->sum('amount');
        $prevExpenses = Expense::whereBetween('expense_date', $prevRange)->sum('amount_base') ?: 
                       Expense::whereBetween('expense_date', $prevRange)->sum('amount');
        
        return [
            'income' => [
                'current' => $currentIncome,
                'previous' => $prevIncome,
                'change' => $prevIncome > 0 ? (($currentIncome - $prevIncome) / $prevIncome) * 100 : 0,
            ],
            'expenses' => [
                'current' => $currentExpenses,
                'previous' => $prevExpenses,
                'change' => $prevExpenses > 0 ? (($currentExpenses - $prevExpenses) / $prevExpenses) * 100 : 0,
            ],
            'profit' => [
                'current' => $currentIncome - $currentExpenses,
                'previous' => $prevIncome - $prevExpenses,
                'change' => ($prevIncome - $prevExpenses) > 0 ? 
                           ((($currentIncome - $currentExpenses) - ($prevIncome - $prevExpenses)) / ($prevIncome - $prevExpenses)) * 100 : 0,
            ],
        ];
    }

    private function checkLowCashBalance(): bool
    {
        $cashBalance = Account::where('type', 'asset')
            ->where('is_active', true)
            ->where(function($q) {
                $q->where('code', 'like', '10%')
                  ->orWhere('name', 'like', '%cash%')
                  ->orWhere('name', 'like', '%bank%');
            })
            ->get()
            ->sum(function($account) {
                return $account->calculateBalance('1900-01-01', now()->format('Y-m-d'));
            });
        
        // Warning if cash balance is less than 10% of monthly expenses
        $monthlyExpenses = Expense::whereBetween('expense_date', [
            now()->startOfMonth()->format('Y-m-d'),
            now()->endOfMonth()->format('Y-m-d')
        ])->sum('amount_base') ?: Expense::whereBetween('expense_date', [
            now()->startOfMonth()->format('Y-m-d'),
            now()->endOfMonth()->format('Y-m-d')
        ])->sum('amount');
        
        return $cashBalance < ($monthlyExpenses * 0.1);
    }

    public function updatedPeriod(): void
    {
        // Refresh data
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">
                Dashboard
            </h1>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Welcome back! Here's your accounting overview.</p>
        </div>
        
        <div>
            <flux:select wire:model.live="period" class="w-44">
                <option value="today">Today</option>
                <option value="this_week">This Week</option>
                <option value="this_month">This Month</option>
                <option value="this_quarter">This Quarter</option>
                <option value="this_year">This Year</option>
            </flux:select>
        </div>
    </div>

    <!-- Quick Actions Panel -->
    <div class="bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 rounded-lg p-6 shadow-lg">
        <h2 class="text-lg font-semibold text-white mb-4">Quick Actions</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <a href="{{ route('sales.create') }}" 
               class="flex flex-col items-center justify-center p-4 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-lg transition-all text-white group"
               wire:navigate>
                <svg class="w-8 h-8 mb-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <span class="text-sm font-medium">Record Sale</span>
            </a>
            <a href="{{ route('expenses.create') }}" 
               class="flex flex-col items-center justify-center p-4 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-lg transition-all text-white group"
               wire:navigate>
                <svg class="w-8 h-8 mb-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm font-medium">Record Expense</span>
            </a>
            <a href="{{ route('journal-entries.create') }}" 
               class="flex flex-col items-center justify-center p-4 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-lg transition-all text-white group"
               wire:navigate>
                <svg class="w-8 h-8 mb-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span class="text-sm font-medium">Journal Entry</span>
            </a>
            <a href="{{ route('cashbook.index') }}" 
               class="flex flex-col items-center justify-center p-4 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-lg transition-all text-white group"
               wire:navigate>
                <svg class="w-8 h-8 mb-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm font-medium">View Cashbook</span>
            </a>
        </div>
    </div>

    <!-- Key Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Income Card -->
        <div class="bg-white dark:bg-zinc-800 p-5 rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2.5 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
            </div>
            <h3 class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Total Income</h3>
            <p class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $baseCurrency->symbol }} {{ number_format($totalIncome, 0) }}</p>
        </div>

        <!-- Total Expenses Card -->
        <div class="bg-white dark:bg-zinc-800 p-5 rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2.5 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                    </svg>
                </div>
            </div>
            <h3 class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Total Expenses</h3>
            <p class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $baseCurrency->symbol }} {{ number_format($totalExpenses, 0) }}</p>
        </div>

        <!-- Net Profit Card -->
        <div class="bg-white dark:bg-zinc-800 p-5 rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2.5 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <span class="text-xs font-medium px-2 py-1 rounded {{ $profit >= 0 ? 'text-green-700 bg-green-50 dark:bg-green-900/30 dark:text-green-400' : 'text-red-700 bg-red-50 dark:bg-red-900/30 dark:text-red-400' }}">
                    {{ $totalIncome > 0 ? number_format(($profit / $totalIncome) * 100, 1) : 0 }}%
                </span>
            </div>
            <h3 class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Net Profit</h3>
            <p class="text-2xl font-semibold {{ $profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $baseCurrency->symbol }} {{ number_format($profit, 0) }}
            </p>
        </div>

        <!-- Active Programs Card -->
        <div class="bg-white dark:bg-zinc-800 p-5 rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2.5 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
            </div>
            <h3 class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Active Programs</h3>
            <p class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $activePrograms }}</p>
        </div>
    </div>

    <!-- Additional Financial Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Payment Vouchers Card -->
        <div class="bg-white dark:bg-zinc-800 p-5 rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2.5 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
            <h3 class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Payment Vouchers</h3>
            <p class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $totalPaymentVouchers }}</p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">{{ $baseCurrency->symbol }} {{ number_format($totalPaymentsAmount, 0) }} paid</p>
        </div>

        <!-- Cash/Bank Balance Card -->
        <div class="bg-white dark:bg-zinc-800 p-5 rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2.5 bg-cyan-50 dark:bg-cyan-900/20 rounded-lg">
                    <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
            <h3 class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Cash & Bank Balance</h3>
            <p class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $baseCurrency->symbol }} {{ number_format($cashBankBalance, 0) }}</p>
            <a href="{{ route('cashbook.index') }}" class="text-xs text-blue-600 dark:text-blue-400 hover:underline mt-1 inline-block">View Cashbook</a>
        </div>

        <!-- Total Income Accounts Card -->
        <div class="bg-white dark:bg-zinc-800 p-5 rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2.5 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <h3 class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Income Accounts Balance</h3>
            <p class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $baseCurrency->symbol }} {{ number_format($totalIncomeAccounts, 0) }}</p>
            <a href="{{ route('general-ledger') }}" class="text-xs text-blue-600 dark:text-blue-400 hover:underline mt-1 inline-block">View Ledger</a>
        </div>

        <!-- Accounts Payable Card -->
        <div class="bg-white dark:bg-zinc-800 p-5 rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2.5 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                    <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                    </svg>
                </div>
            </div>
            <h3 class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">Accounts Payable</h3>
            <p class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $baseCurrency->symbol }} {{ number_format($pendingExpenses, 0) }}</p>
            <a href="{{ route('expenses.index') }}" class="text-xs text-blue-600 dark:text-blue-400 hover:underline mt-1 inline-block">View Expenses</a>
        </div>
    </div>

    <!-- Cash Flow Summary & Recent Transactions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Cash Flow Summary -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                Cash Flow Summary
            </h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <div>
                        <p class="text-xs text-green-700 dark:text-green-400 font-medium">Cash In</p>
                        <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ $baseCurrency->symbol }} {{ number_format($cashFlowIn, 0) }}</p>
                    </div>
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                    </svg>
                </div>
                <div class="flex justify-between items-center p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <div>
                        <p class="text-xs text-red-700 dark:text-red-400 font-medium">Cash Out</p>
                        <p class="text-xl font-bold text-red-600 dark:text-red-400">{{ $baseCurrency->symbol }} {{ number_format($cashFlowOut, 0) }}</p>
                    </div>
                    <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                    </svg>
                </div>
                <div class="flex justify-between items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border-2 border-blue-200 dark:border-blue-800">
                    <div>
                        <p class="text-xs text-blue-700 dark:text-blue-400 font-medium">Net Cash Flow</p>
                        <p class="text-xl font-bold {{ ($cashFlowIn - $cashFlowOut) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $baseCurrency->symbol }} {{ number_format($cashFlowIn - $cashFlowOut, 0) }}
                        </p>
                    </div>
                    <svg class="w-8 h-8 {{ ($cashFlowIn - $cashFlowOut) >= 0 ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="lg:col-span-2 bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Recent Transactions
                </h3>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    @foreach($recentExpenses->take(3) as $expense)
                    <div class="flex items-center justify-between p-3 hover:bg-zinc-50 dark:hover:bg-zinc-900/50 rounded-lg transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $expense->description }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $expense->expense_date->format('M d, Y') }} • {{ $expense->vendor->name ?? $expense->staff->first_name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <span class="text-sm font-semibold text-red-600 dark:text-red-400">-{{ $baseCurrency->symbol }}{{ number_format($expense->amount, 0) }}</span>
                    </div>
                    @endforeach
                    
                    @foreach($recentSales->take(2) as $sale)
                    <div class="flex items-center justify-between p-3 hover:bg-zinc-50 dark:hover:bg-zinc-900/50 rounded-lg transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $sale->description }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $sale->sale_date->format('M d, Y') }} • {{ $sale->customer->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <span class="text-sm font-semibold text-green-600 dark:text-green-400">+{{ $baseCurrency->symbol }}{{ number_format($sale->amount, 0) }}</span>
                    </div>
                    @endforeach
                </div>
                <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <a href="{{ route('general-ledger') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline font-medium">View all transactions →</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Outstanding Items -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-zinc-800 p-5 rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white flex items-center">
                    <span class="w-2 h-2 bg-orange-500 rounded-full mr-2"></span>
                    Unpaid Sales
                </h3>
                <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="text-3xl font-semibold text-orange-600 dark:text-orange-400 mb-2">
                {{ $baseCurrency->symbol }} {{ number_format($unpaidSales, 0) }}
            </p>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">Outstanding receivables</p>
            <a href="{{ route('sales.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition-colors">
                View Details
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>

        <div class="bg-white dark:bg-zinc-800 p-5 rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white flex items-center">
                    <span class="w-2 h-2 bg-purple-500 rounded-full mr-2"></span>
                    Unpaid Expenses
                </h3>
                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="text-3xl font-semibold text-purple-600 dark:text-purple-400 mb-2">
                {{ $baseCurrency->symbol }} {{ number_format($pendingExpenses, 0) }}
            </p>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">Outstanding payables (unpaid & partial)</p>
            <a href="{{ route('expenses.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white text-sm font-medium rounded-lg transition-colors">
                View Details
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
    </div>

    <!-- Currency Breakdown -->
    @if(count($currencyBreakdown) > 0)
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Multi-Currency Overview</h3>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Transactions by currency in their original amounts (Summary cards above show converted {{ $baseCurrency->code }} values)</p>
                </div>
                <svg class="w-6 h-6 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($currencyBreakdown as $currency)
                <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-2xl font-bold text-zinc-700 dark:text-zinc-300">{{ $currency['symbol'] }}</span>
                        <span class="text-xs font-semibold px-2.5 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-full">
                            {{ $currency['code'] }}
                        </span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-zinc-600 dark:text-zinc-400">Income</span>
                            <span class="text-sm font-semibold text-green-600 dark:text-green-400">
                                {{ $currency['symbol'] }} {{ number_format($currency['income'], 2) }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-zinc-600 dark:text-zinc-400">Expenses</span>
                            <span class="text-sm font-semibold text-red-600 dark:text-red-400">
                                {{ $currency['symbol'] }} {{ number_format($currency['expenses'], 2) }}
                            </span>
                        </div>
                        <div class="pt-2 border-t border-zinc-200 dark:border-zinc-700">
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Net</span>
                                <span class="text-base font-bold {{ $currency['net'] >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $currency['symbol'] }} {{ number_format($currency['net'], 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Program Performance Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Top Performing Programs</h3>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Financial overview by program</p>
                </div>
                <a href="{{ route('programs.index') }}" 
                   class="px-4 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 border border-blue-200 dark:border-blue-800 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                    View All
                </a>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                            Program
                        </th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                            Income
                        </th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                            Expenses
                        </th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                            Profit
                        </th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                            Margin
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($programsData as $program)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/30 transition-colors">
                        <td class="px-5 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-9 w-9 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-700 dark:text-blue-300 font-semibold text-sm">
                                    {{ substr($program['name'], 0, 1) }}
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-zinc-900 dark:text-white">
                                        {{ $program['name'] }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-right">
                            <div class="text-sm font-medium text-green-600 dark:text-green-400">
                                {{ $baseCurrency->symbol }} {{ number_format($program['income'], 0) }}
                            </div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-right">
                            <div class="text-sm font-medium text-red-600 dark:text-red-400">
                                {{ $baseCurrency->symbol }} {{ number_format($program['expenses'], 0) }}
                            </div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-right">
                            <div class="text-sm font-semibold {{ $program['profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $baseCurrency->symbol }} {{ number_format($program['profit'], 0) }}
                            </div>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-right">
                            <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium
                                {{ $program['margin'] >= 30 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 
                                   ($program['margin'] >= 10 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' : 
                                   'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400') }}">
                                {{ number_format($program['margin'], 1) }}%
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 text-zinc-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-zinc-500 dark:text-zinc-400 font-medium mb-3">No programs found</p>
                                <a href="{{ route('programs.create') }}" 
                                   class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                    Create First Program
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Monthly Trend Chart -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-white">6-Month Trend</h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Revenue performance over time</p>
                    </div>
                    <svg class="w-8 h-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
            </div>
            <div class="p-5">
                <div class="space-y-4">
                    @foreach ($monthlyTrend as $month)
                        <div>
                            <div class="flex justify-between items-center mb-1.5">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $month['month'] }}</span>
                                <span class="text-sm font-semibold {{ $month['profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $baseCurrency->symbol }} {{ number_format($month['profit'], 0) }}
                                </span>
                            </div>
                            <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2.5 overflow-hidden">
                                <div class="bg-green-500 dark:bg-green-600 h-2.5 rounded-full transition-all" 
                                     style="width: {{ $month['income'] > 0 ? min(($month['income'] / max(array_column($monthlyTrend, 'income'))) * 100, 100) : 0 }}%">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Top Expense Categories Chart -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-white">Top Expense Categories</h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Spending breakdown by category</p>
                    </div>
                    <svg class="w-8 h-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                    </svg>
                </div>
            </div>
            <div class="p-5">
                <div class="space-y-4">
                    @php
                        $maxExpense = $topExpenseCategories->max('total') ?: 1;
                    @endphp
                    @forelse ($topExpenseCategories as $category)
                        <div>
                            <div class="flex justify-between items-center mb-1.5">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ $category->category ?: 'Uncategorized' }}
                                </span>
                                <span class="text-sm font-semibold text-red-600 dark:text-red-400">
                                    {{ $baseCurrency->symbol }} {{ number_format($category->total, 0) }}
                                </span>
                            </div>
                            <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2.5 overflow-hidden">
                                <div class="bg-red-500 dark:bg-red-600 h-2.5 rounded-full transition-all" 
                                     style="width: {{ ($category->total / $maxExpense) * 100 }}%">
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-8">
                            <svg class="w-12 h-12 text-zinc-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-center text-zinc-500 dark:text-zinc-400 text-sm">No expense data available</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
