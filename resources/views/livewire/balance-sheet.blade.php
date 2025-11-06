<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\CompanySetting;
use Livewire\Volt\Component;

new class extends Component {
    public string $asOfDate;
    public bool $showComparative = true;

    public function mount(): void
    {
        $this->asOfDate = now()->toDateString();
    }

    public function with(): array
    {
        $start = '1900-01-01';
        $end = $this->asOfDate;

        $settings = CompanySetting::get();
        $asOfLabel = \Carbon\Carbon::parse($end)->format($settings->date_format ?? 'Y-m-d');

        // Prior period: one year prior
        $priorEnd = \Carbon\Carbon::parse($end)->subYear()->toDateString();
        $priorLabel = \Carbon\Carbon::parse($priorEnd)->format($settings->date_format ?? 'Y-m-d');

        // Fetch active accounts grouped by type
        $assets = Account::active()->ofType('asset')->orderBy('code')->get();
        $liabilities = Account::active()->ofType('liability')->orderBy('code')->get();
        $equity = Account::active()->ofType('equity')->orderBy('code')->get();
        $income = Account::active()->ofType('income')->orderBy('code')->get();
        $expenses = Account::active()->ofType('expense')->orderBy('code')->get();

        // Calculate balances AS OF selected date
        $assetRows = $assets->map(fn($a) => [
            'code' => $a->code,
            'name' => $a->name,
            'balance' => round($a->calculateBalance($start, $end), 2),
            'prior' => round($a->calculateBalance($start, $priorEnd), 2),
        ])->filter(fn($r) => abs($r['balance']) > 0.0001 || abs($r['prior']) > 0.0001)->values();

        $liabilityRows = $liabilities->map(fn($a) => [
            'code' => $a->code,
            'name' => $a->name,
            'balance' => round($a->calculateBalance($start, $end), 2),
            'prior' => round($a->calculateBalance($start, $priorEnd), 2),
        ])->filter(fn($r) => abs($r['balance']) > 0.0001 || abs($r['prior']) > 0.0001)->values();

        $equityRows = $equity->map(fn($a) => [
            'code' => $a->code,
            'name' => $a->name,
            'balance' => round($a->calculateBalance($start, $end), 2),
            'prior' => round($a->calculateBalance($start, $priorEnd), 2),
        ])->filter(fn($r) => abs($r['balance']) > 0.0001 || abs($r['prior']) > 0.0001)->values();

        // Compute Net Income as of date (current and prior)
        $totalIncome = round($income->sum(fn($a) => $a->calculateBalance($start, $end)), 2);
        $totalExpenses = round($expenses->sum(fn($a) => $a->calculateBalance($start, $end)), 2);
        $netIncome = round($totalIncome - $totalExpenses, 2);

        $priorIncome = round($income->sum(fn($a) => $a->calculateBalance($start, $priorEnd)), 2);
        $priorExpenses = round($expenses->sum(fn($a) => $a->calculateBalance($start, $priorEnd)), 2);
        $netIncomePrior = round($priorIncome - $priorExpenses, 2);

        $totalAssets = round(array_sum(array_column($assetRows->all(), 'balance')), 2);
        $totalLiabilities = round(array_sum(array_column($liabilityRows->all(), 'balance')), 2);
        $totalEquity = round(array_sum(array_column($equityRows->all(), 'balance')), 2);
        $equityWithEarnings = round($totalEquity + $netIncome, 2);

        $totalAssetsPrior = round(array_sum(array_column($assetRows->all(), 'prior')), 2);
        $totalLiabilitiesPrior = round(array_sum(array_column($liabilityRows->all(), 'prior')), 2);
        $totalEquityPrior = round(array_sum(array_column($equityRows->all(), 'prior')), 2);
        $equityWithEarningsPrior = round($totalEquityPrior + $netIncomePrior, 2);

        return [
            'assetRows' => $assetRows,
            'liabilityRows' => $liabilityRows,
            'equityRows' => $equityRows,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'totalEquity' => $totalEquity,
            'netIncome' => $netIncome,
            'equityWithEarnings' => $equityWithEarnings,
            'totalAssetsPrior' => $totalAssetsPrior,
            'totalLiabilitiesPrior' => $totalLiabilitiesPrior,
            'totalEquityPrior' => $totalEquityPrior,
            'netIncomePrior' => $netIncomePrior,
            'equityWithEarningsPrior' => $equityWithEarningsPrior,
            'baseCurrency' => Currency::getBaseCurrency(),
            'settings' => $settings,
            'asOfLabel' => $asOfLabel,
            'priorLabel' => $priorLabel,
        ];
    }

    public function setToday(): void
    {
        $this->asOfDate = now()->toDateString();
    }

    public function setMonthEnd(): void
    {
        $this->asOfDate = now()->endOfMonth()->toDateString();
    }

    public function setQuarterEnd(): void
    {
        $this->asOfDate = now()->endOfQuarter()->toDateString();
    }

    public function setYearEnd(): void
    {
        $this->asOfDate = now()->endOfYear()->toDateString();
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center gap-4">
        <div>
            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $settings->company_name ?? config('app.name') }}</div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 bg-clip-text text-transparent">
                Balance Sheet
            </h1>
            <div class="text-xs text-gray-500 dark:text-gray-400">
                As of {{ $asOfLabel }}
                @if($showComparative)
                    <span class="ml-2 text-gray-400">vs {{ $priorLabel }}</span>
                @endif
            </div>
        </div>
        <div class="ml-auto flex items-center gap-2">
            <button wire:click="$toggle('showComparative')" class="px-3 py-1 text-xs rounded border {{ $showComparative ? 'bg-emerald-50 border-emerald-300 text-emerald-700 dark:bg-emerald-900/20 dark:border-emerald-700 dark:text-emerald-400' : 'bg-gray-100 border-gray-300 dark:bg-gray-700 dark:border-gray-600' }}">
                {{ $showComparative ? '✓ Comparative' : 'Comparative' }}
            </button>
            <label class="text-sm text-gray-600 dark:text-gray-300">As of</label>
            <input type="date" wire:model.live="asOfDate" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            <div class="hidden md:flex items-center gap-1">
                <button wire:click="setToday" class="px-2 py-1 text-xs rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700">Today</button>
                <button wire:click="setMonthEnd" class="px-2 py-1 text-xs rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700">Month End</button>
                <button wire:click="setQuarterEnd" class="px-2 py-1 text-xs rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700">Quarter End</button>
                <button wire:click="setYearEnd" class="px-2 py-1 text-xs rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700">Year End</button>
            </div>
            <a href="{{ route('reports.balance-sheet.print', ['as_of_date' => $asOfDate, 'show_comparative' => $showComparative ? '1' : '0']) }}" target="_blank" class="ml-2 px-3 py-2 text-xs rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 border border-gray-300 dark:border-gray-600">Print</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Assets -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-3 bg-emerald-600 text-white font-semibold">Assets</div>
            <div class="p-4 divide-y divide-gray-200/70 dark:divide-gray-700/70">
                @forelse($assetRows as $row)
                    <div class="py-2 flex justify-between text-sm gap-4">
                        <span class="text-gray-700 dark:text-gray-300">{{ $row['code'] }} — {{ $row['name'] }}</span>
                        <div class="flex gap-3 items-center">
                            @if($showComparative)
                                <span class="font-medium text-gray-500 dark:text-gray-400 tabular-nums text-right min-w-[100px] text-xs">{{ ($baseCurrency->symbol ?? '') }} {{ $row['prior'] < 0 ? '(' . number_format(abs($row['prior']), 2) . ')' : number_format($row['prior'], 2) }}</span>
                            @endif
                            <span class="font-medium text-gray-900 dark:text-gray-100 tabular-nums text-right min-w-[120px]">{{ ($baseCurrency->symbol ?? '') }} {{ $row['balance'] < 0 ? '(' . number_format(abs($row['balance']), 2) . ')' : number_format($row['balance'], 2) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No asset balances.</p>
                @endforelse
            </div>
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/40 flex justify-between font-semibold">
                <span>Total Assets</span>
                <div class="flex gap-3 items-center">
                    @if($showComparative)
                        <span class="tabular-nums text-gray-500 dark:text-gray-400 text-xs min-w-[100px] text-right">{{ ($baseCurrency->symbol ?? '') }} {{ $totalAssetsPrior < 0 ? '(' . number_format(abs($totalAssetsPrior), 2) . ')' : number_format($totalAssetsPrior, 2) }}</span>
                    @endif
                    <span class="tabular-nums min-w-[120px] text-right">{{ ($baseCurrency->symbol ?? '') }} {{ $totalAssets < 0 ? '(' . number_format(abs($totalAssets), 2) . ')' : number_format($totalAssets, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Liabilities -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-3 bg-sky-600 text-white font-semibold">Liabilities</div>
            <div class="p-4 divide-y divide-gray-200/70 dark:divide-gray-700/70">
                @forelse($liabilityRows as $row)
                    <div class="py-2 flex justify-between text-sm gap-4">
                        <span class="text-gray-700 dark:text-gray-300">{{ $row['code'] }} — {{ $row['name'] }}</span>
                        <div class="flex gap-3 items-center">
                            @if($showComparative)
                                <span class="font-medium text-gray-500 dark:text-gray-400 tabular-nums text-right min-w-[100px] text-xs">{{ ($baseCurrency->symbol ?? '') }} {{ $row['prior'] < 0 ? '(' . number_format(abs($row['prior']), 2) . ')' : number_format($row['prior'], 2) }}</span>
                            @endif
                            <span class="font-medium text-gray-900 dark:text-gray-100 tabular-nums text-right min-w-[120px]">{{ ($baseCurrency->symbol ?? '') }} {{ $row['balance'] < 0 ? '(' . number_format(abs($row['balance']), 2) . ')' : number_format($row['balance'], 2) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No liability balances.</p>
                @endforelse
            </div>
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/40 flex justify-between font-semibold">
                <span>Total Liabilities</span>
                <div class="flex gap-3 items-center">
                    @if($showComparative)
                        <span class="tabular-nums text-gray-500 dark:text-gray-400 text-xs min-w-[100px] text-right">{{ ($baseCurrency->symbol ?? '') }} {{ $totalLiabilitiesPrior < 0 ? '(' . number_format(abs($totalLiabilitiesPrior), 2) . ')' : number_format($totalLiabilitiesPrior, 2) }}</span>
                    @endif
                    <span class="tabular-nums min-w-[120px] text-right">{{ ($baseCurrency->symbol ?? '') }} {{ $totalLiabilities < 0 ? '(' . number_format(abs($totalLiabilities), 2) . ')' : number_format($totalLiabilities, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Equity -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-3 bg-indigo-600 text-white font-semibold">Equity</div>
            <div class="p-4 divide-y divide-gray-200/70 dark:divide-gray-700/70">
                @forelse($equityRows as $row)
                    <div class="py-2 flex justify-between text-sm gap-4">
                        <span class="text-gray-700 dark:text-gray-300">{{ $row['code'] }} — {{ $row['name'] }}</span>
                        <div class="flex gap-3 items-center">
                            @if($showComparative)
                                <span class="font-medium text-gray-500 dark:text-gray-400 tabular-nums text-right min-w-[100px] text-xs">{{ ($baseCurrency->symbol ?? '') }} {{ $row['prior'] < 0 ? '(' . number_format(abs($row['prior']), 2) . ')' : number_format($row['prior'], 2) }}</span>
                            @endif
                            <span class="font-medium text-gray-900 dark:text-gray-100 tabular-nums text-right min-w-[120px]">{{ ($baseCurrency->symbol ?? '') }} {{ $row['balance'] < 0 ? '(' . number_format(abs($row['balance']), 2) . ')' : number_format($row['balance'], 2) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No equity balances.</p>
                @endforelse
                <div class="py-2 flex justify-between text-sm gap-4">
                    <span class="text-gray-700 dark:text-gray-300 italic">Net Income</span>
                    <div class="flex gap-3 items-center">
                        @if($showComparative)
                            <span class="font-medium {{ $netIncomePrior >= 0 ? 'text-emerald-600/60' : 'text-rose-600/60' }} tabular-nums text-right min-w-[100px] text-xs">{{ ($baseCurrency->symbol ?? '') }} {{ $netIncomePrior < 0 ? '(' . number_format(abs($netIncomePrior), 2) . ')' : number_format($netIncomePrior, 2) }}</span>
                        @endif
                        <span class="font-medium {{ $netIncome >= 0 ? 'text-emerald-600' : 'text-rose-600' }} tabular-nums text-right min-w-[120px]">{{ ($baseCurrency->symbol ?? '') }} {{ $netIncome < 0 ? '(' . number_format(abs($netIncome), 2) . ')' : number_format($netIncome, 2) }}</span>
                    </div>
                </div>
            </div>
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/40 flex justify-between font-semibold">
                <span>Total Equity</span>
                <div class="flex gap-3 items-center">
                    @if($showComparative)
                        <span class="tabular-nums text-gray-500 dark:text-gray-400 text-xs min-w-[100px] text-right">{{ ($baseCurrency->symbol ?? '') }} {{ $totalEquityPrior < 0 ? '(' . number_format(abs($totalEquityPrior), 2) . ')' : number_format($totalEquityPrior, 2) }}</span>
                    @endif
                    <span class="tabular-nums min-w-[120px] text-right">{{ ($baseCurrency->symbol ?? '') }} {{ $totalEquity < 0 ? '(' . number_format(abs($totalEquity), 2) . ')' : number_format($totalEquity, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex justify-between text-sm font-semibold mb-2">
            <span>Current Period Earnings (Included in Equity)</span>
            <div class="flex gap-3 items-center">
                @if($showComparative)
                    <span class="{{ $netIncomePrior >= 0 ? 'text-emerald-600/60' : 'text-rose-600/60' }} tabular-nums text-xs min-w-[100px] text-right">{{ ($baseCurrency->symbol ?? '') }} {{ $netIncomePrior < 0 ? '(' . number_format(abs($netIncomePrior), 2) . ')' : number_format($netIncomePrior, 2) }}</span>
                @endif
                <span class="{{ $netIncome >= 0 ? 'text-emerald-600' : 'text-rose-600' }} tabular-nums min-w-[120px] text-right">{{ ($baseCurrency->symbol ?? '') }} {{ $netIncome < 0 ? '(' . number_format(abs($netIncome), 2) . ')' : number_format($netIncome, 2) }}</span>
            </div>
        </div>
        <div class="flex justify-between text-sm font-semibold">
            <span>Accounting Equation Check (Assets vs Liabilities + Equity + Earnings)</span>
            <span>
                {{ ($baseCurrency->symbol ?? '') }} {{ $totalAssets < 0 ? '(' . number_format(abs($totalAssets), 2) . ')' : number_format($totalAssets, 2) }} = {{ ($baseCurrency->symbol ?? '') }} {{ ($totalLiabilities + $equityWithEarnings) < 0 ? '(' . number_format(abs($totalLiabilities + $equityWithEarnings), 2) . ')' : number_format(($totalLiabilities + $equityWithEarnings), 2) }}
                {!! abs(($totalAssets - ($totalLiabilities + $equityWithEarnings))) < 0.01 ? '<span class="ml-2 text-emerald-600">✓ Balanced</span>' : '<span class="ml-2 text-rose-600">⚠ Imbalance</span>' !!}
            </span>
        </div>
    </div>
</div>
