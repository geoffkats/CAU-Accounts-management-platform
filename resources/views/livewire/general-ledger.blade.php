<?php

use App\Models\Account;
use App\Models\Currency;
use Livewire\Volt\Component;

new class extends Component {
    public string $startDate = '';
    public string $endDate = '';
    public string $accountType = 'all';

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function with(): array
    {
        $query = Account::with('journalEntryLines.journalEntry')
            ->where('is_active', true)
            ->orderBy('code');

        if ($this->accountType !== 'all') {
            $query->where('type', $this->accountType);
        }

        $accounts = $query->get()->map(function ($account) {
            $balance = $account->calculateBalance($this->startDate, $this->endDate);
            $transactions = $account->journalEntryLines()
                ->whereHas('journalEntry', function ($q) {
                    $q->where('status', 'posted')
                      ->whereBetween('date', [$this->startDate, $this->endDate]);
                })
                ->count();

            return (object) [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'balance' => $balance,
                'transactions_count' => $transactions,
                'is_debit_balance' => $account->hasNormalDebitBalance(),
            ];
        });

        // Calculate totals by type
        $totals = [
            'assets' => $accounts->where('type', 'asset')->sum('balance'),
            'liabilities' => $accounts->where('type', 'liability')->sum('balance'),
            'equity' => $accounts->where('type', 'equity')->sum('balance'),
            'income' => $accounts->where('type', 'income')->sum('balance'),
            'expenses' => $accounts->where('type', 'expense')->sum('balance'),
        ];

        return [
            'accounts' => $accounts,
            'totals' => $totals,
            'baseCurrency' => Currency::getBaseCurrency(),
            'accountTypes' => Account::getTypes(),
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 via-blue-600 to-indigo-600 bg-clip-text text-transparent">
            General Ledger
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Complete view of all account balances and transactions</p>
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
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="text-sm font-medium opacity-90">Assets</div>
            <div class="text-2xl font-bold mt-2">{{ $baseCurrency->symbol }} {{ number_format($totals['assets'], 0) }}</div>
        </div>
        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-lg p-6 text-white">
            <div class="text-sm font-medium opacity-90">Liabilities</div>
            <div class="text-2xl font-bold mt-2">{{ $baseCurrency->symbol }} {{ number_format($totals['liabilities'], 0) }}</div>
        </div>
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="text-sm font-medium opacity-90">Equity</div>
            <div class="text-2xl font-bold mt-2">{{ $baseCurrency->symbol }} {{ number_format($totals['equity'], 0) }}</div>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div class="text-sm font-medium opacity-90">Income</div>
            <div class="text-2xl font-bold mt-2">{{ $baseCurrency->symbol }} {{ number_format($totals['income'], 0) }}</div>
        </div>
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
            <div class="text-sm font-medium opacity-90">Expenses</div>
            <div class="text-2xl font-bold mt-2">{{ $baseCurrency->symbol }} {{ number_format($totals['expenses'], 0) }}</div>
        </div>
    </div>

    <!-- Accounts Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-purple-500 to-indigo-600 text-white">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase">Code</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase">Account Name</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase">Type</th>
                        <th class="px-6 py-4 text-center text-xs font-bold uppercase">Transactions</th>
                        <th class="px-6 py-4 text-right text-xs font-bold uppercase">Balance</th>
                        <th class="px-6 py-4 text-center text-xs font-bold uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($accounts as $account)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-mono text-sm font-bold text-gray-900 dark:text-white">{{ $account->code }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $account->name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                @if($account->type === 'asset') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                @elseif($account->type === 'liability') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                                @elseif($account->type === 'equity') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                                @elseif($account->type === 'income') bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400
                                @else bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400
                                @endif">
                                {{ ucfirst($account->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $account->transactions_count }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="text-sm font-bold {{ $account->balance < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                {{ $baseCurrency->symbol }} {{ number_format(abs($account->balance), 2) }}
                                @if($account->balance != 0)
                                    <span class="text-xs ml-1">{{ $account->is_debit_balance ? 'Dr' : 'Cr' }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <a href="/account-statement/{{ $account->id }}" 
                               class="text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300 text-sm font-medium">
                                View Details →
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            No accounts found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Accounting Equation Verification -->
    <div class="bg-gradient-to-r from-purple-500 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
        <h3 class="text-xl font-bold mb-4">📊 Accounting Equation Check</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white/10 rounded-lg p-4">
                <div class="text-sm opacity-90 mb-2">Assets</div>
                <div class="text-2xl font-bold">{{ number_format($totals['assets'], 0) }}</div>
            </div>
            <div class="flex items-center justify-center">
                <div class="text-4xl font-bold">=</div>
            </div>
            <div class="bg-white/10 rounded-lg p-4">
                <div class="text-sm opacity-90 mb-2">Liabilities + Equity</div>
                <div class="text-2xl font-bold">{{ number_format($totals['liabilities'] + $totals['equity'], 0) }}</div>
            </div>
        </div>
        @php
            $difference = abs($totals['assets'] - ($totals['liabilities'] + $totals['equity']));
            $isBalanced = $difference < 1;
        @endphp
        <div class="mt-4 text-center">
            @if($isBalanced)
                <span class="inline-flex items-center px-4 py-2 bg-green-500 rounded-lg text-white font-bold">
                    ✓ Books are Balanced
                </span>
            @else
                <span class="inline-flex items-center px-4 py-2 bg-red-500 rounded-lg text-white font-bold">
                    ⚠ Imbalance: {{ $baseCurrency->symbol }} {{ number_format($difference, 2) }}
                </span>
            @endif
        </div>
    </div>
</div>
