<?php

use App\Models\StudentPayment;
use Livewire\Volt\Component;

new class extends Component {
    public $paymentId;
    public $payment;

    public function mount($id)
    {
        $this->paymentId = $id;
        $this->payment = StudentPayment::with(['student.program', 'invoice', 'receivedBy'])
            ->findOrFail($id);
    }
}; ?>

<x-layouts.app :title="'Payment ' . $payment->payment_number">
    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Payment {{ $payment->payment_number }}</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Payment receipt</p>
            </div>
            <button onclick="window.print()" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                Print Receipt
            </button>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Payment Receipt</h2>
                <p class="text-gray-600 dark:text-gray-400 mt-2">{{ $payment->payment_number }}</p>
            </div>

            <div class="grid grid-cols-2 gap-8 mb-8 pb-8 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Student Information</h3>
                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $payment->student->full_name }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $payment->student->student_id }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $payment->student->program->name }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $payment->student->email }}</p>
                </div>

                <div class="text-right">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Payment Details</h3>
                    <p class="text-sm text-gray-900 dark:text-gray-100">Date: {{ $payment->payment_date->format('M d, Y') }}</p>
                    <p class="text-sm text-gray-900 dark:text-gray-100">Method: {{ $payment->payment_method_label }}</p>
                    @if($payment->reference_number)
                        <p class="text-sm text-gray-900 dark:text-gray-100">Ref: {{ $payment->reference_number }}</p>
                    @endif
                    @if($payment->receivedBy)
                        <p class="text-sm text-gray-900 dark:text-gray-100">Received by: {{ $payment->receivedBy->name }}</p>
                    @endif
                </div>
            </div>

            @if($payment->invoice)
                <div class="mb-8">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Applied To</h3>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $payment->invoice->invoice_number }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $payment->invoice->term }} {{ $payment->invoice->academic_year }}</p>
                            </div>
                            <a href="{{ route('invoices.show', $payment->invoice->id) }}" 
                               class="text-blue-600 hover:text-blue-800 text-sm">
                                View Invoice
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <div class="flex items-center justify-between text-3xl font-bold text-gray-900 dark:text-gray-100">
                    <span>Amount Paid:</span>
                    <span class="text-green-600 dark:text-green-400">{{ number_format($payment->amount, 2) }} {{ $payment->currency }}</span>
                </div>
            </div>

            @if($payment->notes)
                <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Notes</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $payment->notes }}</p>
                </div>
            @endif

            <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700 text-center text-sm text-gray-500 dark:text-gray-400">
                <p>This is an official payment receipt from Code Academy Uganda</p>
                <p class="mt-1">Generated on {{ now()->format('M d, Y H:i') }}</p>
            </div>
        </div>
    </div>
</x-layouts.app>
