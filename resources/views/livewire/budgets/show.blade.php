<?php

use App\Models\ProgramBudget;
use App\Models\Currency;
use App\Models\Expense;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public ProgramBudget $budget;
    public string $activeTab = 'overview';
    public array $expandedCategories = [];

    public function mount(int $id): void
    {
        $this->budget = ProgramBudget::with(['program', 'approvedBy'])->findOrFail($id);
    }

    public function toggleCategory(string $categoryKey): void
    {
        if (in_array($categoryKey, $this->expandedCategories)) {
            $this->expandedCategories = array_filter($this->expandedCategories, fn($k) => $k !== $categoryKey);
        } else {
            $this->expandedCategories[] = $categoryKey;
        }
    }

    public function activate(): void
    {
        if ($this->budget->status !== 'approved') {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Only approved budgets can be activated.'
            ]);
            return;
        }

        $this->budget->update(['status' => 'active']);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Budget activated successfully.'
        ]);

        $this->budget->refresh();
    }

    public function close(): void
    {
        if ($this->budget->status !== 'active') {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Only active budgets can be closed.'
            ]);
            return;
        }

        $this->budget->update(['status' => 'closed']);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Budget closed successfully.'
        ]);

        $this->budget->refresh();
    }

    public function getExpenseBreakdownProperty(): array
    {
        $categories = Expense::getCategories();
        $breakdown = [];
        
        // Calculate estimated budget per category (equally distributed for now)
        $budgetPerCategory = $this->budget->expense_budget / count($categories);
        
        foreach ($categories as $key => $label) {
            // Get actual expenses for this category and program within budget period
            $expenses = Expense::with(['vendor'])
                ->where('program_id', $this->budget->program_id)
                ->where('category', $key)
                ->whereBetween('expense_date', [$this->budget->start_date, $this->budget->end_date])
                ->orderBy('expense_date', 'desc')
                ->get();
            
            $actualSpent = $expenses->sum('amount');
            $expenseCount = $expenses->count();
            
            $utilization = $budgetPerCategory > 0 ? ($actualSpent / $budgetPerCategory) * 100 : 0;
            $variance = $actualSpent - $budgetPerCategory;
            
            // Determine alert level
            if ($utilization >= 90) {
                $alertLevel = 'red';
            } elseif ($utilization >= 75) {
                $alertLevel = 'yellow';
            } else {
                $alertLevel = 'green';
            }
            
            $breakdown[] = [
                'key' => $key,
                'label' => $label,
                'budgeted' => $budgetPerCategory,
                'actual' => $actualSpent,
                'variance' => $variance,
                'utilization' => $utilization,
                'alert_level' => $alertLevel,
                'count' => $expenseCount,
                'expenses' => $expenses,
            ];
        }
        
        // Sort by utilization descending (highest spending first)
        usort($breakdown, fn($a, $b) => $b['utilization'] <=> $a['utilization']);
        
        return $breakdown;
    }

    public function with(): array
    {
        return [
            'baseCurrency' => Currency::getBaseCurrency(),
        ];
    }
}; ?>

