<?php

use Livewire\Volt\Component;
use App\Models\Account;
use App\Models\JournalEntryLine;
use App\Models\Currency;
use Carbon\Carbon;

new class extends Component {
    public $account_id;
    public $start_date;
    public $end_date;
    public string $periodType = 'month';
    public string $asOfDate = '';
    public $transaction_type = 'all'; // all, receipts, payments
    public $opening_balance = 0;
    public $closing_balance = 0;
    public $total_receipts = 0;
    public $total_payments = 0;
    
    public function mount()
    {
        $this->asOfDate = now()->endOfMonth()->format('Y-m-d');
        $this->applyPeriodRange();
        
        // Default to first cash/bank account
        $this->account_id = Account::where('type', 'asset')
            ->where(function($q) {
                $q->where('code', 'like', '10%')
                  ->orWhere('name', 'like', '%cash%')
                  ->orWhere('name', 'like', '%bank%')
                  ->orWhere('name', 'like', '%momo%')
                  ->orWhere('name', 'like', '%mobile%')
                  ->orWhere('name', 'like', '%money%')
                  ->orWhere('name', 'like', '%airtel%')
                  ->orWhere('name', 'like', '%mtn%');
            })
            ->first()?->id;
    }

    private function applyPeriodRange(): void
    {
        if ($this->periodType === 'custom') {
            return;
        }

        $asOf = Carbon::parse($this->asOfDate ?: now()->toDateString());

        if ($this->periodType === 'quarter') {
            $this->start_date = $asOf->copy()->startOfQuarter()->format('Y-m-d');
            $this->end_date = $asOf->copy()->endOfQuarter()->format('Y-m-d');
        } elseif ($this->periodType === 'year') {
            $this->start_date = $asOf->copy()->startOfYear()->format('Y-m-d');
            $this->end_date = $asOf->copy()->endOfYear()->format('Y-m-d');
        } else {
            $this->start_date = $asOf->copy()->startOfMonth()->format('Y-m-d');
            $this->end_date = $asOf->copy()->endOfMonth()->format('Y-m-d');
        }
    }

    public function updatedPeriodType(): void
    {
        $this->applyPeriodRange();
    }

    public function updatedAsOfDate(): void
    {
        $this->applyPeriodRange();
    }
    
    public function exportExcel()
    {
        return redirect()->route('cashbook.export.excel', [
            'account_id' => $this->account_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'transaction_type' => $this->transaction_type,
        ]);
    }
    
    public function exportPdf()
    {
        return redirect()->route('cashbook.export.pdf', [
            'account_id' => $this->account_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'transaction_type' => $this->transaction_type,
        ]);
    }
    
    public function with()
    {
        $accounts = Account::where('type', 'asset')
            ->where('is_active', true)
            ->where(function($q) {
                $q->where('code', 'like', '10%')
                  ->orWhere('name', 'like', '%cash%')
                                    ->orWhere('name', 'like', '%bank%')
                                    ->orWhere('name', 'like', '%momo%')
                                    ->orWhere('name', 'like', '%mobile%')
                                    ->orWhere('name', 'like', '%money%')
                                    ->orWhere('name', 'like', '%airtel%')
                                    ->orWhere('name', 'like', '%mtn%');
            })
            ->orderBy('code')
            ->get();
            
        $transactions = collect();
        $this->opening_balance = 0;
        $this->closing_balance = 0;
        
        if ($this->account_id) {
            $account = Account::find($this->account_id);
            
            // Calculate opening balance
            $this->opening_balance = $account->calculateBalance('1900-01-01', Carbon::parse($this->start_date)->subDay()->format('Y-m-d'));
            
            // Get transactions with filter
            $query = JournalEntryLine::where('account_id', $this->account_id)
                ->whereHas('journalEntry', function($q) {
                    $q->where('status', 'posted')
                      ->whereBetween('date', [$this->start_date, $this->end_date]);
                });
            
            // Filter by transaction type
            if ($this->transaction_type === 'receipts') {
                $query->where('debit', '>', 0);
            } elseif ($this->transaction_type === 'payments') {
                $query->where('credit', '>', 0);
            }
            
            $transactions = $query->with(['journalEntry'])
                ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
                ->orderBy('journal_entries.date')
                ->orderBy('journal_entries.id')
                ->select('journal_entry_lines.*')
                ->get();
            
            $this->total_receipts = $transactions->sum('debit');
            $this->total_payments = $transactions->sum('credit');
            
            // Calculate running balance
            $runningBalance = $this->opening_balance;
            $transactions = $transactions->map(function($transaction) use (&$runningBalance) {
                $transaction->running_balance = $runningBalance + $transaction->debit - $transaction->credit;
                $runningBalance = $transaction->running_balance;
                return $transaction;
            });
            
            $this->closing_balance = $runningBalance;
        }
        
        return [
            'accounts' => $accounts,
            'transactions' => $transactions,
            'currency' => Currency::getBaseCurrency(),
        ];
    }
}; ?>

