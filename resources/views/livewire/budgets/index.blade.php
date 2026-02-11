<?php

use App\Models\Program;
use App\Models\ProgramBudget;
use App\Models\Currency;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public ?int $programFilter = null;

    public function with(): array
    {
        $query = ProgramBudget::with(['program', 'approvedBy'])
            ->when($this->search, function ($q) {
                $q->whereHas('program', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter !== 'all', function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->when($this->programFilter, function ($q) {
                $q->where('program_id', $this->programFilter);
            })
            ->latest();

        return [
            'budgets' => $query->paginate(15),
            'programs' => Program::orderBy('name')->get(),
            'baseCurrency' => Currency::getBaseCurrency(),
            'alertCount' => ProgramBudget::where('status', 'active')
                ->get()
                ->filter(fn($b) => $b->needsAlert())
                ->count(),
        ];
    }

    public function deleteBudget(int $id): void
    {
        $budget = ProgramBudget::findOrFail($id);
        
        if ($budget->status !== 'draft') {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Only draft budgets can be deleted.'
            ]);
            return;
        }

        $budget->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Budget deleted successfully.'
        ]);
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">
                Budget Management
            </h1>
            <p class="text-zinc-600 dark:text-zinc-400 mt-1">Track and manage program budgets vs actuals</p>
        </div>
        
        <div class="flex items-center gap-3">
            @if($alertCount > 0)
                <a href="{{ route('budgets.alerts') }}" 
                   class="px-4 py-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg border border-red-300 dark:border-red-800 hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors inline-flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    {{ $alertCount }} Alert{{ $alertCount > 1 ? 's' : '' }}
                </a>
            @endif
            <a href="{{ route('budgets.create') }}" 
               class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors font-semibold inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Create Budget
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-zinc-800 p-5 rounded-lg border border-zinc-200 dark:border-zinc-700">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Search</label>
                <input type="text" 
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search programs..."
                       class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Program</label>
                <select wire:model.live="programFilter" class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                    <option value="">All Programs</option>
                    @foreach($programs as $program)
                        <option value="{{ $program->id }}">{{ $program->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Status</label>
                <select wire:model.live="statusFilter" class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                    <option value="all">All Statuses</option>
                    <option value="draft">Draft</option>
                    <option value="approved">Approved</option>
                    <option value="active">Active</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Budget Cards -->
    <div class="grid grid-cols-1 gap-4">
        @forelse($budgets as $budget)
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden hover:shadow-lg transition-shadow">
                <div class="p-5">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">
                                    {{ $budget->program->name }}
                                </h3>
                                <span class="px-2.5 py-1 rounded text-xs font-semibold bg-{{ $budget->status_color }}-100 dark:bg-{{ $budget->status_color }}-900/30 text-{{ $budget->status_color }}-700 dark:text-{{ $budget->status_color }}-400">
                                    {{ ucfirst($budget->status) }}
                                </span>
                                @if($budget->status === 'active')
                                    <span class="px-2.5 py-1 rounded text-xs font-semibold bg-{{ $budget->alert_level }}-100 dark:bg-{{ $budget->alert_level }}-900/30 text-{{ $budget->alert_level }}-700 dark:text-{{ $budget->alert_level }}-400">
                                        {{ strtoupper($budget->alert_level) }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ ucfirst($budget->period_type) }} • {{ $budget->start_date->format('M d, Y') }} - {{ $budget->end_date->format('M d, Y') }}
                                @if($budget->status === 'active')
                                    • <span class="font-medium">{{ $budget->days_remaining }} days remaining</span>
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('budgets.show', $budget) }}" 
                               class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>
                            @if($budget->status === 'draft')
                                <a href="{{ route('budgets.edit', $budget) }}" 
                                   class="p-2 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                <button wire:click="deleteBudget({{ $budget->id }})"
                                        wire:confirm="Delete this budget?"
                                        class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Budget Stats -->
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <!-- Income -->
                        <div class="space-y-2">
                            <div class="text-xs font-medium text-zinc-600 dark:text-zinc-400 uppercase">Income Budget</div>
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                {{ $baseCurrency->symbol }} {{ number_format($budget->income_budget, 0) }}
                            </div>
                            @if($budget->status === 'active')
                                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                    Actual: {{ $baseCurrency->symbol }} {{ number_format($budget->actual_income, 0) }}
                                    <span class="font-medium {{ $budget->income_variance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        ({{ $budget->income_variance >= 0 ? '+' : '' }}{{ number_format($budget->income_variance, 0) }})
                                    </span>
                                </div>
                                <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full transition-all" 
                                         style="width: {{ min($budget->income_utilization, 100) }}%"></div>
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ number_format($budget->income_utilization, 1) }}% achieved
                                </div>
                            @endif
                        </div>

                        <!-- Expenses -->
                        <div class="space-y-2">
                            <div class="text-xs font-medium text-zinc-600 dark:text-zinc-400 uppercase">Expense Budget</div>
                            <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                                {{ $baseCurrency->symbol }} {{ number_format($budget->expense_budget, 0) }}
                            </div>
                            @if($budget->status === 'active')
                                <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                    Spent: {{ $baseCurrency->symbol }} {{ number_format($budget->actual_expenses, 0) }}
                                    <span class="font-medium {{ $budget->expense_variance <= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        ({{ $budget->expense_variance >= 0 ? '+' : '' }}{{ number_format($budget->expense_variance, 0) }})
                                    </span>
                                </div>
                                <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2">
                                    <div class="bg-{{ $budget->alert_level === 'red' ? 'red' : ($budget->alert_level === 'yellow' ? 'yellow' : 'blue') }}-500 h-2 rounded-full transition-all" 
                                         style="width: {{ min($budget->expense_utilization, 100) }}%"></div>
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ number_format($budget->expense_utilization, 1) }}% utilized
                                    @if($budget->needsAlert())
                                        <span class="text-red-600 dark:text-red-400 font-semibold">⚠ ALERT</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($budget->notes)
                        <div class="mt-3 p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $budget->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-12 text-center">
                <svg class="w-16 h-16 text-zinc-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                <p class="text-zinc-500 dark:text-zinc-400 mb-4">No budgets found</p>
                <a href="{{ route('budgets.create') }}" class="text-purple-600 dark:text-purple-400 hover:text-purple-700 font-medium">
                    Create your first budget →
                </a>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($budgets->hasPages())
        <div class="mt-6">
            {{ $budgets->links() }}
        </div>
    @endif
</div>
