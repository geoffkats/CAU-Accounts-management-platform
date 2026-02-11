<?php

use App\Models\Sale;
use App\Models\Program;
use App\Models\Customer;
use App\Models\Account;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\CustomerPayment;
use Livewire\Volt\Component;

new class extends Component {
    public Sale $sale;

    public ?int $program_id = null;
    public ?int $customer_id = null;
    public ?int $account_id = null;
    public string $document_type = 'invoice';
    public ?string $product_area_code = null;
    public string $invoice_number = '';
    public string $sale_date = '';
    public ?string $due_date = null;
    public ?string $validity_date = null;
    public ?string $delivery_date = null;
    public ?string $order_status = null;
    public $amount = 0;
    public string $currency = 'UGX';
    public $discount_amount = 0;
    public $tax_amount = 0;
    public string $description = '';
    public ?string $terms_conditions = null;
    public ?string $receipt_number = null;
    public ?float $convertedAmount = null;

    public string $payment_method = 'cash';
    public ?int $payment_account_id = null;

    public function mount(int $id): void
    {
        $this->sale = Sale::with(['payments'])->findOrFail($id);

        $this->program_id = $this->sale->program_id;
        $this->customer_id = $this->sale->customer_id;
        $this->account_id = $this->sale->account_id;
        $this->document_type = $this->sale->document_type ?? Sale::DOC_INVOICE;
        $this->product_area_code = $this->sale->product_area_code;
        $this->invoice_number = $this->sale->invoice_number;
        $this->sale_date = optional($this->sale->sale_date)->format('Y-m-d') ?: now()->format('Y-m-d');
        $this->due_date = optional($this->sale->due_date)->format('Y-m-d');
        $this->validity_date = optional($this->sale->validity_date)->format('Y-m-d');
        $this->delivery_date = optional($this->sale->delivery_date)->format('Y-m-d');
        $this->order_status = $this->sale->order_status;
        $this->amount = $this->sale->amount;
        $this->currency = $this->sale->currency ?: 'UGX';
        $this->discount_amount = $this->sale->discount_amount ?? 0;
        $this->tax_amount = $this->sale->tax_amount ?? 0;
        $this->description = $this->sale->description ?? '';
        $this->terms_conditions = $this->sale->terms_conditions;
        $this->receipt_number = $this->sale->receipt_number;

        $this->updateConversion();
    }

    public function with(): array
    {
        return [
            'programs' => Program::where('status', '!=', 'cancelled')->orderBy('name')->get(),
            'customers' => Customer::orderBy('name')->get(),
            'incomeAccounts' => Account::where('type', 'income')
                ->where('is_active', true)
                ->orderBy('code')
                ->get(),
            'paymentAccounts' => Account::where('type', 'asset')
                ->where('is_active', true)
                ->orderBy('code')
                ->get(),
            'currencies' => Currency::getActive(),
            'baseCurrency' => Currency::getBaseCurrency(),
            'documentTypes' => Sale::getDocumentTypes(),
        ];
    }

    public function updated($property, $value): void
    {
        if ($property === 'document_type') {
            $this->resetDocumentSpecificFields();
            if ($this->isAutoDocumentNumber($this->invoice_number)) {
                $this->invoice_number = $this->generateDocumentNumber($this->document_type);
            }
        }

        if ($property === 'amount' || $property === 'currency') {
            $this->updateConversion();
        }
    }

    private function resetDocumentSpecificFields(): void
    {
        $this->due_date = null;
        $this->validity_date = null;
        $this->delivery_date = null;
        $this->order_status = null;
        $this->receipt_number = null;
        $this->discount_amount = 0;
        $this->tax_amount = 0;
        $this->terms_conditions = null;

        if ($this->document_type === Sale::DOC_INVOICE) {
            $this->validity_date = null;
            $this->delivery_date = null;
            $this->order_status = null;
            $this->receipt_number = null;
        } elseif (in_array($this->document_type, [Sale::DOC_ESTIMATE, Sale::DOC_QUOTATION], true)) {
            $this->due_date = null;
            $this->delivery_date = null;
            $this->order_status = null;
            $this->receipt_number = null;
        } elseif ($this->document_type === Sale::DOC_SALES_ORDER) {
            $this->due_date = null;
            $this->validity_date = null;
            $this->receipt_number = null;
        } elseif ($this->document_type === Sale::DOC_TILL_SALE) {
            $this->due_date = null;
            $this->validity_date = null;
            $this->delivery_date = null;
            $this->order_status = null;
            $this->discount_amount = 0;
            $this->tax_amount = 0;
            $this->terms_conditions = null;
        }
    }

    private function applyDocumentFieldDefaults(array $validated): array
    {
        if ($validated['document_type'] === Sale::DOC_INVOICE) {
            $validated['validity_date'] = null;
            $validated['delivery_date'] = null;
            $validated['order_status'] = null;
            $validated['receipt_number'] = null;
        } elseif (in_array($validated['document_type'], [Sale::DOC_ESTIMATE, Sale::DOC_QUOTATION], true)) {
            $validated['due_date'] = null;
            $validated['delivery_date'] = null;
            $validated['order_status'] = null;
            $validated['receipt_number'] = null;
        } elseif ($validated['document_type'] === Sale::DOC_SALES_ORDER) {
            $validated['due_date'] = null;
            $validated['validity_date'] = null;
            $validated['receipt_number'] = null;
        } elseif ($validated['document_type'] === Sale::DOC_TILL_SALE) {
            $validated['due_date'] = null;
            $validated['validity_date'] = null;
            $validated['delivery_date'] = null;
            $validated['order_status'] = null;
            $validated['discount_amount'] = null;
            $validated['tax_amount'] = null;
            $validated['terms_conditions'] = null;
        }

        return $validated;
    }

    private function generateDocumentNumber(string $documentType): string
    {
        $prefix = $this->getDocumentPrefix($documentType);
        return $prefix . '-' . now()->format('Ymd') . '-' . rand(1000, 9999);
    }

    private function getDocumentPrefix(string $documentType): string
    {
        return match ($documentType) {
            Sale::DOC_ESTIMATE => 'EST',
            Sale::DOC_QUOTATION => 'QUO',
            Sale::DOC_SALES_ORDER => 'SO',
            Sale::DOC_TILL_SALE => 'TILL',
            default => 'INV',
        };
    }

    private function isAutoDocumentNumber(?string $value): bool
    {
        if (!$value) {
            return false;
        }

        return (bool) preg_match('/^(INV|EST|QUO|SO|TILL)-\d{8}-\d{4}$/', $value);
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

    public function updateSale(): void
    {
        $this->program_id = $this->program_id ? (int)$this->program_id : null;
        $this->customer_id = $this->customer_id ? (int)$this->customer_id : null;
        $this->account_id = $this->account_id ? (int)$this->account_id : null;
        $this->payment_account_id = $this->payment_account_id ? (int)$this->payment_account_id : null;

        $validated = $this->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'customer_id' => ['required', 'exists:customers,id'],
            'account_id' => ['required', 'exists:accounts,id'],
            'document_type' => ['required', 'string', 'in:invoice,estimate,quotation,sales_order,till_sale'],
            'product_area_code' => ['nullable', 'string', 'max:50'],
            'invoice_number' => ['required', 'string', 'max:50', 'unique:sales,invoice_number,' . $this->sale->id],
            'sale_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'validity_date' => ['nullable', 'date'],
            'delivery_date' => ['nullable', 'date'],
            'order_status' => ['nullable', 'string', 'in:pending,approved,delivered,cancelled'],
            'amount' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'exists:currencies,code'],
            'description' => ['nullable', 'string', 'max:1000'],
            'terms_conditions' => ['nullable', 'string', 'max:2000'],
            'receipt_number' => ['nullable', 'string', 'max:50'],
        ]);

        $validated = $this->applyDocumentFieldDefaults($validated);

        if (in_array($this->document_type, [Sale::DOC_ESTIMATE, Sale::DOC_QUOTATION, Sale::DOC_SALES_ORDER], true)) {
            if ($this->sale->payments->count() > 0) {
                $this->addError('document_type', 'Cannot change to a non-posting document while payments exist.');
                return;
            }
            $validated['status'] = Sale::STATUS_UNPAID;
            $validated['amount_paid'] = 0;
        }

        if ($this->document_type === Sale::DOC_TILL_SALE && $this->sale->payments->count() === 0) {
            $this->validate([
                'payment_method' => ['required', 'string', 'in:cash,bank_transfer,mobile_money,check'],
                'payment_account_id' => ['required', 'exists:accounts,id'],
            ]);
        }

        $this->sale->update($validated);

        if ($this->document_type === Sale::DOC_TILL_SALE && $this->sale->payments->count() === 0) {
            $baseCurrency = Currency::getBaseCurrency();
            $rate = 1.0;
            if ($this->currency !== $baseCurrency->code) {
                $rate = ExchangeRate::getRate($this->currency, $baseCurrency->code) ?? 1.0;
            }

            CustomerPayment::create([
                'sale_id' => $this->sale->id,
                'customer_id' => $this->sale->customer_id,
                'payment_date' => $this->sale_date,
                'amount' => $this->amount,
                'currency' => $this->currency,
                'exchange_rate' => $rate,
                'payment_method' => $this->payment_method,
                'payment_account_id' => $this->payment_account_id,
                'reference_number' => null,
                'notes' => 'Till sale payment recorded at point of sale',
            ]);
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Sale updated successfully.'
        ]);

        $this->redirect(route('sales.show', $this->sale->id));
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('sales.show', $sale->id) }}" 
           class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 bg-clip-text text-transparent">
                Edit Sale
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Update invoice or sales document details</p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-6">
            <h2 class="text-xl font-bold text-white">Document Details</h2>
        </div>

        <form wire:submit="updateSale" class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="document_type" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Document Type <span class="text-red-500">*</span>
                    </label>
                    <select id="document_type"
                            wire:model.live="document_type"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white"
                            required>
                        @foreach($documentTypes as $type => $label)
                            <option value="{{ $type }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('document_type')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="invoice_number" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Document Number <span class="text-red-500">*</span>
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

                @if($document_type === \App\Models\Sale::DOC_INVOICE)
                    <div>
                        <label for="due_date" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Due Date
                        </label>
                        <input type="date"
                               id="due_date"
                               wire:model="due_date"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                        @error('due_date')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                @if(in_array($document_type, [\App\Models\Sale::DOC_ESTIMATE, \App\Models\Sale::DOC_QUOTATION], true))
                    <div>
                        <label for="validity_date" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Validity Date
                        </label>
                        <input type="date"
                               id="validity_date"
                               wire:model="validity_date"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                        @error('validity_date')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                @if($document_type === \App\Models\Sale::DOC_SALES_ORDER)
                    <div>
                        <label for="delivery_date" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Expected Delivery Date
                        </label>
                        <input type="date"
                               id="delivery_date"
                               wire:model="delivery_date"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                        @error('delivery_date')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="order_status" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Order Status
                        </label>
                        <select id="order_status"
                                wire:model="order_status"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Select Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        @error('order_status')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                @if($document_type === \App\Models\Sale::DOC_TILL_SALE)
                    <div>
                        <label for="receipt_number" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Receipt Number
                        </label>
                        <input type="text"
                               id="receipt_number"
                               wire:model="receipt_number"
                               placeholder="POS receipt number"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                        @error('receipt_number')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <div>
                    <label for="program_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
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
                </div>

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

                <div>
                    <label for="product_area_code" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Product Area Code
                    </label>
                    <input type="text"
                           id="product_area_code"
                           wire:model.defer="product_area_code"
                           placeholder="Department or category code"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                    @error('product_area_code')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

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

                @if(in_array($document_type, [\App\Models\Sale::DOC_INVOICE, \App\Models\Sale::DOC_ESTIMATE, \App\Models\Sale::DOC_QUOTATION, \App\Models\Sale::DOC_SALES_ORDER], true))
                    <div>
                        <label for="discount_amount" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Discount Amount ({{ $currency }})
                        </label>
                        <input type="number"
                               id="discount_amount"
                               wire:model.defer="discount_amount"
                               step="0.01"
                               min="0"
                               placeholder="0.00"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                        @error('discount_amount')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="tax_amount" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Tax Amount ({{ $currency }})
                        </label>
                        <input type="number"
                               id="tax_amount"
                               wire:model.defer="tax_amount"
                               step="0.01"
                               min="0"
                               placeholder="0.00"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                        @error('tax_amount')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                @if($document_type === \App\Models\Sale::DOC_TILL_SALE && $sale->payments->count() === 0)
                    <div>
                        <label for="payment_method" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Payment Method <span class="text-red-500">*</span>
                        </label>
                        <select id="payment_method"
                                wire:model="payment_method"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
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
                        <label for="payment_account_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Payment Account <span class="text-red-500">*</span>
                        </label>
                        <select id="payment_account_id"
                                wire:model="payment_account_id"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Select Account</option>
                            @foreach($paymentAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                            @endforeach
                        </select>
                        @error('payment_account_id')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </div>

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

            @if(in_array($document_type, [\App\Models\Sale::DOC_INVOICE, \App\Models\Sale::DOC_ESTIMATE, \App\Models\Sale::DOC_QUOTATION, \App\Models\Sale::DOC_SALES_ORDER], true))
                <div>
                    <label for="terms_conditions" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Terms & Conditions
                    </label>
                    <textarea id="terms_conditions"
                              wire:model="terms_conditions"
                              rows="3"
                              placeholder="Payment terms, delivery notes, or validity notes"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white resize-none"></textarea>
                    @error('terms_conditions')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <div class="flex gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="submit"
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl hover:shadow-xl transition-all duration-200 font-semibold">
                    Update Sale
                </button>
                <a href="{{ route('sales.show', $sale->id) }}"
                   class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-200 font-semibold">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
