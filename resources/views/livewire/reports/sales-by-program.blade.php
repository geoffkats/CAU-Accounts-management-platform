<?php

use App\Models\Sale;
use App\Models\Program;
use App\Models\Currency;
use Livewire\Volt\Component;

new class extends Component {
    public string $dateFrom = '';
    public string $dateTo = '';
    public ?int $program_id = null;

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function with(): array
    {
        $query = Sale::with(['program', 'customer'])
            ->when($this->dateFrom, function ($q) {
                $q->whereDate('sale_date', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                $q->whereDate('sale_date', '<=', $this->dateTo);
            })
            ->when($this->program_id, function ($q) {
                $q->where('program_id', $this->program_id);
            });

        $sales = $query->get();

        // Group sales by program
        $salesByProgram = $sales->groupBy('program.name')->map(function ($programSales, $programName) {
            $totalSales = $programSales->sum(function($sale) {
                return $sale->amount_base ?? $sale->amount;
            });
            $totalPaid = $programSales->sum('amount_paid');
            $salesCount = $programSales->count();
            
            return [
                'program_name' => $programName,
                'sales_count' => $salesCount,
                'total_sales' => $totalSales,
                'total_paid' => $totalPaid,
                'outstanding' => $totalSales - $totalPaid,
                'sales' => $programSales->sortByDesc('sale_date')
            ];
        })->sortByDesc('total_sales');

        // Summary stats
        $totalSales = $sales->sum(function($sale) {
            return $sale->amount_base ?? $sale->amount;
        });
        $totalPaid = $sales->sum('amount_paid');
        $totalOutstanding = $totalSales - $totalPaid;

        return [
            'programs' => Program::where('status', '!=', 'cancelled')->orderBy('name')->get(),
            'salesByProgram' => $salesByProgram,
            'totalSales' => $totalSales,
            'totalPaid' => $totalPaid,
            'totalOutstanding' => $totalOutstanding,
            'salesCount' => $sales->count(),
            'baseCurrency' => Currency::getBaseCurrency(),
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600 bg-clip-text text-transparent">
            Sales by Program Report
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Analyze sales performance across different programs</p>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="dateFrom" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    From Date
                </label>
                <input type="date" 
                       id="dateFrom"
                       wire:model.live="dateFrom"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
            </div>
            
            <div>
                <label for="dateTo" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    To Date
                </label>
                <input type="date" 
                       id="dateTo"
                       wire:model.live="dateTo"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
            </div>
            
            <div>
                <label for="program_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Program Filter
                </label>
                <select id="program_id"
                        wire:model.live="program_id"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="">All Programs</option>
                    @foreach($programs as $program)
                        <option value="{{ $program->id }}">{{ $program->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="flex items-end">
                <button wire:click="$refresh" 
                        class="w-full px-4 py-2 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-lg hover:shadow-lg transition-all duration-200 font-semibold">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white">
            <div class="flex items-center">
                <div class="p-3 bg-white/20 rounded-lg">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-blue-100 text-sm font-medium">Total Sales</p>
                    <p class="text-2xl font-bold">{{ number_format($salesCount) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white">
            <div class="flex items-center">
                <div class="p-3 bg-white/20 rounded-lg">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-green-100 text-sm font-medium">Total Revenue</p>
                    <p class="text-2xl font-bold">{{ $baseCurrency->symbol }} {{ number_format($totalSales, 0) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white">
            <div class="flex items-center">
                <div class="p-3 bg-white/20 rounded-lg">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-purple-100 text-sm font-medium">Amount Paid</p>
                    <p class="text-2xl font-bold">{{ $baseCurrency->symbol }} {{ number_format($totalPaid, 0) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-6 text-white">
            <div class="flex items-center">
                <div class="p-3 bg-white/20 rounded-lg">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-orange-100 text-sm font-medium">Outstanding</p>
                    <p class="text-2xl font-bold">{{ $baseCurrency->symbol }} {{ number_format($totalOutstanding, 0) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales by Program -->
    @if($salesByProgram->count() > 0)
        <div class="space-y-6">
            @foreach($salesByProgram as $programData)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <!-- Program Header -->
                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 p-6 text-white">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-xl font-bold">{{ $programData['program_name'] }}</h3>
                                <p class="text-blue-100 mt-1">{{ $programData['sales_count'] }} {{ Str::plural('sale', $programData['sales_count']) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold">{{ $baseCurrency->symbol }} {{ number_format($programData['total_sales'], 0) }}</p>
                                <p class="text-blue-100 text-sm">Total Revenue</p>
                            </div>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="mt-4">
                            <div class="flex justify-between text-sm text-blue-100 mb-1">
                                <span>Payment Progress</span>
                                <span>{{ $programData['total_sales'] > 0 ? round(($programData['total_paid'] / $programData['total_sales']) * 100, 1) : 0 }}%</span>
                            </div>
                            <div class="w-full bg-blue-400/30 rounded-full h-2">
                                <div class="bg-white h-2 rounded-full transition-all duration-300" 
                                     style="width: {{ $programData['total_sales'] > 0 ? ($programData['total_paid'] / $programData['total_sales']) * 100 : 0 }}%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Sales List -->
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Invoice</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Customer</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Paid</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($programData['sales'] as $sale)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $sale->invoice_number }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {{ $sale->customer->name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {{ $sale->sale_date->format('M d, Y') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {{ $sale->currency ?? $baseCurrency->code }} {{ number_format($sale->amount, 0) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {{ $sale->currency ?? $baseCurrency->code }} {{ number_format($sale->amount_paid, 0) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                    {{ $sale->status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                                    {{ $sale->status === 'partial' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                                    {{ $sale->status === 'pending' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400' : '' }}">
                                                    {{ ucfirst($sale->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-12 text-center">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">No Sales Data Found</h3>
            <p class="text-gray-600 dark:text-gray-400">Try adjusting your date range or program filter to see sales data.</p>
        </div>
    @endif
</div>