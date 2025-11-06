<?php

use App\Models\Account;
use App\Models\Currency;
use Livewire\Volt\Component;

new class extends Component {
    public int $accountId;
    public ?Account $account = null;
    public string $startDate = '';
    public string $endDate = '';

    public function mount(int $id): void
    {
        $this->accountId = $id;
        $this->account = Account::findOrFail($id);
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function with(): array
    {
        if (!$this->account) {
            return [
                'transactions' => collect(),
                'openingBalance' => 0,
                'closingBalance' => 0,
                'totalDebits' => 0,
                'totalCredits' => 0,
                'baseCurrency' => Currency::getBaseCurrency(),
            ];
        }

        // Get opening balance (before start date)
        $openingBalance = $this->account->calculateBalance(null, date('Y-m-d', strtotime($this->startDate . ' -1 day')));

        // Get transactions with running balance
        $transactions = $this->account->getTransactionsWithRunningBalance($this->startDate, $this->endDate);
        
        // Calculate totals for the period
        $totalDebits = $transactions->sum('debit');
        $totalCredits = $transactions->sum('credit');
        
        // Closing balance
        $closingBalance = $this->account->calculateBalance(null, $this->endDate);

        return [
            'transactions' => $transactions,
            'openingBalance' => $openingBalance,
            'closingBalance' => $closingBalance,
            'totalDebits' => $totalDebits,
            'totalCredits' => $totalCredits,
            'baseCurrency' => Currency::getBaseCurrency(),
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Header with Back Button -->
    <div class="flex items-center gap-4">
        <a href="{{ route('general-ledger') }}" 
           class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
                Account Statement
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                <span class="font-mono font-bold">{{ $account->code }}</span> - {{ $account->name }}
            </p>
        </div>
    </div>

    <!-- Account Info Card -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <div class="text-sm opacity-90 mb-1">Account Code</div>
                <div class="text-2xl font-mono font-bold">{{ $account->code }}</div>
            </div>
            <div>
                <div class="text-sm opacity-90 mb-1">Account Type</div>
                <div class="text-2xl font-bold">{{ ucfirst($account->type) }}</div>
            </div>
            <div>
                <div class="text-sm opacity-90 mb-1">Normal Balance</div>
                <div class="text-2xl font-bold">{{ $account->hasNormalDebitBalance() ? 'Debit' : 'Credit' }}</div>
            </div>
            <div>
                <div class="text-sm opacity-90 mb-1">Status</div>
                <div class="text-2xl font-bold">{{ $account->is_active ? '✓ Active' : '✗ Inactive' }}</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
        </div>
    </div>

    <!-- Balance Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Opening Balance</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ $baseCurrency->symbol }} {{ number_format(abs($openingBalance), 2) }}
                <span class="text-sm ml-1">{{ $openingBalance >= 0 ? 'Dr' : 'Cr' }}</span>
            </div>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 rounded-xl shadow-lg border border-green-200 dark:border-green-800 p-6">
            <div class="text-sm font-medium text-green-700 dark:text-green-400 mb-2">Total Debits</div>
            <div class="text-2xl font-bold text-green-900 dark:text-green-300">
                {{ $baseCurrency->symbol }} {{ number_format($totalDebits, 2) }}
            </div>
        </div>
        <div class="bg-red-50 dark:bg-red-900/20 rounded-xl shadow-lg border border-red-200 dark:border-red-800 p-6">
            <div class="text-sm font-medium text-red-700 dark:text-red-400 mb-2">Total Credits</div>
            <div class="text-2xl font-bold text-red-900 dark:text-red-300">
                {{ $baseCurrency->symbol }} {{ number_format($totalCredits, 2) }}
            </div>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
            <div class="text-sm opacity-90 mb-2">Closing Balance</div>
            <div class="text-2xl font-bold">
                {{ $baseCurrency->symbol }} {{ number_format(abs($closingBalance), 2) }}
                <span class="text-sm ml-1">{{ $closingBalance >= 0 ? 'Dr' : 'Cr' }}</span>
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-6">
            <h2 class="text-xl font-bold text-white">Transaction History</h2>
            <p class="text-white/80 text-sm mt-1">{{ $transactions->count() }} transactions for period {{ date('M d, Y', strtotime($startDate)) }} to {{ date('M d, Y', strtotime($endDate)) }}</p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Date</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Reference</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Description</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Debit</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Credit</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @if($openingBalance != 0)
                    <tr class="bg-blue-50 dark:bg-blue-900/20">
                        <td colspan="3" class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                            Opening Balance
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-bold text-gray-900 dark:text-white">-</td>
                        <td class="px-6 py-4 text-right text-sm font-bold text-gray-900 dark:text-white">-</td>
                        <td class="px-6 py-4 text-right text-sm font-bold text-gray-900 dark:text-white">
                            {{ $baseCurrency->symbol }} {{ number_format(abs($openingBalance), 2) }}
                            <span class="text-xs ml-1">{{ $openingBalance >= 0 ? 'Dr' : 'Cr' }}</span>
                        </td>
                    </tr>
                    @endif
                    
                    @forelse($transactions as $transaction)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            {{ $transaction->journalEntry->date->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-mono font-semibold text-gray-900 dark:text-white">
                                {{ $transaction->journalEntry->reference }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900 dark:text-white">{{ $transaction->journalEntry->description }}</div>
                            @if($transaction->description)
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $transaction->description }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if($transaction->debit > 0)
                                <span class="text-sm font-bold text-green-600 dark:text-green-400">
                                    {{ $baseCurrency->symbol }} {{ number_format($transaction->debit, 2) }}
                                </span>
                            @else
                                <span class="text-sm text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if($transaction->credit > 0)
                                <span class="text-sm font-bold text-red-600 dark:text-red-400">
                                    {{ $baseCurrency->symbol }} {{ number_format($transaction->credit, 2) }}
                                </span>
                            @else
                                <span class="text-sm text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ $baseCurrency->symbol }} {{ number_format(abs($transaction->running_balance), 2) }}
                                <span class="text-xs ml-1">{{ $transaction->running_balance >= 0 ? 'Dr' : 'Cr' }}</span>
                            </span>
                        </td>
                    </tr>
                    @empty
                    @if($openingBalance == 0)
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            No transactions found for this period
                        </td>
                    </tr>
                    @endif
                    @endforelse
                    
                    @if($transactions->count() > 0 || $openingBalance != 0)
                    <tr class="bg-purple-50 dark:bg-purple-900/20 font-bold">
                        <td colspan="3" class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                            Closing Balance
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-green-700 dark:text-green-400">
                            {{ $baseCurrency->symbol }} {{ number_format($totalDebits, 2) }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-red-700 dark:text-red-400">
                            {{ $baseCurrency->symbol }} {{ number_format($totalCredits, 2) }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-gray-900 dark:text-white">
                            {{ $baseCurrency->symbol }} {{ number_format(abs($closingBalance), 2) }}
                            <span class="text-xs ml-1">{{ $closingBalance >= 0 ? 'Dr' : 'Cr' }}</span>
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
