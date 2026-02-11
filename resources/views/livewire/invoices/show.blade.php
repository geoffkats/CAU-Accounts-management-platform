<?php

use App\Models\StudentInvoice;
use Livewire\Volt\Component;

new class extends Component {
    public $invoiceId;
    public $invoice;

    public function mount($id)
    {
        $this->invoiceId = $id;
        $this->invoice = StudentInvoice::with(['student.program', 'items', 'payments.receivedBy', 'paymentAllocations'])
            ->findOrFail($id);
    }
}; ?>

<x-layouts.app :title="'Invoice ' . $invoice->invoice_number">
    <div class="max-w-5xl mx-auto space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Invoice {{ $invoice->invoice_number }}</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $invoice->student->full_name }}</p>
            </div>
            <div class="flex items-center space-x-2">
                @if($invoice->balance > 0)
                    <a href="{{ route('payments.create') }}?invoice_id={{ $invoice->id }}" 
                       class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        Record Payment
                    </a>
                @endif
                @if($invoice->status === 'draft')
                    <a href="{{ route('invoices.edit', $invoice->id) }}" 
                       class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Edit
                    </a>
                @endif
            </div>
        </div>

        <!-- Invoice Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8">
            <!-- Status Badge -->
            <div class="flex items-center justify-between mb-6">
                <span class="px-4 py-2 text-sm font-semibold rounded-full 
                    bg-{{ $invoice->status_color }}-100 
                    text-{{ $invoice->status_color }}-800 
                    dark:bg-{{ $invoice->status_color }}-900 
                    dark:text-{{ $invoice->status_color }}-200">
                    {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                </span>
                <div class="text-right">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Invoice Date</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $invoice->invoice_date->format('M d, Y') }}</p>
                </div>
            </div>

            <!-- Student & Program Info -->
            <div class="grid grid-cols-2 gap-6 mb-8 pb-6 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Bill To</h3>
                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $invoice->student->full_name }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $invoice->student->student_id }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $invoice->student->email }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $invoice->student->phone }}</p>
                </div>
                <div class="text-right">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Details</h3>
                    <p class="text-sm text-gray-900 dark:text-gray-100">{{ $invoice->student->program->name }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $invoice->term }} {{ $invoice->academic_year }}</p>
                    @if($invoice->due_date)
                        <p class="text-sm text-gray-600 dark:text-gray-400">Due: {{ $invoice->due_date->format('M d, Y') }}</p>
                    @endif
                </div>
            </div>

            <!-- Line Items -->
            <table class="w-full mb-6">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Description</th>
                        <th class="py-3 text-right text-sm font-medium text-gray-500 dark:text-gray-400">Amount</th>
                        <th class="py-3 text-right text-sm font-medium text-gray-500 dark:text-gray-400">Qty</th>
                        <th class="py-3 text-right text-sm font-medium text-gray-500 dark:text-gray-400">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="py-3 text-gray-900 dark:text-gray-100">{{ $item->description }}</td>
                            <td class="py-3 text-right text-gray-900 dark:text-gray-100">{{ number_format($item->amount, 2) }}</td>
                            <td class="py-3 text-right text-gray-900 dark:text-gray-100">{{ $item->quantity }}</td>
                            <td class="py-3 text-right text-gray-900 dark:text-gray-100">{{ number_format($item->total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Totals -->
            <div class="space-y-2">
                <div class="flex items-center justify-between text-gray-600 dark:text-gray-400">
                    <span>Subtotal:</span>
                    <span>{{ number_format($invoice->total_amount, 2) }} {{ $invoice->currency }}</span>
                </div>
                @if($invoice->discount_amount > 0)
                    <div class="flex items-center justify-between text-gray-600 dark:text-gray-400">
                        <span>Discount:</span>
                        <span>-{{ number_format($invoice->discount_amount, 2) }} {{ $invoice->currency }}</span>
                    </div>
                @endif
                @if($invoice->paid_amount > 0)
                    <div class="flex items-center justify-between text-green-600 dark:text-green-400">
                        <span>Paid:</span>
                        <span>-{{ number_format($invoice->paid_amount, 2) }} {{ $invoice->currency }}</span>
                    </div>
                @endif
                <div class="flex items-center justify-between text-xl font-bold text-gray-900 dark:text-gray-100 pt-2 border-t border-gray-200 dark:border-gray-700">
                    <span>Balance Due:</span>
                    <span>{{ number_format($invoice->balance, 2) }} {{ $invoice->currency }}</span>
                </div>
            </div>

            @if($invoice->notes)
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $invoice->notes }}</p>
                </div>
            @endif
        </div>

        <!-- Payment History -->
        @if($invoice->payments->count() > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Payment History</h2>
                <div class="space-y-3">
                    @foreach($invoice->payments as $payment)
                        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $payment->payment_number }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $payment->payment_date->format('M d, Y') }} • {{ $payment->payment_method_label }}
                                    @if($payment->receivedBy)
                                        • by {{ $payment->receivedBy->name }}
                                    @endif
                                </p>
                            </div>
                            <span class="text-lg font-semibold text-green-600 dark:text-green-400">
                                {{ number_format($payment->amount, 2) }} {{ $payment->currency }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
