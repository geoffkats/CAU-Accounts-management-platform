<?php

use App\Models\Expense;
use App\Models\Program;
use App\Models\Currency;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public ?int $program_id = null;
    public string $start_date = '';
    public string $end_date = '';
    public string $groupBy = 'category';

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
            'totalExpenses' => collect($reportData)->sum('amount'),
            'baseCurrency' => Currency::getBaseCurrency(),
        ];
    }

    private function generateReport(): array
    {
        $query = Expense::with(['program', 'vendor'])
            ->whereBetween('expense_date', [$this->start_date, $this->end_date])
            ->when($this->program_id, fn($q) => $q->where('program_id', $this->program_id));

        if ($this->groupBy === 'category') {
            return $query->select('category', DB::raw('COUNT(*) as count'), DB::raw('SUM(COALESCE(amount_base, amount)) as amount'))
                ->groupBy('category')
                ->orderByDesc('amount')
                ->get()
                ->map(fn($item) => [
                    'name' => $item->category ?: 'Uncategorized',
                    'count' => $item->count,
                    'amount' => $item->amount,
                ])
                ->toArray();
        } elseif ($this->groupBy === 'program') {
            return $query->select('program_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(COALESCE(amount_base, amount)) as amount'))
                ->groupBy('program_id')
                ->orderByDesc('amount')
                ->get()
                ->map(fn($item) => [
                    'name' => Program::find($item->program_id)?->name ?? 'N/A',
                    'count' => $item->count,
                    'amount' => $item->amount,
                ])
                ->toArray();
        } else { // vendor
            return $query->select('vendor_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as amount'))
                ->groupBy('vendor_id')
                ->orderByDesc('amount')
                ->get()
                ->map(fn($item) => [
                    'name' => $item->vendor?->name ?? 'No Vendor',
                    'count' => $item->count,
                    'amount' => $item->amount,
                ])
                ->toArray();
        }
    }

    public function exportCsv(): void
    {
        $reportData = $this->generateReport();
        
        $csv = ucfirst($this->groupBy) . ",Count,Amount\n";
        foreach ($reportData as $row) {
            $csv .= sprintf('%s,%d,%s' . "\n", $row['name'], $row['count'], $row['amount']);
        }

        $this->dispatch('download-csv', [
            'filename' => 'expense-breakdown-' . now()->format('Y-m-d') . '.csv',
            'content' => $csv
        ]);
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Expense Breakdown</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Analyze expenses by category, program, or vendor</p>
        </div>
        <div class="flex gap-2">
            <button type="button" 
                    wire:click="exportCsv" 
                    class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export CSV
            </button>
            <a href="{{ route('reports.expense-breakdown.print', ['groupBy' => $groupBy, 'program_id' => $program_id, 'start_date' => $start_date, 'end_date' => $end_date]) }}"
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
    <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 p-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Group By</label>
                <select wire:model.live="groupBy" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="category">Category</option>
                    <option value="program">Program</option>
                    <option value="vendor">Vendor</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Program</label>
                <select wire:model.live="program_id" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">All Programs</option>
                    @foreach ($programs as $program)
                        <option value="{{ $program->id }}">{{ $program->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                <input type="date" wire:model.live="start_date" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                <input type="date" wire:model.live="end_date" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
            </div>

            <div class="flex items-end">
                <button type="button" wire:click="$refresh" class="w-full inline-flex items-center justify-center px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                    Generate Report
                </button>
            </div>
        </div>
    </div>

    <!-- Report -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 p-4">
        <div class="mb-4">
            <p class="text-sm text-gray-600 dark:text-gray-300">Period: {{ \Carbon\Carbon::parse($start_date)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($end_date)->format('M d, Y') }}</p>
        </div>
        @php $total = max($totalExpenses, 1); @endphp
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ ucfirst($groupBy) }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Count</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Percentage</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($reportData as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $row['name'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-100">{{ $row['count'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-red-600">{{ $baseCurrency->symbol }} {{ number_format($row['amount'], 0) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="w-24 bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                        <div class="bg-red-500 h-2 rounded-full" style="width: {{ ($row['amount'] / $total) * 100 }}%"></div>
                                    </div>
                                    <span class="text-sm">{{ number_format(($row['amount'] / $total) * 100, 1) }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">No expenses found for the selected period.</td>
                        </tr>
                    @endforelse

                    @if (count($reportData) > 0)
                        <tr class="bg-gray-50 dark:bg-gray-700 font-bold">
                            <td class="px-6 py-4">TOTAL</td>
                            <td class="px-6 py-4 text-right">{{ collect($reportData)->sum('count') }}</td>
                            <td class="px-6 py-4 text-right text-red-600">{{ $baseCurrency->symbol }} {{ number_format($totalExpenses, 0) }}</td>
                            <td class="px-6 py-4 text-right">100%</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Visual Breakdown -->
    @if (count($reportData) > 0)
        <div class="mt-6 bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Visual Breakdown</h2>
            <div class="space-y-4">
                @foreach ($reportData as $row)
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $row['name'] }}</span>
                            <span class="text-sm text-gray-600 dark:text-gray-300">
                                {{ $baseCurrency->symbol }} {{ number_format($row['amount'], 0) }} 
                                ({{ number_format(($row['amount'] / $total) * 100, 1) }}%)
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                            <div class="bg-red-600 h-4 rounded-full flex items-center justify-end px-2" 
                                 style="width: {{ ($row['amount'] / $total) * 100 }}%">
                                <span class="text-xs text-white font-medium">{{ $row['count'] }} items</span>
                            </div>
                        </div>
                    </div>
                @endforeach
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
