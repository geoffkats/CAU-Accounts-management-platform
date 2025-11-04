<?php

use App\Models\Sale;
use App\Models\Program;
use App\Models\Customer;
use App\Models\Account;
use App\Models\Currency;
use App\Models\ExchangeRate;
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;

new class extends Component {
    public ?int $program_id = null;
    public ?int $customer_id = null;
    public ?int $account_id = null;
    public string $invoice_number = '';
    public string $sale_date = '';
    public $amount = 0;
    public string $currency = 'UGX';
    public $amount_paid = 0;
    public string $status = 'unpaid';
    public string $description = '';
    public ?float $convertedAmount = null;

    public function mount(): void
    {
        $this->sale_date = now()->format('Y-m-d');
        $this->invoice_number = 'INV-' . now()->format('Ymd') . '-' . rand(1000, 9999);
    }

    public function with(): array
    {
        return [
            'programs' => Program::where('status', '!=', 'cancelled')->orderBy('name')->get(),
            'customers' => Customer::orderBy('name')->get(),
            'incomeAccounts' => Account::where('type', 'income')->orderBy('code')->get(),
            'currencies' => Currency::getActive(),
            'baseCurrency' => Currency::getBaseCurrency(),
        ];
    }

    public function updated($property, $value): void
    {
        if ($property === 'amount_paid' || $property === 'amount') {
            $this->updatePaymentStatus();
        }
        
        if ($property === 'amount' || $property === 'currency') {
            $this->updateConversion();
        }
    }

    private function updatePaymentStatus(): void
    {
        if ($this->amount > 0 && $this->amount_paid >= $this->amount) {
            $this->status = 'paid';
        } elseif ($this->amount_paid > 0) {
            $this->status = 'partially_paid';
        } else {
            $this->status = 'unpaid';
        }
    }

    private function updateConversion(): void
    {
        if ($this->amount > 0 && $this->currency) {
            $baseCurrency = Currency::getBaseCurrency();
            if ($baseCurrency && $this->currency !== $baseCurrency->code) {
                $rate = ExchangeRate::getRate($this->currency, $baseCurrency->code);
                $this->convertedAmount = $rate ? $this->amount * $rate : null;
            } else {
                $this->convertedAmount = $this->amount;
            }
        } else {
            $this->convertedAmount = null;
        }
    }

    public function save(): void
    {
        // Cast select values to integers
        $this->program_id = $this->program_id ? (int)$this->program_id : null;
        $this->customer_id = $this->customer_id ? (int)$this->customer_id : null;
        $this->account_id = $this->account_id ? (int)$this->account_id : null;
        
        $validated = $this->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'customer_id' => ['required', 'exists:customers,id'],
            'account_id' => ['required', 'exists:accounts,id'],
            'invoice_number' => ['required', 'string', 'max:50', 'unique:sales,invoice_number'],
            'sale_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'exists:currencies,code'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        // Set initial status as unpaid
        $validated['status'] = 'unpaid';
        $validated['amount_paid'] = 0;

        $sale = Sale::create($validated);

        // If payment was made, create payment record
        if ($this->amount_paid > 0) {
            CustomerPayment::create([
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'payment_date' => $this->sale_date,
                'amount' => $this->amount_paid,
                'currency' => $this->currency,
                'payment_method' => 'cash', // default
                'notes' => 'Initial payment',
            ]);
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Invoice created successfully.'
        ]);

        $this->redirect(route('sales.index'));
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('sales.index') }}" 
           class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 bg-clip-text text-transparent">
                Create Customer Invoice
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Record a new invoice for customer</p>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-6">
            <h2 class="text-xl font-bold text-white">Invoice Details</h2>
            <p class="text-sm text-white/80 mt-1">Payments can be recorded after creating the invoice</p>
        </div>

        <form wire:submit="save" class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Invoice Number -->
                <div>
                    <label for="invoice_number" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Invoice Number <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="invoice_number"
                           wire:model="invoice_number"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white font-mono"
                           required>
                    @error('invoice_number')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Sale Date -->
                <div>
                    <label for="sale_date" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Sale Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" 
                           id="sale_date"
                           wire:model="sale_date"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white"
                           required>
                    @error('sale_date')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Program -->
                <div>
                    <label for="program_id" class="block text sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Program <span class="text-red-500">*</span>
                    </label>
                    <select id="program_id"
                            wire:model.defer="program_id"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white"
                            required>
                        <option value="">Select Program</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}">{{ $program->name }}</option>
                        @endforeach
                    </select>
                    @error('program_id')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Customer -->
                <div>
                    <label for="customer_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Customer <span class="text-red-500">*</span>
                    </label>
                    <select id="customer_id"
                            wire:model.defer="customer_id"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white"
                            required>
                        <option value="">Select Customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                    @error('customer_id')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        <a href="{{ route('customers.create') }}" class="text-green-600 hover:underline">+ Add new customer</a>
                    </p>
                </div>

                <!-- Income Account -->
                <div>
                    <label for="account_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Income Account <span class="text-red-500">*</span>
                    </label>
                    <select id="account_id"
                            wire:model.defer="account_id"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white"
                            required>
                        <option value="">Select Account</option>
                        @foreach($incomeAccounts as $account)
                            <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                        @endforeach
                    </select>
                    @error('account_id')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Currency -->
                <div>
                    <label for="currency" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Currency <span class="text-red-500">*</span>
                    </label>
                    <select id="currency"
                            wire:model.live="currency"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white"
                            required>
                        @foreach($currencies as $curr)
                            <option value="{{ $curr->code }}">{{ $curr->code }} - {{ $curr->name }} ({{ $curr->symbol }})</option>
                        @endforeach
                    </select>
                    @error('currency')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Amount -->
                <div>
                    <label for="amount" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Total Amount ({{ $currency }}) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           id="amount"
                           wire:model.live="amount"
                           step="0.01"
                           min="0"
                           placeholder="0.00"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white"
                           required>
                    @error('amount')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    @if($convertedAmount && $currency !== $baseCurrency->code)
                        <p class="mt-1 text-xs text-blue-600 dark:text-blue-400">
                            ≈ {{ $baseCurrency->symbol }} {{ number_format($convertedAmount, 0) }} (Base currency)
                        </p>
                    @endif
                </div>

                <!-- Amount Paid -->
                <div>
                    <label for="amount_paid" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Amount Paid ({{ $currency }}) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           id="amount_paid"
                           wire:model.live="amount_paid"
                           step="0.01"
                           min="0"
                           placeholder="0.00"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white"
                           required>
                    @error('amount_paid')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Outstanding: {{ $currency }} {{ number_format((float)($amount ?: 0) - (float)($amount_paid ?: 0), 2) }}
                    </p>
                </div>

                <!-- Status (Auto-calculated) -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Payment Status
                    </label>
                    <div class="px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold
                            {{ $status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                            {{ $status === 'partially_paid' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                            {{ $status === 'unpaid' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400' : '' }}">
                            {{ str_replace('_', ' ', ucfirst($status)) }}
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Auto-calculated based on amount paid</p>
                </div>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Description / Notes
                </label>
                <textarea id="description"
                          wire:model="description"
                          rows="4"
                          placeholder="Additional details about this sale..."
                          class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white resize-none"></textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="submit"
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl hover:shadow-xl transition-all duration-200 font-semibold">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Create Sale/Invoice
                </button>
                <a href="{{ route('sales.index') }}"
                   class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-200 font-semibold">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