<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">
                {{ $budget->program->name }} Budget
            </h1>
            <p class="text-zinc-600 dark:text-zinc-400 mt-1">
                {{ ucfirst($budget->period_type) }} • {{ $budget->start_date->format('M d, Y') }} - {{ $budget->end_date->format('M d, Y') }}
            </p>
        </div>
        
        <div class="flex items-center gap-3">
            <span class="px-3 py-1.5 rounded text-sm font-semibold bg-{{ $budget->status_color }}-100 dark:bg-{{ $budget->status_color }}-900/30 text-{{ $budget->status_color }}-700 dark:text-{{ $budget->status_color }}-400">
                {{ ucfirst($budget->status) }}
            </span>
            @if($budget->status === 'active')
                <span class="px-3 py-1.5 rounded text-sm font-semibold bg-{{ $budget->alert_level }}-100 dark:bg-{{ $budget->alert_level }}-900/30 text-{{ $budget->alert_level }}-700 dark:text-{{ $budget->alert_level }}-400">
                    {{ strtoupper($budget->alert_level) }} LEVEL
                </span>
            @endif
        </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center gap-3">
        <a href="{{ route('budgets.index') }}" 
           class="px-4 py-2 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Budgets
        </a>
        
        @if($budget->status === 'draft')
            <a href="{{ route('budgets.edit', $budget) }}" 
               class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                Edit Budget
            </a>
        @endif
        
        @if($budget->status === 'approved')
            <button wire:click="activate"
                    wire:confirm="Activate this budget?"
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                Activate Budget
            </button>
        @endif
        
        @if($budget->status === 'active')
            <button wire:click="close"
                    wire:confirm="Close this budget? This cannot be undone."
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                Close Budget
            </button>
        @endif
    </div>

    <!-- Alert Banner -->
    @if($budget->status === 'active' && $budget->needsAlert())
        <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-600 p-4 rounded-lg">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="flex-1">
                    <h3 class="font-semibold text-red-900 dark:text-red-300">Budget Alert!</h3>
                    <p class="text-sm text-red-700 dark:text-red-400 mt-1">
                        Spending is {{ number_format($budget->expense_utilization, 1) }}% of budget with {{ $budget->days_remaining }} days remaining 
                        ({{ number_format($budget->days_elapsed_percentage, 1) }}% of period elapsed).
                        Consider reviewing expenses or requesting a budget adjustment.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Tabs Navigation -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="flex border-b border-zinc-200 dark:border-zinc-700">
            <button wire:click="$set('activeTab', 'overview')"
                    class="px-6 py-4 text-sm font-semibold transition-colors {{ $activeTab === 'overview' ? 'text-indigo-600 dark:text-indigo-400 border-b-2 border-indigo-600 dark:border-indigo-400' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200' }}">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Overview
            </button>
            <button wire:click="$set('activeTab', 'breakdown')"
                    class="px-6 py-4 text-sm font-semibold transition-colors {{ $activeTab === 'breakdown' ? 'text-indigo-600 dark:text-indigo-400 border-b-2 border-indigo-600 dark:border-indigo-400' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200' }}">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                Expense Breakdown
            </button>
        </div>

        <!-- Overview Tab Content -->
        <div class="p-6" x-show="$wire.activeTab === 'overview'" x-cloak>
    <!-- Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <!-- Time Progress -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">Period Progress</div>
                    <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($budget->days_elapsed_percentage, 0) }}%</div>
                </div>
            </div>
            @if($budget->status === 'active')
                <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2 mb-2">
                    <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $budget->days_elapsed_percentage }}%"></div>
                </div>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $budget->days_remaining }} days remaining</p>
            @endif
        </div>

        <!-- Income Progress -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
                <div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">Income Achievement</div>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($budget->income_utilization, 0) }}%</div>
                </div>
            </div>
            @if($budget->status === 'active')
                <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2 mb-2">
                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ min($budget->income_utilization, 100) }}%"></div>
                </div>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    {{ $baseCurrency->symbol }} {{ number_format($budget->actual_income, 0) }} / {{ number_format($budget->income_budget, 0) }}
                </p>
            @endif
        </div>

        <!-- Expense Progress -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-{{ $budget->alert_level === 'red' ? 'red' : ($budget->alert_level === 'yellow' ? 'yellow' : 'blue') }}-100 dark:bg-{{ $budget->alert_level === 'red' ? 'red' : ($budget->alert_level === 'yellow' ? 'yellow' : 'blue') }}-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-{{ $budget->alert_level === 'red' ? 'red' : ($budget->alert_level === 'yellow' ? 'yellow' : 'blue') }}-600 dark:text-{{ $budget->alert_level === 'red' ? 'red' : ($budget->alert_level === 'yellow' ? 'yellow' : 'blue') }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                    </svg>
                </div>
                <div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">Budget Utilization</div>
                    <div class="text-2xl font-bold text-{{ $budget->alert_level === 'red' ? 'red' : ($budget->alert_level === 'yellow' ? 'yellow' : 'blue') }}-600 dark:text-{{ $budget->alert_level === 'red' ? 'red' : ($budget->alert_level === 'yellow' ? 'yellow' : 'blue') }}-400">
                        {{ number_format($budget->expense_utilization, 0) }}%
                    </div>
                </div>
            </div>
            @if($budget->status === 'active')
                <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2 mb-2">
                    <div class="bg-{{ $budget->alert_level === 'red' ? 'red' : ($budget->alert_level === 'yellow' ? 'yellow' : 'blue') }}-500 h-2 rounded-full" style="width: {{ min($budget->expense_utilization, 100) }}%"></div>
                </div>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    {{ $baseCurrency->symbol }} {{ number_format($budget->actual_expenses, 0) }} / {{ number_format($budget->expense_budget, 0) }}
                </p>
            @endif
        </div>
    </div>

    <!-- Detailed Stats -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Income Details -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-700 bg-green-50 dark:bg-green-900/20">
                <h3 class="text-lg font-semibold text-green-900 dark:text-green-300">Income Budget</h3>
            </div>
            <div class="p-5 space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-zinc-600 dark:text-zinc-400">Budgeted:</span>
                    <span class="text-lg font-bold text-zinc-900 dark:text-white">
                        {{ $budget->currency }} {{ number_format($budget->income_budget, 2) }}
                    </span>
                </div>
                @if($budget->status === 'active')
                    <div class="flex justify-between items-center">
                        <span class="text-zinc-600 dark:text-zinc-400">Actual:</span>
                        <span class="text-lg font-bold text-green-600 dark:text-green-400">
                            {{ $budget->currency }} {{ number_format($budget->actual_income, 2) }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center pt-3 border-t border-zinc-200 dark:border-zinc-700">
                        <span class="text-zinc-600 dark:text-zinc-400">Variance:</span>
                        <span class="text-lg font-bold {{ $budget->income_variance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $budget->income_variance >= 0 ? '+' : '' }}{{ $budget->currency }} {{ number_format($budget->income_variance, 2) }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-zinc-600 dark:text-zinc-400">Achievement:</span>
                        <span class="text-lg font-bold text-zinc-900 dark:text-white">
                            {{ number_format($budget->income_utilization, 2) }}%
                        </span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Expense Details -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-700 bg-red-50 dark:bg-red-900/20">
                <h3 class="text-lg font-semibold text-red-900 dark:text-red-300">Expense Budget</h3>
            </div>
            <div class="p-5 space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-zinc-600 dark:text-zinc-400">Budgeted:</span>
                    <span class="text-lg font-bold text-zinc-900 dark:text-white">
                        {{ $budget->currency }} {{ number_format($budget->expense_budget, 2) }}
                    </span>
                </div>
                @if($budget->status === 'active')
                    <div class="flex justify-between items-center">
                        <span class="text-zinc-600 dark:text-zinc-400">Spent:</span>
                        <span class="text-lg font-bold text-red-600 dark:text-red-400">
                            {{ $budget->currency }} {{ number_format($budget->actual_expenses, 2) }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center pt-3 border-t border-zinc-200 dark:border-zinc-700">
                        <span class="text-zinc-600 dark:text-zinc-400">Variance:</span>
                        <span class="text-lg font-bold {{ $budget->expense_variance <= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $budget->expense_variance >= 0 ? '+' : '' }}{{ $budget->currency }} {{ number_format($budget->expense_variance, 2) }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-zinc-600 dark:text-zinc-400">Utilization:</span>
                        <span class="text-lg font-bold text-zinc-900 dark:text-white">
                            {{ number_format($budget->expense_utilization, 2) }}%
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-zinc-600 dark:text-zinc-400">Remaining:</span>
                        <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
                            {{ $budget->currency }} {{ number_format($budget->expense_budget - $budget->actual_expenses, 2) }}
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Notes -->
    @if($budget->notes)
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-5">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-3">Notes</h3>
            <p class="text-zinc-600 dark:text-zinc-400">{{ $budget->notes }}</p>
        </div>
    @endif

    <!-- Metadata -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-5">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Budget Information</h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-zinc-600 dark:text-zinc-400">Created:</span>
                <span class="text-zinc-900 dark:text-white font-medium ml-2">{{ $budget->created_at->format('M d, Y') }}</span>
            </div>
            @if($budget->approved_by)
                <div>
                    <span class="text-zinc-600 dark:text-zinc-400">Approved by:</span>
                    <span class="text-zinc-900 dark:text-white font-medium ml-2">{{ $budget->approvedBy->name }}</span>
                </div>
            @endif
        </div>
    </div>
        </div>

        <!-- Expense Breakdown Tab Content -->
        <div class="p-6" x-show="$wire.activeTab === 'breakdown'" x-cloak>
            <div class="space-y-6">
                <!-- Breakdown Header -->
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-zinc-900 dark:text-white">Expense Utilization by Category</h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                            Detailed breakdown showing how budget was spent across different expense categories
                        </p>
                    </div>
                    <a href="{{ route('expenses.index') }}?program={{ $budget->program_id }}&from={{ $budget->start_date->format('Y-m-d') }}&to={{ $budget->end_date->format('Y-m-d') }}" 
                       class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors text-sm font-semibold inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        View All Expenses
                    </a>
                </div>

                <!-- Category Breakdown Cards -->
                @foreach($this->expenseBreakdown as $category)
                    <div class="bg-white dark:bg-zinc-800 rounded-xl border-2 border-{{ $category['alert_level'] }}-200 dark:border-{{ $category['alert_level'] }}-800 overflow-hidden hover:shadow-lg transition-shadow">
                        <!-- Category Header -->
                        <div class="bg-gradient-to-r from-{{ $category['alert_level'] }}-50 to-{{ $category['alert_level'] }}-100 dark:from-{{ $category['alert_level'] }}-900/20 dark:to-{{ $category['alert_level'] }}-900/10 px-6 py-4 border-b border-{{ $category['alert_level'] }}-200 dark:border-{{ $category['alert_level'] }}-800">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-{{ $category['alert_level'] }}-100 dark:bg-{{ $category['alert_level'] }}-900/30 rounded-lg">
                                        <svg class="w-6 h-6 text-{{ $category['alert_level'] }}-600 dark:text-{{ $category['alert_level'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-bold text-zinc-900 dark:text-white">{{ $category['label'] }}</h4>
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $category['count'] }} expense(s) recorded</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-{{ $category['alert_level'] }}-600 dark:text-{{ $category['alert_level'] }}-400">
                                        {{ number_format($category['utilization'], 1) }}%
                                    </div>
                                    <div class="text-xs text-zinc-600 dark:text-zinc-400 font-semibold uppercase tracking-wider">
                                        {{ $category['utilization'] >= 90 ? 'Critical' : ($category['utilization'] >= 75 ? 'Warning' : 'On Track') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Category Details -->
                        <div class="p-6">
                            <!-- Progress Bar -->
                            <div class="mb-4">
                                <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-3">
                                    <div class="bg-{{ $category['alert_level'] }}-500 h-3 rounded-full transition-all duration-500" 
                                         style="width: {{ min($category['utilization'], 100) }}%"></div>
                                </div>
                            </div>

                            <!-- Financial Details Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <!-- Budgeted Amount -->
                                <div class="bg-zinc-50 dark:bg-zinc-900/50 rounded-lg p-4">
                                    <div class="text-xs text-zinc-600 dark:text-zinc-400 uppercase tracking-wider mb-1">Budgeted</div>
                                    <div class="text-lg font-bold text-zinc-900 dark:text-white">
                                        {{ $budget->currency }} {{ number_format($category['budgeted'], 0) }}
                                    </div>
                                </div>

                                <!-- Actual Spent -->
                                <div class="bg-{{ $category['alert_level'] }}-50 dark:bg-{{ $category['alert_level'] }}-900/20 rounded-lg p-4">
                                    <div class="text-xs text-{{ $category['alert_level'] }}-600 dark:text-{{ $category['alert_level'] }}-400 uppercase tracking-wider mb-1">Actual Spent</div>
                                    <div class="text-lg font-bold text-{{ $category['alert_level'] }}-600 dark:text-{{ $category['alert_level'] }}-400">
                                        {{ $budget->currency }} {{ number_format($category['actual'], 0) }}
                                    </div>
                                </div>

                                <!-- Variance -->
                                <div class="bg-zinc-50 dark:bg-zinc-900/50 rounded-lg p-4">
                                    <div class="text-xs text-zinc-600 dark:text-zinc-400 uppercase tracking-wider mb-1">Variance</div>
                                    <div class="text-lg font-bold {{ $category['variance'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                        {{ $category['variance'] >= 0 ? '+' : '' }}{{ $budget->currency }} {{ number_format($category['variance'], 0) }}
                                    </div>
                                </div>

                                <!-- Remaining -->
                                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                                    <div class="text-xs text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-1">Remaining</div>
                                    <div class="text-lg font-bold text-blue-600 dark:text-blue-400">
                                        {{ $budget->currency }} {{ number_format(max($category['budgeted'] - $category['actual'], 0), 0) }}
                                    </div>
                                </div>
                            </div>

                            <!-- View Details Link -->
                            @if($category['count'] > 0)
                                <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                                    <button wire:click="toggleCategory('{{ $category['key'] }}')"
                                            class="w-full inline-flex items-center justify-between text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors">
                                        <span class="flex items-center gap-2">
                                            <svg class="w-4 h-4 transform transition-transform {{ in_array($category['key'], $expandedCategories) ? 'rotate-90' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                            {{ in_array($category['key'], $expandedCategories) ? 'Hide' : 'View' }} {{ $category['count'] }} {{ $category['label'] }} expense(s)
                                        </span>
                                    </button>

                                    @if(in_array($category['key'], $expandedCategories))
                                        <div class="mt-4 space-y-2 animate-fadeIn">
                                            @foreach($category['expenses'] as $expense)
                                                <a href="{{ route('expenses.index') }}?expense_id={{ $expense->id }}" 
                                                   class="block bg-zinc-50 dark:bg-zinc-900/50 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:border-indigo-300 dark:hover:border-indigo-700 transition-all cursor-pointer">
                                                    <div class="flex items-start justify-between">
                                                        <div class="flex-1">
                                                            <div class="flex items-center gap-2 mb-1">
                                                                <h5 class="font-semibold text-zinc-900 dark:text-white">{{ $expense->description }}</h5>
                                                                @if($expense->payment_status === 'paid')
                                                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                                                        Paid
                                                                    </span>
                                                                @elseif($expense->payment_status === 'partial')
                                                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                                                                        Partial
                                                                    </span>
                                                                @else
                                                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                                                        Unpaid
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            <div class="flex items-center gap-4 text-sm text-zinc-600 dark:text-zinc-400">
                                                                <span class="flex items-center gap-1">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                    </svg>
                                                                    {{ $expense->expense_date->format('M d, Y') }}
                                                                </span>
                                                                @if($expense->vendor)
                                                                    <span class="flex items-center gap-1">
                                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                                        </svg>
                                                                        {{ $expense->vendor->name }}
                                                                    </span>
                                                                @endif
                                                                @if($expense->payment_reference)
                                                                    <span class="flex items-center gap-1">
                                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                                        </svg>
                                                                        {{ $expense->payment_reference }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="text-right ml-4">
                                                            <div class="text-lg font-bold text-zinc-900 dark:text-white">
                                                                {{ $expense->currency }} {{ number_format($expense->amount, 0) }}
                                                            </div>
                                                            @if($expense->payment_date)
                                                                <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                                                    Paid: {{ $expense->payment_date->format('M d, Y') }}
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="mt-2 flex items-center text-xs text-indigo-600 dark:text-indigo-400 font-semibold">
                                                        Click to view full details
                                                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                        </svg>
                                                    </div>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

                @if(empty($this->expenseBreakdown))
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <p class="mt-4 text-zinc-600 dark:text-zinc-400">No expense data available for this budget period</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
