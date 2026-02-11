<?php

use Livewire\Volt\Component;
use App\Models\Expense;
use App\Models\Payment;

new class extends Component {
    public $expense;
    public $payments;
    
    public function mount($id)
    {
        $this->expense = Expense::with(['program', 'vendor', 'staff', 'account', 'paymentAccount', 'payments.paymentAccount'])
            ->findOrFail($id);
        $this->payments = $this->expense->payments()->with('paymentAccount')->latest()->get();
    }
    
    public function with()
    {
        return [
            'totalPaid' => $this->expense->total_paid,
            'outstandingBalance' => $this->expense->outstanding_balance,
        ];
    }
}; ?>

<div>
    <x-slot name="title">Expense Details</x-slot>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Expense Details</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Reference: #{{ $expense->id }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('expenses.edit', $expense->id) }}" 
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-zinc-800 dark:text-gray-300 dark:border-zinc-700 dark:hover:bg-zinc-700"
                   wire:navigate>
                    Edit Expense
                </a>
                @if($expense->outstanding_balance > 0)
                    <a href="{{ route('expenses.payment-voucher', $expense->id) }}" 
                       class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700"
                       wire:navigate>
                        Create Payment Voucher
                    </a>
                @endif
                <a href="{{ route('expenses.index') }}" 
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-zinc-800 dark:text-gray-300 dark:border-zinc-700 dark:hover:bg-zinc-700"
                   wire:navigate>
                    Back to List
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Expense Information -->
                <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Expense Information</h2>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $expense->description }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Date</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $expense->expense_date->format('M d, Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Program</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $expense->program->name ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Category</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white capitalize">{{ $expense->category ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Vendor</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $expense->vendor->name ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Staff</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $expense->staff ? $expense->staff->first_name . ' ' . $expense->staff->last_name : 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Expense Account</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $expense->account->name ?? 'N/A' }} ({{ $expense->account->code ?? '' }})</dd>
                        </div>
                        @if($expense->notes)
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Notes</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $expense->notes }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>

                <!-- Payment History -->
                <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Payment History</h2>
                    @if($payments->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Voucher #</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Account</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Method</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Reference</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                                    @foreach($payments as $payment)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-semibold text-blue-600 dark:text-blue-400">
                                            <a href="{{ route('payment-vouchers.pdf', $payment->id) }}" class="hover:underline" title="Download PDF">
                                                {{ $payment->voucher_number }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $payment->payment_date->format('M d, Y') }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $payment->paymentAccount->name ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white capitalize">{{ $payment->payment_method ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $payment->payment_reference ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-right font-medium">{{ number_format($payment->amount, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">No payments recorded yet.</p>
                    @endif
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Payment Status -->
                <div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Payment Status</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Status</span>
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                @if($expense->payment_status === 'paid') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @elseif($expense->payment_status === 'partial') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                @endif">
                                {{ ucfirst($expense->payment_status) }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center pt-3 border-t border-gray-200 dark:border-zinc-700">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Total Amount</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($expense->amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Total Paid</span>
                            <span class="text-sm font-medium text-green-600 dark:text-green-400">{{ number_format($totalPaid, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-3 border-b border-gray-200 dark:border-zinc-700">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Outstanding</span>
                            <span class="text-sm font-medium text-red-600 dark:text-red-400">{{ number_format($outstandingBalance, 2) }}</span>
                        </div>
                        @if($expense->charges > 0)
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Charges</span>
                            <span class="text-sm text-gray-900 dark:text-white">{{ number_format($expense->charges, 2) }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Quick Actions -->
                @if($expense->outstanding_balance > 0)
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-200 mb-2">Outstanding Balance</h4>
                    <p class="text-xs text-blue-700 dark:text-blue-300 mb-3">This expense has an outstanding balance of {{ number_format($outstandingBalance, 2) }}</p>
                    <a href="{{ route('expenses.payment-voucher', $expense->id) }}" 
                       class="block w-full text-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700"
                       wire:navigate>
                        Create Payment Voucher
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
