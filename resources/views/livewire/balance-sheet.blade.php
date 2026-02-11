<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\CompanySetting;
use Livewire\Volt\Component;

new class extends Component {
    public string $asOfDate;
    public bool $showComparative = true;
    public string $periodType = 'month';

    public function mount(): void
    {
        $this->asOfDate = now()->toDateString();
    }

    private function getPeriodRange(): array
    {
        $asOf = \Carbon\Carbon::parse($this->asOfDate ?: now()->toDateString());
        $period = $this->periodType ?: 'month';

        switch ($period) {
            case 'quarter':
                $start = $asOf->copy()->startOfQuarter();
                $end = $asOf->copy()->endOfQuarter();
                $label = 'Quarter';
                break;
            case 'year':
                $start = $asOf->copy()->startOfYear();
                $end = $asOf->copy()->endOfYear();
                $label = 'Year';
                break;
            case 'month':
            default:
                $start = $asOf->copy()->startOfMonth();
                $end = $asOf->copy()->endOfMonth();
                $label = 'Month';
                break;
        }

        return [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'label' => $label,
        ];
    }

    private function isFixedAsset(Account $account): bool
    {
        if ($account->category === 'long_term') {
            return true;
        }
        if ($account->category === 'short_term') {
            return false;
        }
        $code = (int) $account->code;
        return $code >= 1500;
    }

    private function isLongTermLiability(Account $account): bool
    {
        if ($account->category === 'long_term') {
            return true;
        }
        if ($account->category === 'short_term') {
            return false;
        }
        $code = (int) $account->code;
        $name = strtolower($account->name ?? '');
        return $code >= 2500 || str_contains($name, 'loan') || str_contains($name, 'long');
    }

    public function with(): array
    {
        $range = $this->getPeriodRange();
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
            'id' => $a->id,
            'code' => $a->code,
            'name' => $a->name,
            'balance' => round($a->calculateBalance($start, $end), 2),
            'prior' => round($a->calculateBalance($start, $priorEnd), 2),
            'is_fixed' => $this->isFixedAsset($a),
        ])->filter(fn($r) => abs($r['balance']) > 0.0001 || abs($r['prior']) > 0.0001)->values();

        $liabilityRows = $liabilities->map(fn($a) => [
            'id' => $a->id,
            'code' => $a->code,
            'name' => $a->name,
            'balance' => round($a->calculateBalance($start, $end), 2),
            'prior' => round($a->calculateBalance($start, $priorEnd), 2),
            'is_long_term' => $this->isLongTermLiability($a),
        ])->filter(fn($r) => abs($r['balance']) > 0.0001 || abs($r['prior']) > 0.0001)->values();

        $equityRows = $equity->map(fn($a) => [
            'id' => $a->id,
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

        $fixedAssets = $assetRows->filter(fn($r) => $r['is_fixed'])->values();
        $currentAssets = $assetRows->filter(fn($r) => !$r['is_fixed'])->values();

        $longTermLiabilities = $liabilityRows->filter(fn($r) => $r['is_long_term'])->values();
        $shortTermLiabilities = $liabilityRows->filter(fn($r) => !$r['is_long_term'])->values();

        $totalAssets = round(array_sum(array_column($assetRows->all(), 'balance')), 2);
        $totalLiabilities = round(array_sum(array_column($liabilityRows->all(), 'balance')), 2);
        $totalEquity = round(array_sum(array_column($equityRows->all(), 'balance')), 2);
        $equityWithEarnings = round($totalEquity + $netIncome, 2);

        $totalAssetsPrior = round(array_sum(array_column($assetRows->all(), 'prior')), 2);
        $totalLiabilitiesPrior = round(array_sum(array_column($liabilityRows->all(), 'prior')), 2);
        $totalEquityPrior = round(array_sum(array_column($equityRows->all(), 'prior')), 2);
        $equityWithEarningsPrior = round($totalEquityPrior + $netIncomePrior, 2);

        $totalFixedAssets = round(array_sum(array_column($fixedAssets->all(), 'balance')), 2);
        $totalCurrentAssets = round(array_sum(array_column($currentAssets->all(), 'balance')), 2);
        $totalFixedAssetsPrior = round(array_sum(array_column($fixedAssets->all(), 'prior')), 2);
        $totalCurrentAssetsPrior = round(array_sum(array_column($currentAssets->all(), 'prior')), 2);

        $totalLongTermLiabilities = round(array_sum(array_column($longTermLiabilities->all(), 'balance')), 2);
        $totalShortTermLiabilities = round(array_sum(array_column($shortTermLiabilities->all(), 'balance')), 2);
        $totalLongTermLiabilitiesPrior = round(array_sum(array_column($longTermLiabilities->all(), 'prior')), 2);
        $totalShortTermLiabilitiesPrior = round(array_sum(array_column($shortTermLiabilities->all(), 'prior')), 2);

        return [
            'assetRows' => $assetRows,
            'liabilityRows' => $liabilityRows,
            'equityRows' => $equityRows,
            'fixedAssets' => $fixedAssets,
            'currentAssets' => $currentAssets,
            'longTermLiabilities' => $longTermLiabilities,
            'shortTermLiabilities' => $shortTermLiabilities,
            'totalFixedAssets' => $totalFixedAssets,
            'totalCurrentAssets' => $totalCurrentAssets,
            'totalFixedAssetsPrior' => $totalFixedAssetsPrior,
            'totalCurrentAssetsPrior' => $totalCurrentAssetsPrior,
            'totalLongTermLiabilities' => $totalLongTermLiabilities,
            'totalShortTermLiabilities' => $totalShortTermLiabilities,
            'totalLongTermLiabilitiesPrior' => $totalLongTermLiabilitiesPrior,
            'totalShortTermLiabilitiesPrior' => $totalShortTermLiabilitiesPrior,
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
            'periodStart' => $range['start'],
            'periodEnd' => $range['end'],
            'periodLabel' => $range['label'],
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

    public function updatedPeriodType(): void
    {
        $asOf = \Carbon\Carbon::parse($this->asOfDate ?: now()->toDateString());
        if ($this->periodType === 'quarter') {
            $this->asOfDate = $asOf->endOfQuarter()->toDateString();
        } elseif ($this->periodType === 'year') {
            $this->asOfDate = $asOf->endOfYear()->toDateString();
        } else {
            $this->asOfDate = $asOf->endOfMonth()->toDateString();
        }
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
            <label class="text-sm text-gray-700 dark:text-gray-200">Period</label>
            <select wire:model.live="periodType" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="month">Monthly</option>
                <option value="quarter">Quarterly</option>
                <option value="year">Yearly</option>
            </select>
            <button wire:click="$toggle('showComparative')" class="px-3 py-1 text-xs rounded border {{ $showComparative ? 'bg-emerald-50 border-emerald-300 text-emerald-700 dark:bg-emerald-900/20 dark:border-emerald-700 dark:text-emerald-400' : 'bg-gray-100 border-gray-300 dark:bg-gray-700 dark:border-gray-600' }}">
                {{ $showComparative ? '✓ Comparative' : 'Comparative' }}
            </button>
            <label class="text-sm text-gray-700 dark:text-gray-200">As of</label>
            <input type="date" wire:model.live="asOfDate" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            <div class="hidden md:flex items-center gap-1">
                <button wire:click="setToday" class="px-2 py-1 text-xs rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Today</button>
                <button wire:click="setMonthEnd" class="px-2 py-1 text-xs rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Month End</button>
                <button wire:click="setQuarterEnd" class="px-2 py-1 text-xs rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Quarter End</button>
                <button wire:click="setYearEnd" class="px-2 py-1 text-xs rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">Year End</button>
            </div>
            <a href="{{ route('reports.balance-sheet.print', ['as_of_date' => $asOfDate, 'show_comparative' => $showComparative ? '1' : '0']) }}" target="_blank" class="ml-2 px-3 py-2 text-xs rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200">Print</a>
        </div>
    </div>
    <div class="text-xs text-gray-500 dark:text-gray-400">
        {{ $periodLabel }} period: {{ $periodStart }} to {{ $periodEnd }}
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Assets -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-3 bg-emerald-600 text-white font-semibold">Assets</div>

            <div class="px-4 py-2 text-sm font-semibold text-gray-800 dark:text-gray-200 bg-emerald-50/70 dark:bg-emerald-900/20">Fixed Assets</div>
            <div class="p-4 divide-y divide-gray-200/70 dark:divide-gray-700/70">
                @forelse($fixedAssets as $row)
                    <div class="py-2 flex justify-between text-sm gap-4">
                        <a href="{{ route('account-statement', $row['id']) }}?start_date={{ $periodStart }}&end_date={{ $asOfDate }}" class="text-gray-700 dark:text-gray-300 hover:underline">
                            {{ $row['code'] }} — {{ $row['name'] }}
                        </a>
                        <div class="flex gap-3 items-center">
                            @if($showComparative)
                                <span class="font-medium text-gray-500 dark:text-gray-400 tabular-nums text-right min-w-[100px] text-xs">{{ ($baseCurrency->symbol ?? '') }} {{ $row['prior'] < 0 ? '(' . number_format(abs($row['prior']), 2) . ')' : number_format($row['prior'], 2) }}</span>
                            @endif
                            <span class="font-medium text-gray-900 dark:text-gray-100 tabular-nums text-right min-w-[120px]">{{ ($baseCurrency->symbol ?? '') }} {{ $row['balance'] < 0 ? '(' . number_format(abs($row['balance']), 2) . ')' : number_format($row['balance'], 2) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No fixed asset balances.</p>
                @endforelse
            </div>
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/40 flex justify-between font-semibold text-gray-900 dark:text-gray-100">
                <span>Total Fixed Assets</span>
                <div class="flex gap-3 items-center">
                    @if($showComparative)
                        <span class="tabular-nums text-gray-500 dark:text-gray-400 text-xs min-w-[100px] text-right">{{ ($baseCurrency->symbol ?? '') }} {{ $totalFixedAssetsPrior < 0 ? '(' . number_format(abs($totalFixedAssetsPrior), 2) . ')' : number_format($totalFixedAssetsPrior, 2) }}</span>
                    @endif
                    <span class="tabular-nums min-w-[120px] text-right">{{ ($baseCurrency->symbol ?? '') }} {{ $totalFixedAssets < 0 ? '(' . number_format(abs($totalFixedAssets), 2) . ')' : number_format($totalFixedAssets, 2) }}</span>
                </div>
            </div>

            <div class="px-4 py-2 text-sm font-semibold text-gray-800 dark:text-gray-200 bg-emerald-50/70 dark:bg-emerald-900/20">Current Assets</div>
            <div class="p-4 divide-y divide-gray-200/70 dark:divide-gray-700/70">
                @forelse($currentAssets as $row)
                    <div class="py-2 flex justify-between text-sm gap-4">
                        <a href="{{ route('account-statement', $row['id']) }}?start_date={{ $periodStart }}&end_date={{ $asOfDate }}" class="text-gray-700 dark:text-gray-300 hover:underline">
                            {{ $row['code'] }} — {{ $row['name'] }}
                        </a>
                        <div class="flex gap-3 items-center">
                            @if($showComparative)
                                <span class="font-medium text-gray-500 dark:text-gray-400 tabular-nums text-right min-w-[100px] text-xs">{{ ($baseCurrency->symbol ?? '') }} {{ $row['prior'] < 0 ? '(' . number_format(abs($row['prior']), 2) . ')' : number_format($row['prior'], 2) }}</span>
                            @endif
                            <span class="font-medium text-gray-900 dark:text-gray-100 tabular-nums text-right min-w-[120px]">{{ ($baseCurrency->symbol ?? '') }} {{ $row['balance'] < 0 ? '(' . number_format(abs($row['balance']), 2) . ')' : number_format($row['balance'], 2) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No current asset balances.</p>
                @endforelse
            </div>
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/40 flex justify-between font-semibold text-gray-900 dark:text-gray-100">
                <span>Total Current Assets</span>
                <div class="flex gap-3 items-center">
                    @if($showComparative)
                        <span class="tabular-nums text-gray-500 dark:text-gray-400 text-xs min-w-[100px] text-right">{{ ($baseCurrency->symbol ?? '') }} {{ $totalCurrentAssetsPrior < 0 ? '(' . number_format(abs($totalCurrentAssetsPrior), 2) . ')' : number_format($totalCurrentAssetsPrior, 2) }}</span>
                    @endif
                    <span class="tabular-nums min-w-[120px] text-right">{{ ($baseCurrency->symbol ?? '') }} {{ $totalCurrentAssets < 0 ? '(' . number_format(abs($totalCurrentAssets), 2) . ')' : number_format($totalCurrentAssets, 2) }}</span>
                </div>
            </div>

            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/40 flex justify-between font-semibold text-gray-900 dark:text-gray-100 border-t border-gray-200 dark:border-gray-700">
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

            <div class="px-4 py-2 text-sm font-semibold text-gray-800 dark:text-gray-200 bg-sky-50/70 dark:bg-sky-900/20">Long-term Liabilities</div>
            <div class="p-4 divide-y divide-gray-200/70 dark:divide-gray-700/70">
                @forelse($longTermLiabilities as $row)
                    <div class="py-2 flex justify-between text-sm gap-4">
                        <a href="{{ route('account-statement', $row['id']) }}?start_date={{ $periodStart }}&end_date={{ $asOfDate }}" class="text-gray-700 dark:text-gray-300 hover:underline">
                            {{ $row['code'] }} — {{ $row['name'] }}
                        </a>
                        <div class="flex gap-3 items-center">
                            @if($showComparative)
                                <span class="font-medium text-gray-500 dark:text-gray-400 tabular-nums text-right min-w-[100px] text-xs">{{ ($baseCurrency->symbol ?? '') }} {{ $row['prior'] < 0 ? '(' . number_format(abs($row['prior']), 2) . ')' : number_format($row['prior'], 2) }}</span>
                            @endif
                            <span class="font-medium text-gray-900 dark:text-gray-100 tabular-nums text-right min-w-[120px]">{{ ($baseCurrency->symbol ?? '') }} {{ $row['balance'] < 0 ? '(' . number_format(abs($row['balance']), 2) . ')' : number_format($row['balance'], 2) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No long-term liabilities.</p>
                @endforelse
            </div>
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/40 flex justify-between font-semibold text-gray-900 dark:text-gray-100">
                <span>Total Long-term Liabilities</span>
                <div class="flex gap-3 items-center">
                    @if($showComparative)
                        <span class="tabular-nums text-gray-500 dark:text-gray-400 text-xs min-w-[100px] text-right">{{ ($baseCurrency->symbol ?? '') }} {{ $totalLongTermLiabilitiesPrior < 0 ? '(' . number_format(abs($totalLongTermLiabilitiesPrior), 2) . ')' : number_format($totalLongTermLiabilitiesPrior, 2) }}</span>
                    @endif
                    <span class="tabular-nums min-w-[120px] text-right">{{ ($baseCurrency->symbol ?? '') }} {{ $totalLongTermLiabilities < 0 ? '(' . number_format(abs($totalLongTermLiabilities), 2) . ')' : number_format($totalLongTermLiabilities, 2) }}</span>
                </div>
            </div>

            <div class="px-4 py-2 text-sm font-semibold text-gray-800 dark:text-gray-200 bg-sky-50/70 dark:bg-sky-900/20">Short-term Liabilities</div>
            <div class="p-4 divide-y divide-gray-200/70 dark:divide-gray-700/70">
                @forelse($shortTermLiabilities as $row)
                    <div class="py-2 flex justify-between text-sm gap-4">
                        <a href="{{ route('account-statement', $row['id']) }}?start_date={{ $periodStart }}&end_date={{ $asOfDate }}" class="text-gray-700 dark:text-gray-300 hover:underline">
                            {{ $row['code'] }} — {{ $row['name'] }}
                        </a>
                        <div class="flex gap-3 items-center">
                            @if($showComparative)
                                <span class="font-medium text-gray-500 dark:text-gray-400 tabular-nums text-right min-w-[100px] text-xs">{{ ($baseCurrency->symbol ?? '') }} {{ $row['prior'] < 0 ? '(' . number_format(abs($row['prior']), 2) . ')' : number_format($row['prior'], 2) }}</span>
                            @endif
                            <span class="font-medium text-gray-900 dark:text-gray-100 tabular-nums text-right min-w-[120px]">{{ ($baseCurrency->symbol ?? '') }} {{ $row['balance'] < 0 ? '(' . number_format(abs($row['balance']), 2) . ')' : number_format($row['balance'], 2) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No short-term liabilities.</p>
                @endforelse
            </div>
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/40 flex justify-between font-semibold text-gray-900 dark:text-gray-100">
                <span>Total Short-term Liabilities</span>
                <div class="flex gap-3 items-center">
                    @if($showComparative)
                        <span class="tabular-nums text-gray-500 dark:text-gray-400 text-xs min-w-[100px] text-right">{{ ($baseCurrency->symbol ?? '') }} {{ $totalShortTermLiabilitiesPrior < 0 ? '(' . number_format(abs($totalShortTermLiabilitiesPrior), 2) . ')' : number_format($totalShortTermLiabilitiesPrior, 2) }}</span>
                    @endif
                    <span class="tabular-nums min-w-[120px] text-right">{{ ($baseCurrency->symbol ?? '') }} {{ $totalShortTermLiabilities < 0 ? '(' . number_format(abs($totalShortTermLiabilities), 2) . ')' : number_format($totalShortTermLiabilities, 2) }}</span>
                </div>
            </div>

            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/40 flex justify-between font-semibold text-gray-900 dark:text-gray-100 border-t border-gray-200 dark:border-gray-700">
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
            <div class="px-4 py-3 bg-indigo-600 text-white font-semibold">Owners' Equity</div>
            <div class="p-4 divide-y divide-gray-200/70 dark:divide-gray-700/70">
                @forelse($equityRows as $row)
                    <div class="py-2 flex justify-between text-sm gap-4">
                        <a href="{{ route('account-statement', $row['id']) }}?start_date={{ $periodStart }}&end_date={{ $asOfDate }}" class="text-gray-700 dark:text-gray-300 hover:underline">
                            {{ $row['code'] }} — {{ $row['name'] }}
                        </a>
                        <div class="flex gap-3 items-center">
                            @if($showComparative)
                                <span class="font-medium text-gray-500 dark:text-gray-400 tabular-nums text-right min-w-[100px] text-xs">{{ ($baseCurrency->symbol ?? '') }} {{ $row['prior'] < 0 ? '(' . number_format(abs($row['prior']), 2) . ')' : number_format($row['prior'], 2) }}</span>
                            @endif
                            <span class="font-medium text-gray-900 dark:text-gray-100 tabular-nums text-right min-w-[120px]">{{ ($baseCurrency->symbol ?? '') }} {{ $row['balance'] < 0 ? '(' . number_format(abs($row['balance']), 2) . ')' : number_format($row['balance'], 2) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No equity balances.</p>
                @endforelse
                <div class="py-2 flex justify-between text-sm gap-4">
                    <span class="text-gray-700 dark:text-gray-300 italic">Net Income</span>
                    <div class="flex gap-3 items-center">
                        @if($showComparative)
                            <span class="font-medium {{ $netIncomePrior >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} opacity-60 tabular-nums text-right min-w-[100px] text-xs">{{ ($baseCurrency->symbol ?? '') }} {{ $netIncomePrior < 0 ? '(' . number_format(abs($netIncomePrior), 2) . ')' : number_format($netIncomePrior, 2) }}</span>
                        @endif
                        <span class="font-medium {{ $netIncome >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} tabular-nums text-right min-w-[120px]">{{ ($baseCurrency->symbol ?? '') }} {{ $netIncome < 0 ? '(' . number_format(abs($netIncome), 2) . ')' : number_format($netIncome, 2) }}</span>
                    </div>
                </div>
            </div>
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/40 flex justify-between font-semibold text-gray-900 dark:text-gray-100">
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
        <div class="flex justify-between text-sm font-semibold mb-2 text-gray-900 dark:text-gray-100">
            <span>Current Period Earnings (Included in Equity)</span>
            <div class="flex gap-3 items-center">
                @if($showComparative)
                    <span class="{{ $netIncomePrior >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} opacity-60 tabular-nums text-xs min-w-[100px] text-right">{{ ($baseCurrency->symbol ?? '') }} {{ $netIncomePrior < 0 ? '(' . number_format(abs($netIncomePrior), 2) . ')' : number_format($netIncomePrior, 2) }}</span>
                @endif
                <span class="{{ $netIncome >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} tabular-nums min-w-[120px] text-right">{{ ($baseCurrency->symbol ?? '') }} {{ $netIncome < 0 ? '(' . number_format(abs($netIncome), 2) . ')' : number_format($netIncome, 2) }}</span>
            </div>
        </div>
        <div class="flex justify-between text-sm font-semibold text-gray-900 dark:text-gray-100">
            <span>Accounting Equation Check (Assets vs Liabilities + Equity + Earnings)</span>
            <span>
                {{ ($baseCurrency->symbol ?? '') }} {{ $totalAssets < 0 ? '(' . number_format(abs($totalAssets), 2) . ')' : number_format($totalAssets, 2) }} = {{ ($baseCurrency->symbol ?? '') }} {{ ($totalLiabilities + $equityWithEarnings) < 0 ? '(' . number_format(abs($totalLiabilities + $equityWithEarnings), 2) . ')' : number_format(($totalLiabilities + $equityWithEarnings), 2) }}
                {!! abs(($totalAssets - ($totalLiabilities + $equityWithEarnings))) < 0.01 ? '<span class="ml-2 text-emerald-600 dark:text-emerald-400">✓ Balanced</span>' : '<span class="ml-2 text-rose-600 dark:text-rose-400">⚠ Imbalance</span>' !!}
            </span>
        </div>
        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            Category overrides: set account category to short-term or long-term in the chart of accounts. Defaults use codes 1500+ for fixed assets and 2500+ or "loan" for long-term liabilities.
        </div>
    </div>
</div>
