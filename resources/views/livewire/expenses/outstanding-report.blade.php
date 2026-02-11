<?php

use App\Models\Expense;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

new class extends Component {
    use WithPagination;

    public string $asOfDate = '';

    public function mount(): void
    {
        $this->asOfDate = now()->toDateString();
    }

    public function with(): array
    {
        $asOf = Carbon::parse($this->asOfDate ?: now()->toDateString());
        $expenses = Expense::with(['program', 'vendor', 'staff', 'account'])
            ->get()
            ->filter(fn($expense) => $expense->outstanding_balance > 0)
            ->sortByDesc('outstanding_balance');

        $agingBuckets = [
            'current' => ['label' => '0-30 days', 'amount' => 0, 'count' => 0],
            '31_60' => ['label' => '31-60 days', 'amount' => 0, 'count' => 0],
            '61_90' => ['label' => '61-90 days', 'amount' => 0, 'count' => 0],
            '90_plus' => ['label' => '90+ days', 'amount' => 0, 'count' => 0],
        ];

        foreach ($expenses as $expense) {
            $age = $expense->expense_date
                ? $expense->expense_date->diffInDays($asOf)
                : 0;
            if ($age <= 30) {
                $bucket = 'current';
            } elseif ($age <= 60) {
                $bucket = '31_60';
            } elseif ($age <= 90) {
                $bucket = '61_90';
            } else {
                $bucket = '90_plus';
            }

            $agingBuckets[$bucket]['amount'] += $expense->outstanding_balance;
            $agingBuckets[$bucket]['count'] += 1;
        }

        $totalOutstanding = $expenses->sum('outstanding_balance');
        $totalExpenses = $expenses->sum(fn($e) => $e->amount + ($e->charges ?? 0));
        $totalPaid = $expenses->sum('total_paid');

        return [
            'expenses' => $expenses,
            'totalOutstanding' => $totalOutstanding,
            'totalExpenses' => $totalExpenses,
            'totalPaid' => $totalPaid,
            'count' => $expenses->count(),
            'agingBuckets' => $agingBuckets,
            'asOfDate' => $asOf->format('Y-m-d'),
        ];
    }
}; ?>

<div class="space-y-6">
    <x-slot name="title">Outstanding Expenses Report</x-slot>

    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-orange-600 via-red-600 to-pink-600 bg-clip-text text-transparent">
                Outstanding Expenses Report
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Detailed breakdown of unpaid and partially paid expenses</p>
        </div>
        <div class="flex items-center gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">As of</label>
                <input type="date" wire:model.live="asOfDate" class="px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg dark:bg-gray-800 dark:text-white">
            </div>
            <a href="{{ route('expenses.index') }}" 
               class="px-6 py-3 bg-gray-500 text-white rounded-xl hover:bg-gray-600 transition-all duration-200 font-semibold"
               wire:navigate>
                ← Back to Expenses
            </a>
        </div>
    </div>

    <!-- Aging Summary -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Aging Analysis (as of {{ $asOfDate }})</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @foreach($agingBuckets as $bucket)
                <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30">
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">{{ $bucket['label'] }}</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">UGX {{ number_format($bucket['amount'], 0) }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">{{ number_format($bucket['count']) }} expense(s)</div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 p-6 rounded-xl border-2 border-orange-200 dark:border-orange-800 shadow-lg">
            <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Total Outstanding</div>
            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">UGX {{ number_format($totalOutstanding, 0) }}</div>
        </div>
        <div class="bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 p-6 rounded-xl border-2 border-blue-200 dark:border-blue-800 shadow-lg">
            <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Total Amount</div>
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">UGX {{ number_format($totalExpenses, 0) }}</div>
        </div>
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 p-6 rounded-xl border-2 border-green-200 dark:border-green-800 shadow-lg">
            <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Total Paid</div>
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">UGX {{ number_format($totalPaid, 0) }}</div>
        </div>
        <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 p-6 rounded-xl border-2 border-purple-200 dark:border-purple-800 shadow-lg">
            <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Number of Expenses</div>
            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $count }}</div>
        </div>
    </div>

    <!-- Outstanding Expenses Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Expenses with Outstanding Balances</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">ID</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Date</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Description</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Paid To</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Amount</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Charges</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Total</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Paid</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Outstanding</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Status</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($expenses as $expense)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                            #{{ $expense->id }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            {{ $expense->expense_date->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 max-w-[200px]">
                            <div class="text-sm font-semibold text-gray-900 dark:text-white truncate" title="{{ $expense->description }}">
                                {{ $expense->description }}
                            </div>
                            @if($expense->category)
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">🏷️ {{ $expense->category }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 max-w-[150px]">
                            @if($expense->vendor)
                                <div class="truncate" title="{{ $expense->vendor->name }}">{{ $expense->vendor->name }}</div>
                            @elseif($expense->staff)
                                <div class="truncate" title="{{ $expense->staff->first_name }} {{ $expense->staff->last_name }}">
                                    {{ $expense->staff->first_name }} {{ $expense->staff->last_name }}
                                </div>
                            @else
                                <span class="text-gray-400">N/A</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900 dark:text-white">
                            {{ number_format($expense->amount, 0) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-600 dark:text-gray-400">
                            {{ number_format($expense->charges ?? 0, 0) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-blue-600 dark:text-blue-400">
                            {{ number_format($expense->amount + ($expense->charges ?? 0), 0) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-green-600 dark:text-green-400">
                            {{ number_format($expense->total_paid, 0) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-red-600 dark:text-red-400">
                            {{ number_format($expense->outstanding_balance, 0) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if($expense->payment_status === 'partial')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                    Partial
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400">
                                    Unpaid
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('expenses.show', $expense->id) }}"
                                   class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                   title="View Details"
                                   wire:navigate>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                <a href="{{ route('expenses.payment-voucher', $expense->id) }}"
                                   class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                   title="Create Payment Voucher"
                                   wire:navigate>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="px-6 py-12 text-center">
                            <svg class="w-16 h-16 text-green-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400 font-medium text-lg">🎉 All expenses are fully paid!</p>
                            <p class="text-gray-400 dark:text-gray-500 text-sm mt-2">There are no outstanding balances.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