<div>
    <x-slot name="title">Cashbook</x-slot>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Cashbook</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Track all cash and bank transactions</p>
        </div>

        <!-- Filters & Actions -->
        <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Account</label>
                    <select wire:model.live="account_id" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-700 rounded-md dark:bg-zinc-900 dark:text-white">
                        <option value="">Select Account</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Period</label>
                    <select wire:model.live="periodType" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-700 rounded-md dark:bg-zinc-900 dark:text-white">
                        <option value="month">Monthly</option>
                        <option value="quarter">Quarterly</option>
                        <option value="year">Yearly</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">As of</label>
                    <input type="date" wire:model.live="asOfDate" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-700 rounded-md dark:bg-zinc-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start Date</label>
                    <input type="date" wire:model.live="start_date" @disabled($periodType !== 'custom') class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-700 rounded-md dark:bg-zinc-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">End Date</label>
                    <input type="date" wire:model.live="end_date" @disabled($periodType !== 'custom') class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-700 rounded-md dark:bg-zinc-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Transaction Type</label>
                    <select wire:model.live="transaction_type" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-700 rounded-md dark:bg-zinc-900 dark:text-white">
                        <option value="all">All Transactions</option>
                        <option value="receipts">Receipts Only</option>
                        <option value="payments">Payments Only</option>
                    </select>
                </div>
            </div>
            
            @if($account_id)
            <div class="flex gap-2 pt-4 border-t border-gray-200 dark:border-zinc-700">
                <button wire:click="exportPdf" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 text-sm font-medium">
                    📄 Export PDF
                </button>
                <button wire:click="exportExcel" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm font-medium">
                    📊 Export Excel
                </button>
                <a href="{{ route('cashbook.print', ['account_id' => $account_id, 'start_date' => $start_date, 'end_date' => $end_date, 'transaction_type' => $transaction_type]) }}" 
                   target="_blank"
                   class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">
                    🖨️ Print
                </a>
            </div>
            @endif
        </div>

        @if($account_id)
        <!-- Balance Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
                <div class="text-sm font-medium text-blue-900 dark:text-blue-200 mb-1">Opening Balance</div>
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $currency->symbol }} {{ number_format($opening_balance, 2) }}</div>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-6">
                <div class="text-sm font-medium text-green-900 dark:text-green-200 mb-1">Total Receipts</div>
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $currency->symbol }} {{ number_format($transactions->sum('debit'), 2) }}</div>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6">
                <div class="text-sm font-medium text-red-900 dark:text-red-200 mb-1">Total Payments</div>
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $currency->symbol }} {{ number_format($transactions->sum('credit'), 2) }}</div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="bg-white dark:bg-zinc-800 shadow rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                    <thead class="bg-gray-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Reference</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Receipts</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Payments</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                        <tr class="bg-gray-50 dark:bg-zinc-900/50">
                            <td colspan="5" class="px-6 py-3 text-sm font-medium text-gray-900 dark:text-white">Opening Balance</td>
                            <td class="px-6 py-3 text-sm font-bold text-right text-gray-900 dark:text-white">{{ number_format($opening_balance, 2) }}</td>
                        </tr>
                        @forelse($transactions as $transaction)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ $transaction->journalEntry->date->format('M d, Y') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ $transaction->description }}</td>
                            <td class="px-6 py-4 text-sm">
                                <a href="{{ route('journal-entries.show', $transaction->journalEntry->id) }}" 
                                   class="text-blue-600 dark:text-blue-400 hover:underline font-medium"
                                   title="View Journal Entry"
                                   wire:navigate>
                                    {{ $transaction->journalEntry->reference }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-sm text-right text-green-600 dark:text-green-400 font-medium">
                                {{ $transaction->debit > 0 ? number_format($transaction->debit, 2) : '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-right text-red-600 dark:text-red-400 font-medium">
                                {{ $transaction->credit > 0 ? number_format($transaction->credit, 2) : '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-right font-bold text-gray-900 dark:text-white">{{ number_format($transaction->running_balance, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">No transactions found for this period</td>
                        </tr>
                        @endforelse
                        <tr class="bg-gray-50 dark:bg-zinc-900/50 font-bold">
                            <td colspan="5" class="px-6 py-3 text-sm text-gray-900 dark:text-white">Closing Balance</td>
                            <td class="px-6 py-3 text-sm text-right text-gray-900 dark:text-white">{{ number_format($closing_balance, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-12 text-center">
            <p class="text-gray-500 dark:text-gray-400">Please select an account to view cashbook</p>
        </div>
        @endif
    </div>
</div>
