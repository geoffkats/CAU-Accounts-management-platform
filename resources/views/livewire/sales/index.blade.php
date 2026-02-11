<?php

use App\Models\Sale;
use App\Models\Program;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public ?int $programFilter = null;
    public string $documentTypeFilter = 'all';
    public string $periodType = 'month';
    public string $asOfDate = '';

    public function mount(): void
    {
        $this->asOfDate = now()->toDateString();
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

    public function with(): array
    {
        $range = $this->getPeriodRange();

        $query = Sale::with(['program', 'customer', 'account'])
            ->when($this->search, function ($q) {
                $q->where('invoice_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('customer', function ($q) {
                      $q->where('name', 'like', '%' . $this->search . '%');
                  });
            })
            ->when($this->statusFilter !== 'all', function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->when($this->programFilter, function ($q) {
                $q->where('program_id', $this->programFilter);
            })
            ->when($this->documentTypeFilter !== 'all', function ($q) {
                $q->where('document_type', $this->documentTypeFilter);
            })
            ->whereBetween('sale_date', [$range['start'], $range['end']])
            ->latest('sale_date');

        // Calculate totals - need to handle base currency conversion properly
        $allSales = Sale::when($this->search, function ($q) {
                $q->where('invoice_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('customer', function ($q) {
                      $q->where('name', 'like', '%' . $this->search . '%');
                  });
            })
            ->when($this->statusFilter !== 'all', function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->when($this->programFilter, function ($q) {
                $q->where('program_id', $this->programFilter);
            })
            ->when($this->documentTypeFilter !== 'all', function ($q) {
                $q->where('document_type', $this->documentTypeFilter);
            })
            ->whereBetween('sale_date', [$range['start'], $range['end']])
            ->get();
        
        $totalSales = $allSales->sum(function($sale) {
            return $sale->amount_base ?? $sale->amount;
        });
        
        $totalPaid = $allSales->sum(function($sale) {
            // Convert amount_paid to base currency if needed
            if ($sale->amount_base && $sale->amount && $sale->amount > 0) {
                // Calculate the ratio and apply to amount_paid
                return ($sale->amount_paid ?? 0) * ($sale->amount_base / $sale->amount);
            }
            return $sale->amount_paid ?? 0;
        });
        
        $totalUnpaid = $totalSales - $totalPaid;

        $agingBuckets = [
            '0-30' => 0.0,
            '31-60' => 0.0,
            '61-90' => 0.0,
            '91+' => 0.0,
        ];

        $asOf = Carbon::parse($range['end']);
        foreach ($allSales as $sale) {
            if (!$sale->postsToLedger()) {
                continue;
            }
            $remaining = (float) $sale->remaining_balance;
            if ($remaining <= 0) {
                continue;
            }
            $days = Carbon::parse($sale->sale_date)->diffInDays($asOf);
            $remainingBase = $remaining;
            if ($sale->amount_base && $sale->amount && $sale->amount > 0) {
                $remainingBase = $remaining * ($sale->amount_base / $sale->amount);
            }

            if ($days <= 30) {
                $agingBuckets['0-30'] += $remainingBase;
            } elseif ($days <= 60) {
                $agingBuckets['31-60'] += $remainingBase;
            } elseif ($days <= 90) {
                $agingBuckets['61-90'] += $remainingBase;
            } else {
                $agingBuckets['91+'] += $remainingBase;
            }
        }

        return [
            'sales' => $query->paginate(15),
            'programs' => Program::orderBy('name')->get(),
            'totalSales' => $totalSales,
            'totalPaid' => $totalPaid,
            'totalUnpaid' => $totalUnpaid,
            'baseCurrency' => Currency::getBaseCurrency(),
            'periodStart' => $range['start'],
            'periodEnd' => $range['end'],
            'periodLabel' => $range['label'],
            'documentTypes' => Sale::getDocumentTypes(),
            'agingBuckets' => $agingBuckets,
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedProgramFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDocumentTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPeriodType(): void
    {
        $this->resetPage();
    }

    public function updatedAsOfDate(): void
    {
        $this->resetPage();
    }

    public function markAsPaid(int $id): void
    {
        $sale = Sale::findOrFail($id);
        $sale->markAsPaid();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Sale marked as paid.'
        ]);
    }

    public function deleteSale(int $id): void
    {
        $sale = Sale::findOrFail($id);
        $sale->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Sale deleted successfully.'
        ]);
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 bg-clip-text text-transparent">
                Sales Ledger
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Track sales documents and receipts</p>
        </div>
        <a href="{{ route('sales.create') }}" 
           class="px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl hover:shadow-xl transition-all duration-200 font-semibold">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            New Sales Document
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 p-6 rounded-xl border-2 border-blue-200 dark:border-blue-800 shadow-lg">
            <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Total Sales</div>
            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $baseCurrency->symbol }} {{ number_format($totalSales, 0) }}</div>
        </div>
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 p-6 rounded-xl border-2 border-green-200 dark:border-green-800 shadow-lg">
            <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Total Collected</div>
            <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $baseCurrency->symbol }} {{ number_format($totalPaid, 0) }}</div>
        </div>
        <div class="bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 p-6 rounded-xl border-2 border-orange-200 dark:border-orange-800 shadow-lg">
            <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Outstanding</div>
            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ $baseCurrency->symbol }} {{ number_format($totalUnpaid, 0) }}</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
                <input type="text" 
                       wire:model.live.debounce.300ms="search"
                       placeholder="Invoice # or customer..."
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                <select wire:model.live="statusFilter"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                    <option value="all">All Status</option>
                    <option value="unpaid">Unpaid</option>
                    <option value="partially_paid">Partially Paid</option>
                    <option value="paid">Paid</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Program</label>
                <select wire:model.live="programFilter"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                    <option value="">All Programs</option>
                    @foreach($programs as $program)
                        <option value="{{ $program->id }}">{{ $program->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Document Type</label>
                <select wire:model.live="documentTypeFilter"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                    <option value="all">All Types</option>
                    @foreach($documentTypes as $type => $label)
                        <option value="{{ $type }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Period</label>
                <select wire:model.live="periodType"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                    <option value="month">Monthly</option>
                    <option value="quarter">Quarterly</option>
                    <option value="year">Yearly</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">As Of</label>
                <input type="date"
                       wire:model.live="asOfDate"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
            </div>
            <div class="flex items-end">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <div class="font-medium text-gray-900 dark:text-white">{{ $periodLabel }} range</div>
                    <div>{{ $periodStart }} to {{ $periodEnd }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Aging Analysis -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Receivables Aging Analysis</h2>
            <div class="text-sm text-gray-600 dark:text-gray-400">As of {{ $periodEnd }}</div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($agingBuckets as $label => $amount)
                <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ $label }} days</div>
                    <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $baseCurrency->symbol }} {{ number_format($amount, 0) }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Sales Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Document</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Date</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Customer</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Product Area</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Amount</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Status</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($sales as $sale)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors duration-150 cursor-pointer" 
                        onclick="window.Livewire.navigate('{{ route('sales.show', $sale->id) }}')">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $documentTypes[$sale->document_type ?? \App\Models\Sale::DOC_INVOICE] ?? 'Invoice' }}
                            </div>
                            <div class="font-mono text-xs text-green-600 dark:text-green-400">
                                {{ $sale->invoice_number }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            {{ $sale->sale_date->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $sale->customer->name }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $sale->program->name }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $sale->product_area_code ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ $sale->currency ?? $baseCurrency->code }} {{ number_format($sale->amount, 0) }}
                            </div>
                            @if($sale->remaining_balance > 0)
                                <div class="text-xs text-red-600 dark:text-red-400">
                                    Balance: {{ number_format($sale->remaining_balance, 0) }}
                                </div>
                            @else
                                <div class="text-xs text-gray-400">
                                    Fully paid
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold
                                {{ $sale->status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                {{ $sale->status === 'partially_paid' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                {{ $sale->status === 'unpaid' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}">
                                {{ str_replace('_', ' ', ucfirst($sale->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right" onclick="event.stopPropagation()">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('sales.show', $sale->id) }}"
                                   wire:navigate
                                   class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                                   title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                <a href="{{ route('sales.edit', $sale->id) }}"
                                   wire:navigate
                                   class="p-1.5 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-lg transition-colors"
                                   title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                                    </svg>
                                </a>
                                <a href="{{ route('sales.print', $sale->id) }}"
                                   target="_blank"
                                   class="p-1.5 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors"
                                   title="Print">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18h12v4H6v-4z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 14H4a2 2 0 01-2-2V9a2 2 0 012-2h16a2 2 0 012 2v3a2 2 0 01-2 2h-2" />
                                    </svg>
                                </a>
                                @if($sale->remaining_balance > 0)
                                    <a href="{{ route('sales.show', $sale->id) }}"
                                       wire:navigate
                                       class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-semibold transition-colors inline-flex items-center gap-1"
                                       title="Record Payment">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Pay
                                    </a>
                                @endif
                                <button wire:click="deleteSale({{ $sale->id }})"
                                        wire:confirm="Are you sure you want to delete this sale?"
                                        class="p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                        title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400 font-medium mb-4">No sales found</p>
                            <a href="{{ route('sales.create') }}" 
                               class="inline-flex items-center px-6 py-2 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-lg hover:shadow-lg transition-all duration-200 text-sm font-medium">
                                Create First Sale
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sales->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $sales->links() }}
        </div>
        @endif
    </div>
</div>
