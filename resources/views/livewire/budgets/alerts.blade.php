<?php

use App\Models\ProgramBudget;
use App\Models\Currency;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $severity = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSeverity(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        // Build SQL expressions that work per driver (SQLite for dev, MySQL for prod)
        $driver = DB::getDriverName();

        // Correlated subquery for actual expenses in budget window
        $actualExpensesSql = 'COALESCE((
            SELECT SUM(e.amount_base)
            FROM expenses e
            WHERE e.program_id = program_budgets.program_id
              AND e.expense_date BETWEEN program_budgets.start_date AND program_budgets.end_date
        ), 0)';

        // Utilization expression: 100 * actual_expenses / expense_budget
        $utilExpr = "(CASE WHEN expense_budget = 0 THEN 0 ELSE ($actualExpensesSql / expense_budget) * 100 END)";

        // Days elapsed percentage expression
        if ($driver === 'mysql') {
            $elapsedExpr = 'LEAST(100, GREATEST(0, (DATEDIFF(NOW(), start_date) / NULLIF(DATEDIFF(end_date, start_date), 0)) * 100))';
        } else {
            // sqlite
            $elapsedExpr = 'MIN(100, MAX(0, ((julianday(CURRENT_TIMESTAMP) - julianday(start_date)) / NULLIF(julianday(end_date) - julianday(start_date), 0)) * 100))';
        }

        // Severity predicates
        $redWhere = "(($utilExpr) >= 90) OR (($utilExpr) > ($elapsedExpr + 20))";
        $yellowCandidate = "(($utilExpr) >= 70) OR (($utilExpr) > ($elapsedExpr + 10))";
        $yellowWhere = "(($yellowCandidate) AND NOT (($redWhere)))";

        // Base query with computed columns
        $query = ProgramBudget::query()
            ->select('*')
            ->addSelect([ 
                'actual_expenses_sql' => DB::raw($actualExpensesSql),
                'expense_utilization_sql' => DB::raw($utilExpr),
                'days_elapsed_percentage_sql' => DB::raw($elapsedExpr),
            ])
            ->with(['program'])
            ->where('status', 'active');

        if ($this->search) {
            $query->whereHas('program', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            });
        }

        // Apply severity filter if specified
        if ($this->severity === 'red') {
            $query->whereRaw($redWhere);
        } elseif ($this->severity === 'yellow') {
            $query->whereRaw($yellowWhere);
        } else {
            // Show budgets which meet at least yellow conditions
            $query->whereRaw($yellowCandidate);
        }

        // Order: red first, then by utilization desc
        $query->orderByRaw("CASE WHEN ($redWhere) THEN 1 ELSE 2 END")
              ->orderByRaw("$utilExpr DESC");

        $budgets = $query->paginate(15);

        // Counts for badges (rebuild minimal queries to avoid double pagination hits)
        $countBase = ProgramBudget::query()->where('status', 'active');
        if ($this->search) {
            $countBase->whereHas('program', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            });
        }
        $redCount = (clone $countBase)
            ->whereRaw($redWhere)
            ->selectRaw('COUNT(*) as c')
            ->value('c') ?? 0;

        $yellowCount = (clone $countBase)
            ->whereRaw($yellowWhere)
            ->selectRaw('COUNT(*) as c')
            ->value('c') ?? 0;

        return [
            'budgets' => $budgets,
            'baseCurrency' => Currency::getBaseCurrency(),
            'redCount' => $redCount,
            'yellowCount' => $yellowCount,
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Budget Alerts</h1>
            <p class="text-zinc-600 dark:text-zinc-400 mt-1">Budgets requiring immediate attention</p>
        </div>
        <a href="{{ route('budgets.index') }}" 
           class="px-4 py-2 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Budgets
        </a>
    </div>

    <!-- Alert Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-5">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $redCount }}</div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">Critical Alerts</div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-5">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $yellowCount }}</div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">Warning Alerts</div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-5">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $redCount + $yellowCount }}</div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">Total Alerts</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    Search Program
                </label>
                <input type="text" 
                       wire:model.live.debounce.300ms="search"
                       placeholder="Type to search..."
                       class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    Severity Level
                </label>
                <select wire:model.live="severity"
                        class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    <option value="">All Alerts</option>
                    <option value="red">Critical Only</option>
                    <option value="yellow">Warning Only</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Alert Cards -->
    @if($budgets->count() > 0)
        <div class="space-y-4">
            @foreach($budgets as $budget)
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-{{ $budget->alert_level }}-300 dark:border-{{ $budget->alert_level }}-700 p-5 hover:shadow-lg transition-shadow">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white">
                                    {{ $budget->program->name }}
                                </h3>
                                <span class="px-3 py-1 rounded text-sm font-semibold bg-{{ $budget->alert_level }}-100 dark:bg-{{ $budget->alert_level }}-900/30 text-{{ $budget->alert_level }}-700 dark:text-{{ $budget->alert_level }}-400">
                                    {{ strtoupper($budget->alert_level) }} ALERT
                                </span>
                            </div>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ ucfirst($budget->period_type) }} • {{ $budget->start_date->format('M d, Y') }} - {{ $budget->end_date->format('M d, Y') }}
                            </p>
                        </div>
                        <a href="{{ route('budgets.show', $budget) }}" 
                           class="px-4 py-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors">
                            View Details
                        </a>
                    </div>

                    <!-- Alert Message -->
                    <div class="bg-{{ $budget->alert_level }}-50 dark:bg-{{ $budget->alert_level }}-900/20 border-l-4 border-{{ $budget->alert_level }}-600 p-4 rounded-lg mb-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-{{ $budget->alert_level }}-600 dark:text-{{ $budget->alert_level }}-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm text-{{ $budget->alert_level }}-700 dark:text-{{ $budget->alert_level }}-400">
                                    <strong>{{ $budget->alert_level === 'red' ? 'Critical:' : 'Warning:' }}</strong>
                                    Spending at {{ number_format($budget->expense_utilization, 1) }}% of budget with {{ $budget->days_remaining }} days remaining 
                                    ({{ number_format($budget->days_elapsed_percentage, 1) }}% of period elapsed).
                                    @if($budget->expense_utilization - $budget->days_elapsed_percentage >= 10)
                                        Currently {{ number_format($budget->expense_utilization - $budget->days_elapsed_percentage, 1) }}% ahead of schedule.
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bars -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Time Progress -->
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Time Progress</span>
                                <span class="text-sm font-bold text-zinc-900 dark:text-white">{{ number_format($budget->days_elapsed_percentage, 1) }}%</span>
                            </div>
                            <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-3">
                                <div class="bg-blue-500 h-3 rounded-full" style="width: {{ $budget->days_elapsed_percentage }}%"></div>
                            </div>
                            <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">{{ $budget->days_remaining }} days remaining</p>
                        </div>

                        <!-- Budget Utilization -->
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Budget Utilization</span>
                                <span class="text-sm font-bold text-{{ $budget->alert_level }}-600 dark:text-{{ $budget->alert_level }}-400">
                                    {{ number_format($budget->expense_utilization, 1) }}%
                                </span>
                            </div>
                            <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-3">
                                <div class="bg-{{ $budget->alert_level }}-500 h-3 rounded-full" style="width: {{ min($budget->expense_utilization, 100) }}%"></div>
                            </div>
                            <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">
                                {{ $budget->currency }} {{ number_format($budget->actual_expenses, 0) }} / {{ number_format($budget->expense_budget, 0) }}
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $budgets->links() }}
        </div>
    @else
        <!-- Empty State -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-12 text-center">
            <svg class="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-2">All Clear!</h3>
            <p class="text-zinc-600 dark:text-zinc-400 mb-6">
                No budget alerts at this time. All active budgets are on track.
            </p>
            <a href="{{ route('budgets.index') }}" 
               class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors inline-flex items-center gap-2">
                View All Budgets
            </a>
        </div>
    @endif
</div>
