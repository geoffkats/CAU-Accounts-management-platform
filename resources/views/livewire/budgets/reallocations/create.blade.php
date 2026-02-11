<?php

use App\Models\ProgramBudget;
use App\Models\BudgetReallocation;
use App\Models\Currency;
use Livewire\Volt\Component;

new class extends Component {
    public ?int $from_budget_id = null;
    public ?int $to_budget_id = null;
    public ?float $amount = null;
    public string $category = 'income';
    public string $reason = '';

    public function save(): void
    {
        $validated = $this->validate([
            'from_budget_id' => 'required|exists:program_budgets,id',
            'to_budget_id' => 'required|exists:program_budgets,id|different:from_budget_id',
            'amount' => 'required|numeric|min:0.01',
            'category' => 'required|in:income,expense',
            'reason' => 'required|string|min:10|max:500',
        ]);

        // Validate sufficient budget available
        $fromBudget = ProgramBudget::findOrFail($this->from_budget_id);
        $availableAmount = $this->category === 'income' 
            ? $fromBudget->income_budget - $fromBudget->actual_income
            : $fromBudget->expense_budget - $fromBudget->actual_expenses;

        if ($this->amount > $availableAmount) {
            $this->addError('amount', 'Insufficient budget available. Maximum: ' . number_format($availableAmount, 2));
            return;
        }

        BudgetReallocation::create([
            'from_budget_id' => $this->from_budget_id,
            'to_budget_id' => $this->to_budget_id,
            'amount' => $this->amount,
            'category' => $this->category,
            'reason' => $this->reason,
            'status' => 'pending',
            'requested_by' => auth()->id(),
        ]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Reallocation request submitted successfully.'
        ]);

        $this->redirect(route('budgets.reallocations.index'));
    }

    public function updatedFromBudgetId(): void
    {
        $this->reset(['amount']);
    }

    public function updatedCategory(): void
    {
        $this->reset(['amount']);
    }

    public function with(): array
    {
        $activeBudgets = ProgramBudget::with('program')
            ->where('status', 'active')
            ->get();

        $fromBudget = $this->from_budget_id 
            ? ProgramBudget::find($this->from_budget_id) 
            : null;

        $availableAmount = null;
        if ($fromBudget) {
            $availableAmount = $this->category === 'income'
                ? $fromBudget->income_budget - $fromBudget->actual_income
                : $fromBudget->expense_budget - $fromBudget->actual_expenses;
        }

        return [
            'activeBudgets' => $activeBudgets,
            'baseCurrency' => Currency::getBaseCurrency(),
            'fromBudget' => $fromBudget,
            'availableAmount' => $availableAmount,
        ];
    }
}; ?>

<div class="max-w-3xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">New Budget Reallocation</h1>
            <p class="text-zinc-600 dark:text-zinc-400 mt-1">Transfer budget between programs</p>
        </div>
        <a href="{{ route('budgets.reallocations.index') }}" 
           class="px-4 py-2 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
            Cancel
        </a>
    </div>

    <!-- Form -->
    <form wire:submit="save" class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 space-y-6">
        
        <!-- Category Selection -->
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">
                Budget Category <span class="text-red-500">*</span>
            </label>
            <div class="grid grid-cols-2 gap-4">
                <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition-colors {{ $category === 'income' ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/20' : 'border-zinc-300 dark:border-zinc-600' }}">
                    <input type="radio" wire:model.live="category" value="income" class="mr-3">
                    <div>
                        <div class="font-semibold text-zinc-900 dark:text-white">Income Budget</div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">Transfer income allocation</div>
                    </div>
                </label>
                <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition-colors {{ $category === 'expense' ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/20' : 'border-zinc-300 dark:border-zinc-600' }}">
                    <input type="radio" wire:model.live="category" value="expense" class="mr-3">
                    <div>
                        <div class="font-semibold text-zinc-900 dark:text-white">Expense Budget</div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400">Transfer expense allocation</div>
                    </div>
                </label>
            </div>
            @error('category') <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span> @enderror
        </div>

        <!-- From Budget -->
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                From Program (Source) <span class="text-red-500">*</span>
            </label>
            <select wire:model.live="from_budget_id"
                    class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                <option value="">Select source budget...</option>
                @foreach($activeBudgets as $budget)
                    <option value="{{ $budget->id }}">
                        {{ $budget->program->name }} - {{ ucfirst($budget->period_type) }} ({{ $budget->start_date->format('M Y') }})
                    </option>
                @endforeach
            </select>
            @error('from_budget_id') <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span> @enderror
            
            @if($fromBudget && $availableAmount !== null)
                <div class="mt-2 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-sm">
                    <div class="font-semibold text-blue-900 dark:text-blue-300 mb-1">Available Budget:</div>
                    <div class="text-blue-700 dark:text-blue-400">
                        {{ $baseCurrency->symbol }} {{ number_format($availableAmount, 2) }}
                    </div>
                </div>
            @endif
        </div>

        <!-- To Budget -->
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                To Program (Destination) <span class="text-red-500">*</span>
            </label>
            <select wire:model="to_budget_id"
                    class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                <option value="">Select destination budget...</option>
                @foreach($activeBudgets as $budget)
                    @if($budget->id != $from_budget_id)
                        <option value="{{ $budget->id }}">
                            {{ $budget->program->name }} - {{ ucfirst($budget->period_type) }} ({{ $budget->start_date->format('M Y') }})
                        </option>
                    @endif
                @endforeach
            </select>
            @error('to_budget_id') <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span> @enderror
        </div>

        <!-- Amount -->
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                Amount <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-zinc-500">{{ $baseCurrency->symbol }}</span>
                <input type="number" 
                       step="0.01" 
                       wire:model="amount"
                       placeholder="0.00"
                       class="w-full pl-12 pr-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
            </div>
            @error('amount') <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span> @enderror
        </div>

        <!-- Reason -->
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                Reason for Reallocation <span class="text-red-500">*</span>
            </label>
            <textarea wire:model="reason"
                      rows="4"
                      placeholder="Provide a detailed explanation for this budget reallocation..."
                      class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500"></textarea>
            @error('reason') <span class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</span> @enderror
            <div class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Minimum 10 characters
            </div>
        </div>

        <!-- Impact Preview -->
        @if($from_budget_id && $to_budget_id && $amount)
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-600 p-4 rounded-lg">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="flex-1">
                        <h4 class="font-semibold text-yellow-900 dark:text-yellow-300 mb-2">Reallocation Impact</h4>
                        <div class="text-sm text-yellow-700 dark:text-yellow-400 space-y-1">
                            <div>• {{ $baseCurrency->symbol }} {{ number_format($amount, 2) }} will be moved from source program</div>
                            <div>• Destination program will receive {{ $baseCurrency->symbol }} {{ number_format($amount, 2) }}</div>
                            <div>• This request requires approval from finance manager</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Submit Button -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
            <a href="{{ route('budgets.reallocations.index') }}" 
               class="px-6 py-2 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                Cancel
            </a>
            <button type="submit" 
                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                Submit Request
            </button>
        </div>
    </form>
</div>
