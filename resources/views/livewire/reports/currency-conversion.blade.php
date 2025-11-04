<?php

use App\Models\Sale;
use App\Models\Expense;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Program;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

new class extends Component {
    use WithPagination;

    public string $type = 'all'; // all, income, expense
    public string $currency = '';
    public ?int $programId = null;
    public string $startDate;
    public string $endDate;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->endOfMonth()->toDateString();
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function updatingCurrency(): void
    {
        $this->resetPage();
    }

    public function updatingProgramId(): void
    {
        $this->resetPage();
    }

    public function exportCsv()
    {
        $transactions = $this->getTransactions()->get();
        $baseCurrency = Currency::getBaseCurrency();
        
        $filename = 'currency-conversion-report-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($transactions, $baseCurrency) {
            $file = fopen('php://output', 'w');
            
            // Header
            fputcsv($file, [
                'Date',
                'Type',
                'Program',
                'Description',
                'Original Currency',
                'Original Amount',
                'Exchange Rate',
                'Base Currency',
                'Base Amount',
                'Current Rate',
                'Current Base Value',
                'Gain/Loss'
            ]);

            foreach ($transactions as $transaction) {
                $currentRate = ExchangeRate::getRate($transaction->currency, $baseCurrency->code);
                $currentBaseValue = $transaction->amount * $currentRate;
                $gainLoss = $currentBaseValue - $transaction->amount_base;

                fputcsv($file, [
                    $transaction->date->format('Y-m-d'),
                    $transaction->type,
                    $transaction->program->name ?? 'N/A',
                    $transaction->description ?? $transaction->notes ?? '',
                    $transaction->currency,
                    $transaction->amount,
                    $transaction->exchange_rate,
                    $baseCurrency->code,
                    $transaction->amount_base,
                    $currentRate,
                    $currentBaseValue,
                    $gainLoss,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function getTransactions()
    {
        $query = collect();

        if (in_array($this->type, ['all', 'income'])) {
            $salesQuery = Sale::with(['program'])
                ->whereBetween('sale_date', [$this->startDate, $this->endDate])
                ->when($this->currency, fn($q) => $q->where('currency', $this->currency))
                ->when($this->programId, fn($q) => $q->where('program_id', $this->programId))
                ->get()
                ->map(function($sale) {
                    $sale->type = 'Income';
                    $sale->date = $sale->sale_date;
                    return $sale;
                });
            
            $query = $query->concat($salesQuery);
        }

        if (in_array($this->type, ['all', 'expense'])) {
            $expensesQuery = Expense::with(['program'])
                ->whereBetween('expense_date', [$this->startDate, $this->endDate])
                ->when($this->currency, fn($q) => $q->where('currency', $this->currency))
                ->when($this->programId, fn($q) => $q->where('program_id', $this->programId))
                ->get()
                ->map(function($expense) {
                    $expense->type = 'Expense';
                    $expense->date = $expense->expense_date;
                    return $expense;
                });
            
            $query = $query->concat($expensesQuery);
        }

        return $query->sortByDesc('date');
    }

    public function with(): array
    {
        $transactions = $this->getTransactions();
        // Build a paginator from the in-memory collection so Blade can call links()
        $perPage = 15;
        $page = max((int) ($this->getPage() ?? 1), 1);
        $total = $transactions->count();
        $items = $transactions->slice(($page - 1) * $perPage, $perPage)->values();
        $paginatedTransactions = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
        
        $baseCurrency = Currency::getBaseCurrency();
        $currencies = Currency::where('is_active', true)->get();
        $programs = Program::all();

        // Calculate totals and gains/losses
        $totalsByOriginalCurrency = [];
        $totalGainLoss = 0;

        foreach ($transactions as $transaction) {
            if (!isset($totalsByOriginalCurrency[$transaction->currency])) {
                $totalsByOriginalCurrency[$transaction->currency] = [
                    'original_amount' => 0,
                    'base_amount' => 0,
                    'current_base_value' => 0,
                    'gain_loss' => 0,
                ];
            }

            $currentRate = ExchangeRate::getRate($transaction->currency, $baseCurrency->code);
            $currentBaseValue = $transaction->amount * $currentRate;
            $gainLoss = $currentBaseValue - $transaction->amount_base;

            $totalsByOriginalCurrency[$transaction->currency]['original_amount'] += $transaction->amount;
            $totalsByOriginalCurrency[$transaction->currency]['base_amount'] += $transaction->amount_base;
            $totalsByOriginalCurrency[$transaction->currency]['current_base_value'] += $currentBaseValue;
            $totalsByOriginalCurrency[$transaction->currency]['gain_loss'] += $gainLoss;

            $totalGainLoss += $gainLoss;
        }

        // Exchange rate history
        $rateHistory = ExchangeRate::with(['fromCurrency', 'toCurrency'])
            ->whereBetween('effective_date', [$this->startDate, $this->endDate])
            ->orderBy('effective_date', 'desc')
            ->get()
            ->groupBy('from_currency');

        return [
            'transactions' => $paginatedTransactions,
            'total' => $total,
            'baseCurrency' => $baseCurrency,
            'currencies' => $currencies,
            'programs' => $programs,
            'totalsByOriginalCurrency' => $totalsByOriginalCurrency,
            'totalGainLoss' => $totalGainLoss,
            'rateHistory' => $rateHistory,
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Currency Conversion Report</h1>
            <p class="text-zinc-600 dark:text-zinc-400 mt-1">Track currency exposures and exchange rate impacts</p>
        </div>
        
        <button wire:click="exportCsv" 
                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors inline-flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export CSV
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-5">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    Type
                </label>
                <select wire:model.live="type"
                        class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    <option value="all">All Transactions</option>
                    <option value="income">Income Only</option>
                    <option value="expense">Expenses Only</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    Currency
                </label>
                <select wire:model.live="currency"
                        class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    <option value="">All Currencies</option>
                    @foreach($currencies as $curr)
                        <option value="{{ $curr->code }}">{{ $curr->code }} - {{ $curr->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    Program
                </label>
                <select wire:model.live="programId"
                        class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    <option value="">All Programs</option>
                    @foreach($programs as $program)
                        <option value="{{ $program->id }}">{{ $program->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    Start Date
                </label>
                <input type="date" 
                       wire:model.live="startDate"
                       class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    End Date
                </label>
                <input type="date" 
                       wire:model.live="endDate"
                       class="w-full px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-5">
            <h3 class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-3">Total Transactions</h3>
            <div class="text-3xl font-bold text-zinc-900 dark:text-white">{{ $total }}</div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-5">
            <h3 class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-3">Currencies Tracked</h3>
            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ count($totalsByOriginalCurrency) }}</div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-5">
            <h3 class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-3">Total Gain/Loss</h3>
            <div class="text-3xl font-bold {{ $totalGainLoss >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $totalGainLoss >= 0 ? '+' : '' }}{{ $baseCurrency->symbol }} {{ number_format($totalGainLoss, 2) }}
            </div>
            <div class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Unrealized {{ $totalGainLoss >= 0 ? 'gain' : 'loss' }} from rate changes
            </div>
        </div>
    </div>

    <!-- Currency Breakdown -->
    @if(count($totalsByOriginalCurrency) > 0)
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Currency Breakdown</h3>
            </div>
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Currency</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Original Amount</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Original Base Value</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Current Base Value</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Gain/Loss</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($totalsByOriginalCurrency as $currency => $totals)
                        <tr>
                            <td class="px-6 py-4 font-semibold text-zinc-900 dark:text-white">{{ $currency }}</td>
                            <td class="px-6 py-4 text-right text-zinc-600 dark:text-zinc-400">
                                {{ $currency }} {{ number_format($totals['original_amount'], 2) }}
                            </td>
                            <td class="px-6 py-4 text-right text-zinc-600 dark:text-zinc-400">
                                {{ $baseCurrency->symbol }} {{ number_format($totals['base_amount'], 2) }}
                            </td>
                            <td class="px-6 py-4 text-right text-zinc-600 dark:text-zinc-400">
                                {{ $baseCurrency->symbol }} {{ number_format($totals['current_base_value'], 2) }}
                            </td>
                            <td class="px-6 py-4 text-right font-semibold {{ $totals['gain_loss'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $totals['gain_loss'] >= 0 ? '+' : '' }}{{ $baseCurrency->symbol }} {{ number_format($totals['gain_loss'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Transaction List -->
    @if($transactions->count() > 0)
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Transaction Details</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Program</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Original</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Rate</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Base Value</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Gain/Loss</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($transactions as $transaction)
                            @php
                                $currentRate = \App\Models\ExchangeRate::getRate($transaction->currency, $baseCurrency->code);
                                $currentBaseValue = $transaction->amount * $currentRate;
                                $gainLoss = $currentBaseValue - $transaction->amount_base;
                            @endphp
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                                <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $transaction->date->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded text-xs font-semibold 
                                        {{ $transaction->type === 'Income' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' }}">
                                        {{ $transaction->type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $transaction->program->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="text-sm font-semibold text-zinc-900 dark:text-white">
                                        {{ $transaction->currency }} {{ number_format($transaction->amount, 2) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ number_format($transaction->exchange_rate, 4) }}
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-semibold text-zinc-900 dark:text-white">
                                    {{ $baseCurrency->symbol }} {{ number_format($transaction->amount_base, 2) }}
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-semibold {{ $gainLoss >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $gainLoss >= 0 ? '+' : '' }}{{ $baseCurrency->symbol }} {{ number_format($gainLoss, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $transactions->links() }}
        </div>
    @else
        <!-- Empty State -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-12 text-center">
            <svg class="w-16 h-16 text-zinc-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-2">No Transactions Found</h3>
            <p class="text-zinc-600 dark:text-zinc-400">
                Adjust your filters or date range to view currency conversion data.
            </p>
        </div>
    @endif
</div>
