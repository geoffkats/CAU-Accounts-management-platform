<?php

use App\Models\JournalEntry;
use App\Models\Account;
use App\Models\Currency;
use Livewire\Volt\Component;

new class extends Component {
    public $date;
    public $type = 'adjustment';
    public $description = '';
    public $lines = [];
    
    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
        
        // Initialize with 2 empty lines
        $this->addLine();
        $this->addLine();
    }

    public function addLine(): void
    {
        $this->lines[] = [
            'account_id' => '',
            'debit' => '',
            'credit' => '',
            'description' => '',
        ];
    }

    public function removeLine($index): void
    {
        if (count($this->lines) > 2) {
            unset($this->lines[$index]);
            $this->lines = array_values($this->lines); // Re-index array
        }
    }

    public function getTotalDebits(): float
    {
        return collect($this->lines)->sum(fn($line) => (float)($line['debit'] ?? 0));
    }

    public function getTotalCredits(): float
    {
        return collect($this->lines)->sum(fn($line) => (float)($line['credit'] ?? 0));
    }

    public function getBalance(): float
    {
        return abs($this->getTotalDebits() - $this->getTotalCredits());
    }

    public function isBalanced(): bool
    {
        return $this->getBalance() < 0.01;
    }

    public function save(): void
    {
        // Validation
        $this->validate([
            'date' => 'required|date',
            'type' => 'required|string',
            'description' => 'required|string|min:5',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.description' => 'nullable|string',
        ]);

        // Validate at least one debit and one credit
        $hasDebit = collect($this->lines)->some(fn($line) => !empty($line['debit']) && $line['debit'] > 0);
        $hasCredit = collect($this->lines)->some(fn($line) => !empty($line['credit']) && $line['credit'] > 0);

        if (!$hasDebit || !$hasCredit) {
            session()->flash('error', 'Journal entry must have at least one debit and one credit.');
            return;
        }

        // Validate balance
        if (!$this->isBalanced()) {
            session()->flash('error', 'Journal entry is not balanced. Debits must equal Credits.');
            return;
        }

        // Validate each line has either debit or credit (not both, not neither)
        foreach ($this->lines as $index => $line) {
            $debit = (float)($line['debit'] ?? 0);
            $credit = (float)($line['credit'] ?? 0);
            
            if ($debit > 0 && $credit > 0) {
                session()->flash('error', "Line " . ($index + 1) . " cannot have both debit and credit.");
                return;
            }
            
            if ($debit <= 0 && $credit <= 0) {
                session()->flash('error', "Line " . ($index + 1) . " must have either debit or credit amount.");
                return;
            }
        }

        try {
            // Create journal entry
            $entry = JournalEntry::createEntry(
                [
                    'date' => $this->date,
                    'type' => $this->type,
                    'description' => $this->description,
                    'created_by' => auth()->id(),
                    'status' => 'posted',
                    'posted_at' => now(),
                ],
                collect($this->lines)->map(function ($line) {
                    return [
                        'account_id' => $line['account_id'],
                        'debit' => (float)($line['debit'] ?? 0),
                        'credit' => (float)($line['credit'] ?? 0),
                        'description' => $line['description'] ?? '',
                    ];
                })->toArray()
            );

            session()->flash('success', "Journal entry {$entry->reference} created successfully!");
            $this->redirect(route('journal-entries.show', $entry->id), navigate: true);
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create journal entry: ' . $e->getMessage());
        }
    }

    public function with(): array
    {
        return [
            'accounts' => Account::where('is_active', true)->orderBy('code')->get(),
            'baseCurrency' => Currency::getBaseCurrency(),
            'totalDebits' => $this->getTotalDebits(),
            'totalCredits' => $this->getTotalCredits(),
            'balance' => $this->getBalance(),
            'isBalanced' => $this->isBalanced(),
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
                New Journal Entry
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Create manual journal entry for adjustments, corrections, or opening balances</p>
        </div>
        <a href="{{ route('journal-entries.index') }}" 
           class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
            ← Back to List
        </a>
    </div>

    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <!-- Entry Details -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Entry Details</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date *</label>
                    <input type="date" wire:model="date" required
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                    @error('date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Type *</label>
                    <select wire:model="type" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                        <option value="adjustment">Adjustment</option>
                        <option value="opening">Opening Balance</option>
                        <option value="closing">Closing Entry</option>
                        <option value="accrual">Accrual</option>
                        <option value="reclassification">Reclassification</option>
                    </select>
                    @error('type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Reference</label>
                    <input type="text" value="Auto-generated" disabled
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-gray-900 text-gray-500">
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description *</label>
                <textarea wire:model="description" rows="2" required
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"
                          placeholder="Explain the purpose of this journal entry..."></textarea>
                @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Journal Lines -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-purple-500 to-indigo-600 p-6">
                <h2 class="text-xl font-bold text-white">Journal Lines</h2>
                <p class="text-white/80 text-sm mt-1">Each line debits or credits one account. Total debits must equal total credits.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Account *</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Description</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Debit</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Credit</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($lines as $index => $line)
                        <tr>
                            <td class="px-4 py-3">
                                <select wire:model="lines.{{ $index }}.account_id" required
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white text-sm">
                                    <option value="">Select Account</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                    @endforeach
                                </select>
                                @error("lines.{$index}.account_id") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </td>
                            <td class="px-4 py-3">
                                <input type="text" wire:model="lines.{{ $index }}.description" 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white text-sm"
                                       placeholder="Line description...">
                            </td>
                            <td class="px-4 py-3">
                                <input type="number" step="0.01" min="0" wire:model="lines.{{ $index }}.debit" 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white text-sm text-right"
                                       placeholder="0.00">
                            </td>
                            <td class="px-4 py-3">
                                <input type="number" step="0.01" min="0" wire:model="lines.{{ $index }}.credit" 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white text-sm text-right"
                                       placeholder="0.00">
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if(count($lines) > 2)
                                <button type="button" wire:click="removeLine({{ $index }})" 
                                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <td colspan="2" class="px-4 py-3">
                                <button type="button" wire:click="addLine" 
                                        class="text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300 text-sm font-medium">
                                    + Add Line
                                </button>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="text-sm font-bold text-green-600 dark:text-green-400">
                                    {{ $baseCurrency->symbol }} {{ number_format($totalDebits, 2) }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="text-sm font-bold text-red-600 dark:text-red-400">
                                    {{ $baseCurrency->symbol }} {{ number_format($totalCredits, 2) }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($isBalanced)
                                    <span class="text-green-600 dark:text-green-400 font-bold">✓ Balanced</span>
                                @else
                                    <span class="text-red-600 dark:text-red-400 font-bold">⚠ {{ $baseCurrency->symbol }} {{ number_format($balance, 2) }} off</span>
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <span class="font-medium">Double-Entry Rule:</span> Total Debits must equal Total Credits
            </div>
            <div class="flex gap-3">
                <a href="{{ route('journal-entries.index') }}" 
                   class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Cancel
                </a>
                <button type="submit" 
                        @if(!$isBalanced) disabled @endif
                        class="px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed">
                    Post Journal Entry
                </button>
            </div>
        </div>
    </form>
</div>
