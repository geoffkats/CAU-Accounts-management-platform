<?php

use App\Models\Account;
use App\Models\Currency;
use Livewire\Volt\Component;
use Carbon\Carbon;

new class extends Component {
    public string $periodType = 'month';
    public string $asOfDate = '';
    public string $format = 'html';

    public function mount(): void
    {
        $this->asOfDate = now()->toDateString();
    }

    public function with(): array
    {
        $reportData = $this->generateReport();

        return [
            'reportData' => $reportData,
            'baseCurrency' => Currency::getBaseCurrency(),
            'periodStart' => $reportData['period']['start'],
            'periodEnd' => $reportData['period']['end'],
            'periodLabel' => $reportData['period']['label'],
        ];
    }

    private function getPeriodRange(): array
    {
        $asOf = Carbon::parse($this->asOfDate ?: now()->toDateString());
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

    private function isCostOfSalesAccount(Account $account): bool
    {
        return str_starts_with((string) $account->code, '52');
    }

    private function generateReport(): array
    {
        $period = $this->getPeriodRange();
        $start = $period['start'];
        $end = $period['end'];

        $incomeAccounts = Account::active()->ofType('income')->orderBy('code')->get();
        $expenseAccounts = Account::active()->ofType('expense')->orderBy('code')->get();

        $salesRows = $incomeAccounts->map(function ($account) use ($start, $end) {
            $balance = round($account->calculateBalance($start, $end), 2);
            return [
                'code' => $account->code,
                'name' => $account->name,
                'amount' => $balance,
            ];
        })->filter(fn($r) => abs($r['amount']) > 0.005)->values()->all();

        $costRows = $expenseAccounts->filter(fn($a) => $this->isCostOfSalesAccount($a))
            ->map(function ($account) use ($start, $end) {
                $balance = round($account->calculateBalance($start, $end), 2);
                return [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $balance,
                ];
            })->filter(fn($r) => abs($r['amount']) > 0.005)->values()->all();

        $expenseRows = $expenseAccounts->reject(fn($a) => $this->isCostOfSalesAccount($a))
            ->map(function ($account) use ($start, $end) {
                $balance = round($account->calculateBalance($start, $end), 2);
                return [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => $balance,
                ];
            })->filter(fn($r) => abs($r['amount']) > 0.005)->values()->all();

        $totalSales = round(array_sum(array_column($salesRows, 'amount')), 2);
        $totalCost = round(array_sum(array_column($costRows, 'amount')), 2);
        $grossProfit = round($totalSales - $totalCost, 2);
        $totalExpenses = round(array_sum(array_column($expenseRows, 'amount')), 2);
        $netProfit = round($grossProfit - $totalExpenses, 2);

        return [
            'period' => $period,
            'salesRows' => $salesRows,
            'costRows' => $costRows,
            'expenseRows' => $expenseRows,
            'totalSales' => $totalSales,
            'totalCost' => $totalCost,
            'grossProfit' => $grossProfit,
            'totalExpenses' => $totalExpenses,
            'netProfit' => $netProfit,
        ];
    }

    public function exportCsv(): void
    {
        $reportData = $this->generateReport();

        $csv = "Section,Code,Name,Amount\n";
        foreach ($reportData['salesRows'] as $row) {
            $csv .= sprintf("Sales,%s,%s,%s\n", $row['code'], $row['name'], $row['amount']);
        }
        $csv .= sprintf("Sales Total,,,%.2f\n", $reportData['totalSales']);
        foreach ($reportData['costRows'] as $row) {
            $csv .= sprintf("Cost of Sales,%s,%s,%s\n", $row['code'], $row['name'], $row['amount']);
        }
        $csv .= sprintf("Cost of Sales Total,,,%.2f\n", $reportData['totalCost']);
        $csv .= sprintf("Gross Profit,,,%.2f\n", $reportData['grossProfit']);
        foreach ($reportData['expenseRows'] as $row) {
            $csv .= sprintf("Expenses,%s,%s,%s\n", $row['code'], $row['name'], $row['amount']);
        }
        $csv .= sprintf("Expenses Total,,,%.2f\n", $reportData['totalExpenses']);
        $csv .= sprintf("Net Profit/Loss,,,%.2f\n", $reportData['netProfit']);

        $this->dispatch('download-csv', [
            'filename' => 'profit-loss-' . now()->format('Y-m-d') . '.csv',
            'content' => $csv
        ]);
    }

    public function exportPdf(): void
    {
        // PDF export would be implemented with a package like dompdf or snappy
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'PDF export coming soon!'
        ]);
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Profit & Loss Statement</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Sales, cost of sales, expenses, and net profit/loss</p>
        </div>
        
        <div class="flex gap-2">
            <button wire:click="exportCsv" 
                    class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export CSV
            </button>
                <a href="{{ route('reports.profit-loss.print', ['period' => $periodType, 'as_of' => $asOfDate]) }}"
               target="_blank"
               class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                Export PDF
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Period</label>
                <select wire:model.live="periodType"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="month">Monthly</option>
                    <option value="quarter">Quarterly</option>
                    <option value="year">Yearly</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">As Of</label>
                <input type="date" 
                       wire:model.live="asOfDate"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white" />
            </div>

            <div class="flex items-end">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <div class="font-medium text-gray-900 dark:text-white">{{ $periodLabel }} range</div>
                    <div>{{ $periodStart }} to {{ $periodEnd }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-8">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Section</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Account</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr class="bg-gray-50 dark:bg-gray-900/50 font-semibold">
                        <td class="px-6 py-3">Sales</td>
                        <td></td>
                        <td class="px-6 py-3 text-right">{{ $baseCurrency->symbol }} {{ number_format($reportData['totalSales'], 2) }}</td>
                    </tr>
                    @foreach ($reportData['salesRows'] as $row)
                        <tr>
                            <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-400"></td>
                            <td class="px-6 py-3 text-sm text-gray-900 dark:text-white">{{ $row['code'] }} - {{ $row['name'] }}</td>
                            <td class="px-6 py-3 text-sm text-right text-green-600 dark:text-green-400">{{ $baseCurrency->symbol }} {{ number_format($row['amount'], 2) }}</td>
                        </tr>
                    @endforeach

                    <tr class="bg-gray-50 dark:bg-gray-900/50 font-semibold">
                        <td class="px-6 py-3">Cost of Sales</td>
                        <td></td>
                        <td class="px-6 py-3 text-right">{{ $baseCurrency->symbol }} {{ number_format($reportData['totalCost'], 2) }}</td>
                    </tr>
                    @foreach ($reportData['costRows'] as $row)
                        <tr>
                            <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-400"></td>
                            <td class="px-6 py-3 text-sm text-gray-900 dark:text-white">{{ $row['code'] }} - {{ $row['name'] }}</td>
                            <td class="px-6 py-3 text-sm text-right text-red-600 dark:text-red-400">{{ $baseCurrency->symbol }} {{ number_format($row['amount'], 2) }}</td>
                        </tr>
                    @endforeach

                    <tr class="font-bold border-t border-gray-300 dark:border-gray-600">
                        <td class="px-6 py-3">Gross Profit</td>
                        <td></td>
                        <td class="px-6 py-3 text-right {{ $reportData['grossProfit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $baseCurrency->symbol }} {{ number_format($reportData['grossProfit'], 2) }}
                        </td>
                    </tr>

                    <tr class="bg-gray-50 dark:bg-gray-900/50 font-semibold">
                        <td class="px-6 py-3">Expenses</td>
                        <td></td>
                        <td class="px-6 py-3 text-right">{{ $baseCurrency->symbol }} {{ number_format($reportData['totalExpenses'], 2) }}</td>
                    </tr>
                    @foreach ($reportData['expenseRows'] as $row)
                        <tr>
                            <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-400"></td>
                            <td class="px-6 py-3 text-sm text-gray-900 dark:text-white">{{ $row['code'] }} - {{ $row['name'] }}</td>
                            <td class="px-6 py-3 text-sm text-right text-red-600 dark:text-red-400">{{ $baseCurrency->symbol }} {{ number_format($row['amount'], 2) }}</td>
                        </tr>
                    @endforeach

                    <tr class="font-bold border-t-2 border-gray-300 dark:border-gray-600">
                        <td class="px-6 py-3">Net Profit/Loss</td>
                        <td></td>
                        <td class="px-6 py-3 text-right {{ $reportData['netProfit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $baseCurrency->symbol }} {{ number_format($reportData['netProfit'], 2) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-xs text-gray-500 dark:text-gray-400">
        Cost of sales is calculated from expense accounts with codes starting with 52.
    </div>
</div>

@script
<script>
    $wire.on('download-csv', (event) => {
        const blob = new Blob([event.content], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = event.filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    });
</script>
@endscript
