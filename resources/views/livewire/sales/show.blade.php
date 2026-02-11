<?php

use App\Models\Sale;
use App\Models\CustomerPayment;
use App\Models\Currency;
use App\Models\ExchangeRate;
use Livewire\Volt\Component;

new class extends Component {
    public Sale $sale;
    public bool $showPaymentModal = false;
    public string $payment_date = '';
    public float $payment_amount = 0;
    public string $payment_currency = 'UGX';
    public string $payment_method = 'cash';
    public string $payment_reference = '';
    public string $payment_notes = '';

    public function mount(int $id): void
    {
        $this->sale = Sale::with(['program', 'customer', 'account', 'payments'])->findOrFail($id);
        $this->payment_date = now()->format('Y-m-d');
        $this->payment_currency = $this->sale->currency;
        $this->payment_amount = $this->sale->remaining_balance;
    }

    public function openPaymentModal(): void
    {
        $this->showPaymentModal = true;
        $this->payment_amount = $this->sale->remaining_balance;
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
        $this->reset(['payment_date', 'payment_amount', 'payment_method', 'payment_reference', 'payment_notes']);
        $this->payment_date = now()->format('Y-m-d');
        $this->payment_currency = $this->sale->currency;
    }

    public function recordPayment(): void
    {
        $validated = $this->validate([
            'payment_date' => ['required', 'date'],
            'payment_amount' => ['required', 'numeric', 'min:0.01', 'max:' . $this->sale->remaining_balance],
            'payment_currency' => ['required', 'string', 'exists:currencies,code'],
            'payment_method' => ['required', 'string', 'in:cash,bank_transfer,mobile_money,check'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'payment_notes' => ['nullable', 'string', 'max:500'],
        ]);

        // Get exchange rate
        $baseCurrency = Currency::getBaseCurrency();
        $rate = 1.0;
        if ($this->payment_currency !== $baseCurrency->code) {
            $rate = ExchangeRate::getRate($this->payment_currency, $baseCurrency->code) ?? 1.0;
        }

        CustomerPayment::create([
            'sale_id' => $this->sale->id,
            'customer_id' => $this->sale->customer_id,
            'payment_date' => $this->payment_date,
            'amount' => $this->payment_amount,
            'currency' => $this->payment_currency,
            'exchange_rate' => $rate,
            'payment_method' => $this->payment_method,
            'reference_number' => $this->payment_reference,
            'notes' => $this->payment_notes,
        ]);

        $this->sale->refresh();
        $this->closePaymentModal();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Payment recorded successfully.'
        ]);
    }

    public function deletePayment(int $paymentId): void
    {
        $payment = CustomerPayment::findOrFail($paymentId);
        $payment->delete();

        $this->sale->refresh();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Payment deleted successfully.'
        ]);
    }

    public function with(): array
    {
        return [
            'baseCurrency' => Currency::getBaseCurrency(),
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('sales.index') }}" 
               class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 bg-clip-text text-transparent">
                    {{ \App\Models\Sale::getDocumentTypes()[$sale->document_type ?? \App\Models\Sale::DOC_INVOICE] ?? 'Invoice' }} {{ $sale->invoice_number }}
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $sale->customer->name }} • {{ $sale->sale_date->format('M d, Y') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('sales.edit', $sale->id) }}"
               wire:navigate
               class="px-5 py-3 bg-amber-500 hover:bg-amber-600 text-white rounded-xl transition-all duration-200 font-semibold inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                </svg>
                Edit
            </a>
            <a href="{{ route('sales.print', $sale->id) }}"
               target="_blank"
               class="px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-all duration-200 font-semibold inline-flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18h12v4H6v-4z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 14H4a2 2 0 01-2-2V9a2 2 0 012-2h16a2 2 0 012 2v3a2 2 0 01-2 2h-2" />
                </svg>
                Print
            </a>
            @if($sale->remaining_balance > 0 && $sale->postsToLedger())
                <button wire:click="openPaymentModal"
                        class="px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl hover:shadow-xl transition-all duration-200 font-semibold inline-flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Record Payment
                </button>
            @endif
        </div>
    </div>

    <!-- Status Banner -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                @php
                    $statusColors = [
                        'unpaid' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                        'partially_paid' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                        'paid' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                        'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300',
                    ];
                @endphp
                <span class="px-4 py-2 text-sm font-bold rounded-full {{ $statusColors[$sale->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ strtoupper(str_replace('_', ' ', $sale->status)) }}
                </span>
                <div class="h-8 w-px bg-gray-300 dark:bg-gray-600"></div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Document Amount</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $sale->currency }} {{ number_format($sale->amount, 0) }}</p>
                </div>
                <div class="h-8 w-px bg-gray-300 dark:bg-gray-600"></div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Paid Amount</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $sale->currency }} {{ number_format($sale->amount_paid, 0) }}</p>
                </div>
                <div class="h-8 w-px bg-gray-300 dark:bg-gray-600"></div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Balance Due</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $sale->currency }} {{ number_format($sale->remaining_balance, 0) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Invoice Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Payment History -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-6">
                    <h2 class="text-xl font-bold text-white">Payment History</h2>
                    <p class="text-sm text-white/80 mt-1">{{ $sale->payments->count() }} payment(s) recorded</p>
                </div>

                @if($sale->payments->count() > 0)
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($sale->payments as $payment)
                            <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-900 dark:text-white">{{ $payment->currency }} {{ number_format($payment->amount, 0) }}</p>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $payment->payment_date->format('M d, Y') }}</p>
                                            </div>
                                        </div>
                                        <div class="ml-14 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                            <p><span class="font-semibold">Method:</span> {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</p>
                                            @if($payment->reference_number)
                                                <p><span class="font-semibold">Reference:</span> {{ $payment->reference_number }}</p>
                                            @endif
                                            @if($payment->notes)
                                                <p><span class="font-semibold">Notes:</span> {{ $payment->notes }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <button wire:click="deletePayment({{ $payment->id }})"
                                            wire:confirm="Are you sure you want to delete this payment?"
                                            class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                            title="Delete Payment">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="mt-4 text-gray-600 dark:text-gray-400">No payments recorded yet</p>
                        <button wire:click="openPaymentModal"
                                class="mt-4 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                            Record First Payment
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- Invoice Info -->
        <div class="space-y-6">
            <!-- Customer Info -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Customer Details</h3>
                <div class="space-y-3 text-sm">
                    <div>
                        <p class="text-gray-600 dark:text-gray-400">Name</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $sale->customer->name }}</p>
                    </div>
                    @if($sale->customer->email)
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Email</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $sale->customer->email }}</p>
                        </div>
                    @endif
                    @if($sale->customer->phone)
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Phone</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $sale->customer->phone }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Document Info -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Document Information</h3>
                <div class="space-y-3 text-sm">
                    <div>
                        <p class="text-gray-600 dark:text-gray-400">Document Number</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $sale->invoice_number }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-400">Document Type</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ \App\Models\Sale::getDocumentTypes()[$sale->document_type ?? \App\Models\Sale::DOC_INVOICE] ?? 'Invoice' }}</p>
                    </div>
                    @if($sale->document_type === \App\Models\Sale::DOC_INVOICE)
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Due Date</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ optional($sale->due_date)->format('M d, Y') ?? 'N/A' }}</p>
                        </div>
                    @endif
                    @if(in_array($sale->document_type, [\App\Models\Sale::DOC_ESTIMATE, \App\Models\Sale::DOC_QUOTATION], true))
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Validity Date</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ optional($sale->validity_date)->format('M d, Y') ?? 'N/A' }}</p>
                        </div>
                    @endif
                    @if($sale->document_type === \App\Models\Sale::DOC_SALES_ORDER)
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Expected Delivery</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ optional($sale->delivery_date)->format('M d, Y') ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Order Status</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $sale->order_status ? ucfirst($sale->order_status) : 'N/A' }}</p>
                        </div>
                    @endif
                    @if($sale->document_type === \App\Models\Sale::DOC_TILL_SALE)
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Receipt Number</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $sale->receipt_number ?? 'N/A' }}</p>
                        </div>
                    @endif
                    <div>
                        <p class="text-gray-600 dark:text-gray-400">Program</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $sale->program->name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-400">Account</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $sale->account->name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 dark:text-gray-400">Product Area Code</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $sale->product_area_code ?? 'N/A' }}</p>
                    </div>
                    @if(in_array($sale->document_type, [\App\Models\Sale::DOC_INVOICE, \App\Models\Sale::DOC_ESTIMATE, \App\Models\Sale::DOC_QUOTATION, \App\Models\Sale::DOC_SALES_ORDER], true))
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Discount</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $sale->currency }} {{ number_format($sale->discount_amount ?? 0, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Tax</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $sale->currency }} {{ number_format($sale->tax_amount ?? 0, 2) }}</p>
                        </div>
                    @endif
                    @if($sale->description)
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Description</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $sale->description }}</p>
                        </div>
                    @endif
                    @if(in_array($sale->document_type, [\App\Models\Sale::DOC_INVOICE, \App\Models\Sale::DOC_ESTIMATE, \App\Models\Sale::DOC_QUOTATION, \App\Models\Sale::DOC_SALES_ORDER], true))
                        <div>
                            <p class="text-gray-600 dark:text-gray-400">Terms & Conditions</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $sale->terms_conditions ?: 'N/A' }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    @if($showPaymentModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" wire:click.self="closePaymentModal">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-md mx-4">
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-6 rounded-t-2xl">
                    <h3 class="text-xl font-bold text-white">Record Payment</h3>
                    <p class="text-sm text-white/80 mt-1">Balance due: {{ $sale->currency }} {{ number_format($sale->remaining_balance, 0) }}</p>
                </div>

                <form wire:submit="recordPayment" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Payment Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" 
                                   wire:model="payment_date"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                            @error('payment_date')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                Amount <span class="text-red-500">*</span>
                            </label>
                            <input type="number" 
                                   step="0.01"
                                   wire:model="payment_amount"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                            @error('payment_amount')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Payment Method <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="payment_method"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="check">Check</option>
                        </select>
                        @error('payment_method')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Reference Number
                        </label>
                        <input type="text" 
                               wire:model="payment_reference"
                               placeholder="Transaction ID, Check number, etc."
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                        @error('payment_reference')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Notes
                        </label>
                        <textarea wire:model="payment_notes" 
                                  rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white"></textarea>
                        @error('payment_notes')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-3 pt-4">
                        <button type="button"
                                wire:click="closePaymentModal"
                                class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors font-semibold">
                            Cancel
                        </button>
                        <button type="submit"
                                class="flex-1 px-4 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-lg hover:shadow-xl transition-all duration-200 font-semibold">
                            Record Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
