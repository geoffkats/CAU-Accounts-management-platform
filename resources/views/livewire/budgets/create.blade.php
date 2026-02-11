<?php

use App\Models\Program;
use App\Models\ProgramBudget;
use App\Models\Currency;
use Livewire\Volt\Component;

new class extends Component {
    public ?int $id = null;
    public int $program_id;
    public string $period_type = 'quarterly';
    public string $start_date = '';
    public string $end_date = '';
    public float $income_budget = 0;
    public float $expense_budget = 0;
    public string $currency = 'UGX';
    public string $notes = '';
    public string $status = 'draft';

    public function mount(?int $id = null): void
    {
        $this->id = $id;
        
        if ($id) {
            $budget = ProgramBudget::findOrFail($id);
            $this->program_id = $budget->program_id;
            $this->period_type = $budget->period_type;
            $this->start_date = $budget->start_date->format('Y-m-d');
            $this->end_date = $budget->end_date->format('Y-m-d');
            $this->income_budget = $budget->income_budget;
            $this->expense_budget = $budget->expense_budget;
            $this->currency = $budget->currency;
            $this->notes = $budget->notes ?? '';
            $this->status = $budget->status;
        } else {
            $baseCurrency = Currency::getBaseCurrency();
            $this->currency = $baseCurrency->code;
            $this->start_date = now()->startOfQuarter()->format('Y-m-d');
            $this->end_date = now()->endOfQuarter()->format('Y-m-d');
        }
    }

    public function updatedPeriodType(): void
    {
        if ($this->period_type === 'quarterly') {
            $this->start_date = now()->startOfQuarter()->format('Y-m-d');
            $this->end_date = now()->endOfQuarter()->format('Y-m-d');
        } else {
            $this->start_date = now()->startOfYear()->format('Y-m-d');
            $this->end_date = now()->endOfYear()->format('Y-m-d');
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'program_id' => 'required|exists:programs,id',
            'period_type' => 'required|in:quarterly,annual',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'income_budget' => 'required|numeric|min:0',
            'expense_budget' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'notes' => 'nullable|string|max:1000',
            'status' => 'required|in:draft,approved',
        ]);

        if ($this->id) {
            $budget = ProgramBudget::findOrFail($this->id);
            $budget->update($validated);
            $message = 'Budget updated successfully.';
        } else {
            ProgramBudget::create($validated);
            $message = 'Budget created successfully.';
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message
        ]);

        $this->redirect(route('budgets.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'programs' => Program::orderBy('name')->get(),
            'currencies' => Currency::where('is_active', true)->get(),
            'baseCurrency' => Currency::getBaseCurrency(),
        ];
    }
}; ?>

<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">
            {{ $id ? 'Edit Budget' : 'Create Budget' }}
        </h1>
        <p class="text-zinc-600 dark:text-zinc-400 mt-1">Set income and expense budgets for program tracking</p>
    </div>

    <form wire:submit="save">
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 space-y-6">
            
            <!-- Program Selection -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    Program <span class="text-red-600">*</span>
                </label>
                <select wire:model="program_id" 
                        class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                        required>
                    <option value="">Select a program</option>
                    @foreach($programs as $program)
                        <option value="{{ $program->id }}">{{ $program->name }}</option>
                    @endforeach
                </select>
                @error('program_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Period Type -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    Budget Period <span class="text-red-600">*</span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition-colors {{ $period_type === 'quarterly' ? 'border-purple-600 bg-purple-50 dark:bg-purple-900/20' : 'border-zinc-300 dark:border-zinc-600' }}">
                        <input type="radio" wire:model.live="period_type" value="quarterly" class="mr-3">
                        <div>
                            <div class="font-semibold text-zinc-900 dark:text-white">Quarterly</div>
                            <div class="text-sm text-zinc-600 dark:text-zinc-400">3-month budget period</div>
                        </div>
                    </label>
                    <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition-colors {{ $period_type === 'annual' ? 'border-purple-600 bg-purple-50 dark:bg-purple-900/20' : 'border-zinc-300 dark:border-zinc-600' }}">
                        <input type="radio" wire:model.live="period_type" value="annual" class="mr-3">
                        <div>
                            <div class="font-semibold text-zinc-900 dark:text-white">Annual</div>
                            <div class="text-sm text-zinc-600 dark:text-zinc-400">12-month budget period</div>
                        </div>
                    </label>
                </div>
                @error('period_type') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Date Range -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        Start Date <span class="text-red-600">*</span>
                    </label>
                    <input type="date" 
                           wire:model="start_date"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                           required>
                    @error('start_date') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        End Date <span class="text-red-600">*</span>
                    </label>
                    <input type="date" 
                           wire:model="end_date"
                           class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                           required>
                    @error('end_date') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Currency -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    Currency <span class="text-red-600">*</span>
                </label>
                <select wire:model="currency" 
                        class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                        required>
                    @foreach($currencies as $curr)
                        <option value="{{ $curr->code }}">{{ $curr->code }} - {{ $curr->name }}</option>
                    @endforeach
                </select>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                    Base currency is {{ $baseCurrency->code }}. Amounts will be converted for reporting.
                </p>
                @error('currency') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Budget Amounts -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        Income Budget <span class="text-red-600">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500">{{ $currency }}</span>
                        <input type="number" 
                               wire:model="income_budget"
                               step="0.01"
                               min="0"
                               class="w-full pl-16 pr-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                               placeholder="0.00"
                               required>
                    </div>
                    @error('income_budget') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        Expense Budget <span class="text-red-600">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500">{{ $currency }}</span>
                        <input type="number" 
                               wire:model="expense_budget"
                               step="0.01"
                               min="0"
                               class="w-full pl-16 pr-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                               placeholder="0.00"
                               required>
                    </div>
                    @error('expense_budget') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Projected Profit -->
            @if($income_budget > 0 || $expense_budget > 0)
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-blue-900 dark:text-blue-300">Projected Profit/Loss:</span>
                        <span class="text-lg font-bold {{ ($income_budget - $expense_budget) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $currency }} {{ number_format($income_budget - $expense_budget, 2) }}
                        </span>
                    </div>
                    @if($expense_budget > 0)
                        <div class="mt-2 text-sm text-blue-700 dark:text-blue-400">
                            Margin: {{ number_format((($income_budget - $expense_budget) / max($income_budget, 1)) * 100, 2) }}%
                        </div>
                    @endif
                </div>
            @endif

            <!-- Notes -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    Notes
                </label>
                <textarea wire:model="notes"
                          rows="3"
                          class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                          placeholder="Additional notes about this budget..."></textarea>
                @error('notes') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    Status <span class="text-red-600">*</span>
                </label>
                <select wire:model="status" 
                        class="w-full px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                        required>
                    <option value="draft">Draft</option>
                    <option value="approved">Approved (Ready to activate)</option>
                </select>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                    Budgets become "Active" automatically when their start date arrives and status is "Approved"
                </p>
                @error('status') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <a href="{{ route('budgets.index') }}" 
                   class="px-4 py-2 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors font-semibold">
                    {{ $id ? 'Update Budget' : 'Create Budget' }}
                </button>
            </div>
        </div>
    </form>
</div>
