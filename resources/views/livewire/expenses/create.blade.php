<?php

use App\Models\Expense;
use App\Models\Program;
use App\Models\Vendor;
use App\Models\Account;
use App\Models\Currency;
use App\Models\ExchangeRate;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public ?int $program_id = null;
    public ?int $vendor_id = null;
    public ?int $account_id = null;
    public string $expense_date = '';
    // Note: avoid strict typing for Livewire-bound numeric props to prevent hydration issues
    public $amount = 0;
    public string $currency = 'UGX';
    public string $status = 'pending';
    public string $payment_status = 'unpaid';
    public string $payment_reference = '';
    public string $category = '';
    public string $description = '';
    public $receipt = null;
    public ?float $convertedAmount = null;

    public function mount(): void
    {
        $this->expense_date = now()->format('Y-m-d');
    }

    public function with(): array
    {
        return [
            'programs' => Program::where('status', '!=', 'cancelled')->orderBy('name')->get(),
            'vendors' => Vendor::orderBy('name')->get(),
            'expenseAccounts' => Account::where('type', 'expense')->orderBy('code')->get(),
            'currencies' => Currency::getActive(),
            'baseCurrency' => Currency::getBaseCurrency(),
        ];
    }

    public function updated($property, $value): void
    {
        if ($property === 'amount' || $property === 'currency') {
            $this->updateConversion();
        }
    }

    private function updateConversion(): void
    {
        $amt = (float) ($this->amount ?: 0);
        if ($amt > 0 && $this->currency) {
            $baseCurrency = Currency::getBaseCurrency();
            if ($baseCurrency && $this->currency !== $baseCurrency->code) {
                $rate = ExchangeRate::getRate($this->currency, $baseCurrency->code);
                $this->convertedAmount = $rate ? $amt * $rate : null;
            } else {
                $this->convertedAmount = $amt;
            }
        } else {
            $this->convertedAmount = null;
        }
    }

    public function save(): void
    {
        // Cast select values to integers prior to validation (Livewire sends strings)
        $this->program_id = $this->program_id ? (int) $this->program_id : null;
        $this->vendor_id = $this->vendor_id ? (int) $this->vendor_id : null;
        $this->account_id = $this->account_id ? (int) $this->account_id : null;

        $validated = $this->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'vendor_id' => ['required', 'exists:vendors,id'],
            'account_id' => ['required', 'exists:accounts,id'],
            'expense_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'exists:currencies,code'],
            'status' => ['required', 'in:pending,paid'],
            'payment_status' => ['required', 'in:unpaid,paid,partial'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:1000'],
            'receipt' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf'],
        ]);

        if ($this->receipt) {
            $validated['receipt_path'] = $this->receipt->store('receipts', 'public');
        }

        // Set payment_date if creating as paid
        if ($this->payment_status === 'paid') {
            $validated['payment_date'] = now();
        }

        Expense::create($validated);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Expense created successfully.'
        ]);

        $this->redirect(route('expenses.index'));
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('expenses.index') }}" 
           class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-red-600 via-pink-600 to-rose-600 bg-clip-text text-transparent">
                Create Expense
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Record a new business expense</p>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gradient-to-r from-red-500 to-pink-600 p-6">
            <h2 class="text-xl font-bold text-white">Expense Details</h2>
        </div>

        <form wire:submit="save" class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Expense Date -->
                <div>
                    <label for="expense_date" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Expense Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" 
                           id="expense_date"
                           wire:model="expense_date"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white"
                           required>
                    @error('expense_date')
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
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white"
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
                        Amount ({{ $currency }}) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           id="amount"
                           wire:model.live="amount"
                           step="0.01"
                           min="0"
                           placeholder="0.00"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white"
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

                <!-- Program -->
                <div>
                    <label for="program_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Program <span class="text-red-500">*</span>
                    </label>
                        <select id="program_id"
                            wire:model.defer="program_id"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white"
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

                <!-- Vendor -->
                <div>
                    <label for="vendor_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Vendor <span class="text-red-500">*</span>
                    </label>
                        <select id="vendor_id"
                            wire:model.defer="vendor_id"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white"
                            required>
                        <option value="">Select Vendor</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                        @endforeach
                    </select>
                    @error('vendor_id')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        <a href="{{ route('vendors.create') }}" class="text-red-600 hover:underline">+ Add new vendor</a>
                    </p>
                </div>

                <!-- Expense Account -->
                <div>
                    <label for="account_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Expense Account <span class="text-red-500">*</span>
                    </label>
                        <select id="account_id"
                            wire:model.defer="account_id"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white"
                            required>
                        <option value="">Select Account</option>
                        @foreach($expenseAccounts as $account)
                            <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                        @endforeach
                    </select>
                    @error('account_id')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Category -->
                <div>
                    <label for="category" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Category
                    </label>
                    <input type="text" 
                           id="category"
                           wire:model="category"
                           placeholder="e.g., Office Supplies, Travel"
                           list="categories"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white">
                    <datalist id="categories">
                        <option value="Office Supplies">
                        <option value="Travel">
                        <option value="Utilities">
                        <option value="Marketing">
                        <option value="Professional Fees">
                        <option value="Training">
                        <option value="Equipment">
                        <option value="Rent">
                    </datalist>
                    @error('category')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Payment Status -->
                <div>
                    <label for="payment_status" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Payment Status <span class="text-red-500">*</span>
                    </label>
                    <select id="payment_status"
                            wire:model="payment_status"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white"
                            required>
                        <option value="unpaid">Unpaid (Accounts Payable)</option>
                        <option value="paid">Paid</option>
                        <option value="partial">Partially Paid</option>
                    </select>
                    @error('payment_status')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Select 'Unpaid' to record expense for later payment
                    </p>
                </div>

                <!-- Payment Reference (conditional) -->
                @if($payment_status === 'paid' || $payment_status === 'partial')
                <div>
                    <label for="payment_reference" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Payment Reference
                    </label>
                    <input type="text" 
                           id="payment_reference"
                           wire:model="payment_reference"
                           placeholder="Check #, Transaction ID, etc."
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white">
                    @error('payment_reference')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Optional: Check number or payment transaction reference
                    </p>
                </div>
                @endif

                <!-- Receipt Upload -->
                <div>
                    <label for="receipt" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Receipt / Attachment
                    </label>
                    <input type="file" 
                           id="receipt"
                           wire:model="receipt"
                           accept=".jpg,.jpeg,.png,.pdf"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100">
                    @error('receipt')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Max size: 5MB. Formats: JPG, PNG, PDF
                    </p>
                    @if ($receipt)
                        <div class="mt-2 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                            <p class="text-sm text-green-700 dark:text-green-400">✓ File ready: {{ $receipt->getClientOriginalName() }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Description <span class="text-red-500">*</span>
                </label>
                <textarea id="description"
                          wire:model="description"
                          rows="4"
                          placeholder="Describe the expense..."
                          class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white resize-none"
                          required></textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="submit"
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-red-500 to-pink-600 text-white rounded-xl hover:shadow-xl transition-all duration-200 font-semibold"
                        wire:loading.attr="disabled">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span wire:loading.remove>Create Expense</span>
                    <span wire:loading>Uploading...</span>
                </button>
                <a href="{{ route('expenses.index') }}"
                   class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-200 font-semibold">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
