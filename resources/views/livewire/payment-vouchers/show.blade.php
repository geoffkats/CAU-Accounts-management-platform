<?php

use App\Models\Payment;
use Livewire\Volt\Component;

new class extends Component {
    public Payment $payment;

    public function mount(int $id): void
    {
        $this->payment = Payment::with(['expense.vendor', 'expense.staff', 'expense.program', 'paymentAccount'])
            ->findOrFail($id);
    }

    public function with(): array
    {
        return [
            'baseCurrency' => \App\Models\Currency::getBaseCurrency(),
        ];
    }

    public function deletePayment(): void
    {
        $voucher = $this->payment;
        $voucher->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Payment voucher deleted successfully.'
        ]);

        $this->redirect(route('payment-vouchers.index'));
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('payment-vouchers.index') }}" 
               class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Payment Voucher {{ $payment->voucher_number }}
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    {{ $payment->payment_date->format('F d, Y') }}
                </p>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="flex items-center gap-3">
            <a href="{{ route('payment-vouchers.edit', $payment->id) }}"
               wire:navigate
               class="px-5 py-3 bg-amber-500 hover:bg-amber-600 text-white rounded-xl transition-all duration-200 font-semibold inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                </svg>
                Edit
            </a>
            <a href="{{ route('payment-vouchers.pdf', $payment->id) }}"
               class="px-5 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-all duration-200 font-semibold inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Download PDF
            </a>
            <button type="button"
                    wire:click="deletePayment"
                    wire:confirm="Delete this payment voucher?"
                    class="px-5 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl transition-all duration-200 font-semibold inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3m-4 0h14" />
                </svg>
                Delete
            </button>
        </div>
    </div>

    <!-- Payment Details -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Payment Information -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Payment Information</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Amount:</span>
                    <span class="font-bold text-gray-900 dark:text-white text-lg">{{ $baseCurrency->symbol }} {{ number_format($payment->amount, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Payment Date:</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $payment->payment_date->format('M d, Y') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Payment Method:</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ ucfirst($payment->payment_method ?? 'N/A') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Payment Account:</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $payment->paymentAccount->name ?? 'N/A' }}</span>
                </div>
                @if($payment->payment_reference)
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Reference:</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $payment->payment_reference }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Expense Details -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Expense Details</h3>
            <div class="space-y-3 text-sm">
                <div>
                    <span class="text-gray-600 dark:text-gray-400">Pay To:</span>
                    <p class="font-semibold text-gray-900 dark:text-white">
                        {{ $payment->expense->vendor->name ?? $payment->expense->staff->first_name . ' ' . $payment->expense->staff->last_name ?? 'N/A' }}
                    </p>
                </div>
                <div>
                    <span class="text-gray-600 dark:text-gray-400">Description:</span>
                    <p class="font-semibold text-gray-900 dark:text-white">{{ $payment->expense->description }}</p>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Program:</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $payment->expense->program->name ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Category:</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ ucfirst($payment->expense->category ?? 'N/A') }}</span>
                </div>
            </div>
        </div>
    </div>

    @if($payment->notes)
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Notes</h3>
        <p class="text-gray-700 dark:text-gray-300">{{ $payment->notes }}</p>
    </div>
    @endif
</div>
