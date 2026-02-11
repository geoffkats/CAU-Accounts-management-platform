<?php

use Livewire\Volt\Component;
use App\Models\Payment;
use Livewire\WithPagination;
use Carbon\Carbon;

new class extends Component {
    use WithPagination;
    
    public $search = '';
    public string $periodType = 'month';
    public string $asOfDate = '';

    public function mount(): void
    {
        $this->asOfDate = now()->toDateString();
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'periodType', 'asOfDate'], true)) {
            $this->resetPage();
        }
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
    
    public function with()
    {
        $range = $this->getPeriodRange();

        $query = Payment::with(['expense.vendor', 'expense.staff', 'expense.program', 'paymentAccount'])
            ->when($this->search, function($q) {
                $q->where(function ($q) {
                    $q->where('voucher_number', 'like', '%' . $this->search . '%')
                      ->orWhere('payment_reference', 'like', '%' . $this->search . '%')
                      ->orWhereHas('expense', function($q) {
                          $q->where('description', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->whereBetween('payment_date', [$range['start'], $range['end']]);

        $totalAmount = (clone $query)->sum('amount');
        $query = $query->latest('payment_date');
        
        return [
            'payments' => $query->paginate(20),
            'totalAmount' => $totalAmount,
            'periodStart' => $range['start'],
            'periodEnd' => $range['end'],
            'periodLabel' => $range['label'],
        ];
    }

    public function deletePayment(int $id): void
    {
        $payment = Payment::findOrFail($id);
        $payment->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Payment voucher deleted successfully.'
        ]);
    }
}; ?>

<div>
    <x-slot name="title">Payment Vouchers</x-slot>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Payment Vouchers</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">All payment vouchers in one place</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-4 mb-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Period</label>
                    <select wire:model.live="periodType"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-700 rounded-lg dark:bg-zinc-900 dark:text-white">
                        <option value="month">Monthly</option>
                        <option value="quarter">Quarterly</option>
                        <option value="year">Yearly</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">As Of</label>
                    <input type="date"
                           wire:model.live="asOfDate"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-700 rounded-lg dark:bg-zinc-900 dark:text-white">
                </div>
                <div class="flex items-end">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <div class="font-medium text-gray-900 dark:text-white">{{ $periodLabel }} total</div>
                        <div>{{ number_format($totalAmount, 2) }}</div>
                        <div class="text-xs">{{ $periodStart }} to {{ $periodEnd }}</div>
                    </div>
                </div>
            </div>

            <input type="text" 
                   wire:model.live.debounce.300ms="search"
                   placeholder="Search by voucher number, reference, or description..."
                   class="w-full px-4 py-2 border border-gray-300 dark:border-zinc-700 rounded-lg dark:bg-zinc-900 dark:text-white">
        </div>

        <!-- Vouchers Table -->
        <div class="bg-white dark:bg-zinc-800 shadow rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                    <thead class="bg-gray-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Voucher #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Paid To</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Expense</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Payment Account</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Method</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amount</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                        @forelse($payments as $payment)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-900/50">
                            <td class="px-6 py-4 text-sm font-semibold text-blue-600 dark:text-blue-400">
                                {{ $payment->voucher_number }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                {{ $payment->payment_date->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                {{ $payment->expense->vendor->name ?? $payment->expense->staff->first_name . ' ' . $payment->expense->staff->last_name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white max-w-xs truncate">
                                {{ $payment->expense->description }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                {{ $payment->paymentAccount->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white capitalize">
                                {{ $payment->payment_method ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-right font-medium text-gray-900 dark:text-white">
                                {{ number_format($payment->amount, 2) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('payment-vouchers.show', $payment->id) }}" 
                                       wire:navigate
                                       class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                       title="View">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('payment-vouchers.edit', $payment->id) }}"
                                       wire:navigate
                                       class="text-amber-600 hover:text-amber-900 dark:text-amber-400 dark:hover:text-amber-300"
                                       title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('payment-vouchers.pdf', $payment->id) }}" 
                                       class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                       title="Download PDF">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </a>
                                    <button type="button"
                                            wire:click="deletePayment({{ $payment->id }})"
                                            wire:confirm="Delete this payment voucher?"
                                            class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200"
                                            title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3m-4 0h14" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                No payment vouchers found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-200 dark:border-zinc-700">
                {{ $payments->links() }}
            </div>
        </div>
    </div>
</div>
