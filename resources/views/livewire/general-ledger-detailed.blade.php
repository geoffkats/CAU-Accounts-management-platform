<?php

use App\Models\Account;
use App\Models\JournalEntryLine;
use App\Models\Currency;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public ?int $accountId = null;
    public string $startDate = '';
    public string $endDate = '';
    public string $accountType = 'all';

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function selectAccount(?int $id): void
    {
        $this->accountId = $id;
        $this->resetPage();
    }

    public function with(): array
    {
        $accountsQuery = Account::where('is_active', true)
            ->orderBy('code');

        if ($this->accountType !== 'all') {
            $accountsQuery->where('type', $this->accountType);
        }

        $accounts = $accountsQuery->get();

        // If an account is selected, get its detailed transactions
        $transactions = collect();
        $runningBalance = 0;
        $selectedAccount = null;

        if ($this->accountId) {
            $selectedAccount = Account::findOrFail($this->accountId);
            
            // Get all journal entry lines for this account
            $lines = JournalEntryLine::with('journalEntry')
                ->where('account_id', $this->accountId)
                ->whereHas('journalEntry', function ($q) {
                    $q->where('status', 'posted')
                      ->whereBetween('date', [$this->startDate, $this->endDate]);
                })
                ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
                ->orderBy('journal_entries.date')
                ->orderBy('journal_entries.id')
                ->select('journal_entry_lines.*')
                ->get();

            // Calculate running balance
            foreach ($lines as $line) {
                $debit = (float) $line->debit;
                $credit = (float) $line->credit;

                // Update running balance based on account type
                if (in_array($selectedAccount->type, ['asset', 'expense'])) {
                    $runningBalance += $debit - $credit;
                } else {
                    $runningBalance += $credit - $debit;
                }

                $line->running_balance = $runningBalance;
            }

            $transactions = $lines;
        }

        return [
            'accounts' => $accounts,
            'transactions' => $transactions,
            'selectedAccount' => $selectedAccount,
            'baseCurrency' => Currency::getBaseCurrency(),
            'accountTypes' => Account::getTypes(),
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 via-blue-600 to-indigo-600 bg-clip-text text-transparent">
            General Ledger (Detailed)
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Transaction-level view with running balances for each account</p>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start Date</label>
                <input type="date" wire:model.live="startDate" 
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">End Date</label>
                <input type="date" wire:model.live="endDate" 
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Account Type</label>
                <select wire:model.live="accountType" 
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                    <option value="all">All Types</option>
                    @foreach($accountTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Actions</label>
                <button wire:click="selectAccount(null)" 
                        class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                    Clear Selection
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Accounts List -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-500 to-indigo-600 p-4">
                    <h3 class="text-lg font-bold text-white">Chart of Accounts</h3>
                </div>
                <div class="max-h-[600px] overflow-y-auto">
                    @foreach($accounts as $account)
                    <button 
                        wire:click="selectAccount({{ $account->id }})"
                        class="w-full text-left px-4 py-3 border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors
                               {{ $accountId === $account->id ? 'bg-purple-50 dark:bg-purple-900/20 border-l-4 border-l-purple-600' : '' }}">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $account->code }}</div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $account->name }}</div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium mt-1
                                    @if($account->type === 'asset') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                    @elseif($account->type === 'liability') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                                    @elseif($account->type === 'equity') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                                    @elseif($account->type === 'income') bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400
                                    @else bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400
                                    @endif">
                                    {{ ucfirst($account->type) }}
                                </span>
                            </div>
                        </div>
                    </button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Transactions Detail -->
        <div class="lg:col-span-2">
            @if($selectedAccount)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-6 text-white">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-sm opacity-90 font-mono">{{ $selectedAccount->code }}</div>
                            <h2 class="text-2xl font-bold mt-1">{{ $selectedAccount->name }}</h2>
                            <div class="text-sm opacity-90 mt-2">{{ ucfirst($selectedAccount->type) }} Account</div>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Reference</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Description</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Debit ({{ $baseCurrency->code }})</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Credit ({{ $baseCurrency->code }})</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($transactions as $line)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    {{ $line->journalEntry->date->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <a href="{{ route('journal-entries.show', $line->journalEntry->id) }}" 
                                       class="text-sm font-mono text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300">
                                        {{ $line->journalEntry->reference }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                    {{ $line->description ?: $line->journalEntry->description }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-bold text-green-600 dark:text-green-400">
                                    @if($line->debit > 0)
                                        {{ number_format($line->debit, 2) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-bold text-red-600 dark:text-red-400">
                                    @if($line->credit > 0)
                                        {{ number_format($line->credit, 2) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-bold {{ $line->running_balance < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                    {{ number_format(abs($line->running_balance), 2) }}
                                    <span class="text-xs ml-1">
                                        @if($line->running_balance != 0)
                                            {{ $selectedAccount->hasNormalDebitBalance() ? ($line->running_balance > 0 ? 'Dr' : 'Cr') : ($line->running_balance > 0 ? 'Cr' : 'Dr') }}
                                        @endif
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                    No transactions found for this account in the selected period.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($transactions->isNotEmpty())
                        <tfoot class="bg-gray-50 dark:bg-gray-900/50 font-bold">
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 uppercase">Period Total</td>
                                <td class="px-4 py-3 text-right text-sm text-green-600 dark:text-green-400">
                                    {{ number_format($transactions->sum('debit'), 2) }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm text-red-600 dark:text-red-400">
                                    {{ number_format($transactions->sum('credit'), 2) }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm text-gray-900 dark:text-white">
                                    {{ number_format(abs($transactions->last()->running_balance ?? 0), 2) }}
                                    @if($transactions->last())
                                        <span class="text-xs ml-1">
                                            {{ $selectedAccount->hasNormalDebitBalance() ? ($transactions->last()->running_balance > 0 ? 'Dr' : 'Cr') : ($transactions->last()->running_balance > 0 ? 'Cr' : 'Dr') }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
            @else
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-12 text-center">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Select an Account</h3>
                <p class="text-gray-600 dark:text-gray-400">Choose an account from the list to view its detailed general ledger with all transactions and running balance.</p>
            </div>
            @endif
        </div>
    </div>
</div>
