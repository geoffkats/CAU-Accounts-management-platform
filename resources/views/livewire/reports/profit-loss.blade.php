<?php

use App\Models\Program;
use App\Models\Sale;
use App\Models\Expense;
use App\Models\Currency;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public ?int $program_id = null;
    public string $start_date = '';
    public string $end_date = '';
    public string $format = 'html';

    public function mount(): void
    {
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->endOfMonth()->format('Y-m-d');
    }

    public function with(): array
    {
        $programs = Program::all();
        $reportData = $this->generateReport();

        return [
            'programs' => $programs,
            'reportData' => $reportData,
            'grandTotalIncome' => collect($reportData)->sum('income'),
            'grandTotalExpenses' => collect($reportData)->sum('expenses'),
            'grandTotalProfit' => collect($reportData)->sum('profit'),
            'baseCurrency' => Currency::getBaseCurrency(),
        ];
    }

    private function generateReport(): array
    {
        $query = Program::query()
            ->when($this->program_id, fn($q) => $q->where('id', $this->program_id));

        return $query->get()->map(function ($program) {
            $income = $program->sales()
                ->whereBetween('sale_date', [$this->start_date, $this->end_date])
                ->sum('amount_base') ?: $program->sales()
                ->whereBetween('sale_date', [$this->start_date, $this->end_date])
                ->sum('amount');

            $expenses = $program->expenses()
                ->whereBetween('expense_date', [$this->start_date, $this->end_date])
                ->sum('amount_base') ?: $program->expenses()
                ->whereBetween('expense_date', [$this->start_date, $this->end_date])
                ->sum('amount');

            $profit = $income - $expenses;

            return [
                'program' => $program->name,
                'code' => $program->code,
                'income' => $income,
                'expenses' => $expenses,
                'profit' => $profit,
                'margin' => $income > 0 ? (($profit / $income) * 100) : 0,
            ];
        })->toArray();
    }

    public function exportCsv(): void
    {
        $reportData = $this->generateReport();
        
        $csv = "Program,Code,Income,Expenses,Profit,Margin %\n";
        foreach ($reportData as $row) {
            $csv .= sprintf(
                '%s,%s,%s,%s,%s,%.2f' . "\n",
                $row['program'],
                $row['code'],
                $row['income'],
                $row['expenses'],
                $row['profit'],
                $row['margin']
            );
        }

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
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Profit & Loss by Program</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Analyze profitability across programs</p>
        </div>
        
        <div class="flex gap-2">
            <button wire:click="exportCsv" 
                    class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export CSV
            </button>
            <a href="{{ route('reports.profit-loss.print', ['program_id' => $program_id, 'start_date' => $start_date, 'end_date' => $end_date]) }}"
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
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Program</label>
                <select wire:model.live="program_id"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="">All Programs</option>
                    @foreach ($programs as $program)
                        <option value="{{ $program->id }}">{{ $program->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start Date</label>
                <input type="date" 
                       wire:model.live="start_date"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">End Date</label>
                <input type="date" 
                       wire:model.live="end_date"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">&nbsp;</label>
                <button wire:click="$refresh" 
                        class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium">
                    Generate Report
                </button>
            </div>
        </div>
    </div>

    <!-- Report Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-8">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Program</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Code</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Income</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Expenses</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Profit/Loss</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Margin %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($reportData as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $row['program'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ $row['code'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 dark:text-green-400 font-medium">
                                {{ $baseCurrency->symbol }} {{ number_format($row['income'], 0) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 dark:text-red-400 font-medium">
                                {{ $baseCurrency->symbol }} {{ number_format($row['expenses'], 0) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold {{ $row['profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $baseCurrency->symbol }} {{ number_format($row['profit'], 0) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                                {{ number_format($row['margin'], 2) }}%
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400">No data available for the selected period.</p>
                            </td>
                        </tr>
                    @endforelse

                    @if (count($reportData) > 0)
                        <tr class="bg-gray-50 dark:bg-gray-900/50 font-bold border-t-2 border-gray-300 dark:border-gray-600">
                            <td colspan="2" class="px-6 py-4 text-sm text-gray-900 dark:text-white uppercase">Grand Total</td>
                            <td class="px-6 py-4 text-sm text-right text-green-600 dark:text-green-400">
                                {{ $baseCurrency->symbol }} {{ number_format($grandTotalIncome, 0) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-right text-red-600 dark:text-red-400">
                                {{ $baseCurrency->symbol }} {{ number_format($grandTotalExpenses, 0) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-right {{ $grandTotalProfit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $baseCurrency->symbol }} {{ number_format($grandTotalProfit, 0) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-right text-gray-900 dark:text-white">
                                {{ $grandTotalIncome > 0 ? number_format(($grandTotalProfit / $grandTotalIncome) * 100, 2) : 0 }}%
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Summary Cards -->
    @if (count($reportData) > 0)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border-l-4 border-green-500">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Total Income</p>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $baseCurrency->symbol }} {{ number_format($grandTotalIncome, 0) }}</p>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border-l-4 border-red-500">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Total Expenses</p>
                <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $baseCurrency->symbol }} {{ number_format($grandTotalExpenses, 0) }}</p>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border-l-4 {{ $grandTotalProfit >= 0 ? 'border-green-500' : 'border-red-500' }}">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Net Profit/Loss</p>
                <p class="text-3xl font-bold {{ $grandTotalProfit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $baseCurrency->symbol }} {{ number_format($grandTotalProfit, 0) }}
                </p>
            </div>
        </div>
    @endif
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
