<?php

use App\Models\Program;
use App\Models\Sale;
use App\Models\Expense;
use App\Models\Customer;
use App\Models\Vendor;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public string $period = 'this_month';

    public function with(): array
    {
        $dateRange = $this->getDateRange();

        return [
            'totalIncome' => Sale::whereBetween('sale_date', $dateRange)->sum('amount'),
            'totalExpenses' => Expense::whereBetween('expense_date', $dateRange)->sum('amount'),
            'profit' => Sale::whereBetween('sale_date', $dateRange)->sum('amount') - 
                       Expense::whereBetween('expense_date', $dateRange)->sum('amount'),
            'activePrograms' => Program::where('status', 'active')->count(),
            'unpaidSales' => Sale::where('status', '!=', 'paid')->sum('amount'),
            'pendingExpenses' => Expense::where('status', 'pending')->sum('amount'),
            'programsData' => $this->getProgramsData($dateRange),
            'monthlyTrend' => $this->getMonthlyTrend(),
            'topCustomers' => Customer::withSum('sales', 'amount')
                ->orderByDesc('sales_sum_amount')
                ->limit(5)
                ->get(),
            'topExpenseCategories' => Expense::whereBetween('expense_date', $dateRange)
                ->select('category', DB::raw('SUM(amount) as total'))
                ->groupBy('category')
                ->orderByDesc('total')
                ->limit(5)
                ->get(),
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
                    ->sum('amount');
                $expenses = $program->expenses()
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

            $income = Sale::whereBetween('sale_date', [$startDate, $endDate])->sum('amount');
            $expenses = Expense::whereBetween('expense_date', [$startDate, $endDate])->sum('amount');

            $months->push([
                'month' => $date->format('M'),
                'income' => $income,
                'expenses' => $expenses,
                'profit' => $income - $expenses,
            ]);
        }

        return $months->toArray();
    }

    public function updatedPeriod(): void
    {
        // Refresh data
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">
                Dashboard
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Welcome back! Here's your accounting overview.</p>
        </div>
        
        <div class="flex items-center gap-4">
            <flux:select wire:model.live="period" class="w-48">
                <option value="today">Today</option>
                <option value="this_week">This Week</option>
                <option value="this_month">This Month</option>
                <option value="this_quarter">This Quarter</option>
                <option value="this_year">This Year</option>
            </flux:select>
        </div>
    </div>

    <!-- Key Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8">
        <!-- Total Income Card -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
                <span class="text-xs font-semibold text-green-700 bg-green-50 dark:bg-green-900/30 dark:text-green-400 px-2.5 py-1 rounded">+12%</span>
            </div>
            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Total Income</h3>
            <p class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">UGX {{ number_format($totalIncome, 0) }}</p>
            <div class="mt-4 flex items-center text-xs text-gray-500 dark:text-gray-400">
                <svg class="w-4 h-4 mr-1 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
                </svg>
                <span>From last period</span>
            </div>
        </div>

        <!-- Total Expenses Card -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                    </svg>
                </div>
                <span class="text-xs font-semibold text-red-700 bg-red-50 dark:bg-red-900/30 dark:text-red-400 px-2.5 py-1 rounded">-5%</span>
            </div>
            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Total Expenses</h3>
            <p class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">UGX {{ number_format($totalExpenses, 0) }}</p>
            <div class="mt-4 flex items-center text-xs text-gray-500 dark:text-gray-400">
                <svg class="w-4 h-4 mr-1 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12 13a1 1 0 100 2h5a1 1 0 001-1V9a1 1 0 10-2 0v2.586l-4.293-4.293a1 1 0 00-1.414 0L8 9.586 3.707 5.293a1 1 0 00-1.414 1.414l5 5a1 1 0 001.414 0L11 9.414 14.586 13H12z" clip-rule="evenodd" />
                </svg>
                <span>From last period</span>
            </div>
        </div>

        <!-- Net Profit Card -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <span class="text-xs font-semibold {{ $profit >= 0 ? 'text-green-700 bg-green-50 dark:bg-green-900/30 dark:text-green-400' : 'text-red-700 bg-red-50 dark:bg-red-900/30 dark:text-red-400' }} px-2.5 py-1 rounded">
                    {{ $profit >= 0 ? '+' : '' }}{{ $totalIncome > 0 ? number_format(($profit / $totalIncome) * 100, 1) : 0 }}%
                </span>
            </div>
            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Net Profit</h3>
            <p class="text-2xl md:text-3xl font-bold {{ $profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                UGX {{ number_format($profit, 0) }}
            </p>
            <div class="mt-4 flex items-center text-xs text-gray-500 dark:text-gray-400">
                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
                </svg>
                <span>Margin: {{ $totalIncome > 0 ? number_format(($profit / $totalIncome) * 100, 1) : 0 }}%</span>
            </div>
        </div>

        <!-- Active Programs Card -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg">
                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
            </div>
            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Active Programs</h3>
            <p class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">{{ $activePrograms }}</p>
            <div class="mt-4">
                <a href="{{ route('programs.index') }}" class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium flex items-center">
                    View all programs
                    <svg class="w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Outstanding Items -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-orange-50 dark:bg-orange-900/10 p-6 rounded-lg border-l-4 border-orange-500">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                    <span class="w-2 h-2 bg-orange-500 rounded-full mr-2 animate-pulse"></span>
                    Unpaid Sales
                </h3>
                <svg class="w-8 h-8 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="text-3xl md:text-4xl font-bold text-orange-600 dark:text-orange-400 mb-2">
                UGX {{ number_format($unpaidSales, 0) }}
            </p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Outstanding receivables requiring attention</p>
            <a href="{{ route('sales.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition-colors text-sm font-medium">
                View Details
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>

        <div class="bg-purple-50 dark:bg-purple-900/10 p-6 rounded-lg border-l-4 border-purple-500">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                    <span class="w-2 h-2 bg-purple-500 rounded-full mr-2 animate-pulse"></span>
                    Pending Expenses
                </h3>
                <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="text-3xl md:text-4xl font-bold text-purple-600 dark:text-purple-400 mb-2">
                UGX {{ number_format($pendingExpenses, 0) }}
            </p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Awaiting approval or payment processing</p>
            <a href="{{ route('expenses.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors text-sm font-medium">
                View Details
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
    </div>

    <!-- Program Performance Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-8">
        <div class="bg-gray-50 dark:bg-gray-900/50 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Top Performing Programs</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Financial overview by program</p>
                </div>
                <a href="{{ route('programs.index') }}" 
                   class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm font-medium">
                    View All Programs
                </a>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Program
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Income
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Expenses
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Profit
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Margin
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($programsData as $program)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold">
                                    {{ substr($program['name'], 0, 1) }}
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $program['name'] }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="text-sm font-semibold text-green-600 dark:text-green-400">
                                UGX {{ number_format($program['income'], 0) }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="text-sm font-semibold text-red-600 dark:text-red-400">
                                UGX {{ number_format($program['expenses'], 0) }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="text-sm font-bold {{ $program['profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                UGX {{ number_format($program['profit'], 0) }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                                {{ $program['margin'] >= 30 ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 
                                   ($program['margin'] >= 10 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : 
                                   'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400') }}">
                                {{ number_format($program['margin'], 1) }}%
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400 font-medium">No programs found</p>
                                <a href="{{ route('programs.create') }}" 
                                   class="mt-4 px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm font-medium">
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
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Monthly Trend Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-blue-600 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-white">6-Month Trend</h3>
                        <p class="text-blue-100 text-sm">Revenue performance over time</p>
                    </div>
                    <svg class="w-10 h-10 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @foreach ($monthlyTrend as $month)
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $month['month'] }}</span>
                                <span class="text-sm font-bold {{ $month['profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    UGX {{ number_format($month['profit'], 0) }}
                                </span>
                            </div>
                            <div class="relative w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                <div class="absolute inset-0 bg-green-500 dark:bg-green-600 h-3 rounded-full transition-all" 
                                     style="width: {{ $month['income'] > 0 ? min(($month['income'] / max(array_column($monthlyTrend, 'income'))) * 100, 100) : 0 }}%">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Top Expense Categories Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-red-600 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-white">Top Expense Categories</h3>
                        <p class="text-red-100 text-sm">Spending breakdown by category</p>
                    </div>
                    <svg class="w-10 h-10 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                    </svg>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @php
                        $maxExpense = $topExpenseCategories->max('total') ?: 1;
                    @endphp
                    @forelse ($topExpenseCategories as $category)
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ $category->category ?: 'Uncategorized' }}
                                </span>
                                <span class="text-sm font-bold text-red-600 dark:text-red-400">
                                    UGX {{ number_format($category->total, 0) }}
                                </span>
                            </div>
                            <div class="relative w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                <div class="absolute inset-0 bg-red-500 dark:bg-red-600 h-3 rounded-full transition-all" 
                                     style="width: {{ ($category->total / $maxExpense) * 100 }}%">
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-8">
                            <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-center text-gray-500 dark:text-gray-400 text-sm">No expense data available</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
