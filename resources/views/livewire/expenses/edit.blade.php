<?php

use App\Models\Expense;
use App\Models\Program;
use App\Models\Vendor;
use App\Models\Staff;
use App\Models\Account;
use App\Models\Currency;
use App\Models\ExchangeRate;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public int $expenseId;

    public ?int $program_id = null;
    public ?int $vendor_id = null;
    public ?int $staff_id = null;
    public ?int $account_id = null;
    public string $expense_date = '';
    public $amount = 0;
    public $charges = 0;
    public string $currency = 'UGX';
    public string $status = 'pending';
    public string $category = '';
    public ?string $description = null;
    public $receipt = null; // new uploaded file
    public ?string $existing_receipt_path = null; // show current
    public ?float $convertedAmount = null;
    public string $payee_type = 'vendor'; // 'vendor' or 'staff'

    public function mount(int $id): void
    {
        $this->expenseId = $id;
        $expense = Expense::findOrFail($id);

        $this->program_id = $expense->program_id;
        $this->vendor_id = $expense->vendor_id;
        $this->staff_id = $expense->staff_id;
        $this->account_id = $expense->account_id;
        $this->expense_date = optional($expense->expense_date)->format('Y-m-d') ?: now()->format('Y-m-d');
        $this->amount = $expense->amount;
        $this->charges = $expense->charges ?? 0;
        $this->currency = $expense->currency ?: 'UGX';
        $this->status = $expense->status ?: 'pending';
        $this->category = $expense->category ?: '';
        $this->description = $expense->description;
        $this->existing_receipt_path = $expense->receipt_path;

        $this->payee_type = $expense->vendor_id ? 'vendor' : ($expense->staff_id ? 'staff' : 'vendor');
        $this->updateConversion();
    }

    public function with(): array
    {
        return [
            'programs' => Program::where('status', '!=', 'cancelled')->orderBy('name')->get(),
            'vendors' => Vendor::orderBy('name')->get(),
            'staffMembers' => Staff::where('is_active', true)->orderBy('first_name')->get(),
            'expenseAccounts' => Account::where('type', 'expense')->orderBy('code')->get(),
            'currencies' => Currency::getActive(),
            'baseCurrency' => Currency::getBaseCurrency(),
            'expense' => Expense::with(['vendor','staff','program','account'])->findOrFail($this->expenseId),
        ];
    }

    public function updated($property, $value): void
    {
        if ($property === 'amount' || $property === 'currency' || $property === 'charges') {
            $this->updateConversion();
        }
        if ($property === 'payee_type') {
            // reset the non-selected payee to avoid validation conflicts
            if ($this->payee_type === 'vendor') {
                $this->staff_id = null;
            } else {
                $this->vendor_id = null;
            }
        }
    }

    private function updateConversion(): void
    {
        $amt = (float) ($this->amount ?: 0);
        $charges = (float) ($this->charges ?: 0);
        $total = $amt + $charges;
        if ($total > 0 && $this->currency) {
            $baseCurrency = Currency::getBaseCurrency();
            if ($baseCurrency && $this->currency !== $baseCurrency->code) {
                $rate = ExchangeRate::getRate($this->currency, $baseCurrency->code);
                $this->convertedAmount = $rate ? $total * $rate : null;
            } else {
                $this->convertedAmount = $total;
            }
        } else {
            $this->convertedAmount = null;
        }
    }

    public function updateExpense(): void
    {
        // Normalize ids
        $this->program_id = $this->program_id ? (int) $this->program_id : null;
        $this->vendor_id = $this->vendor_id ? (int) $this->vendor_id : null;
        $this->staff_id = $this->staff_id ? (int) $this->staff_id : null;
        $this->account_id = $this->account_id ? (int) $this->account_id : null;

        // Normalize numeric values - convert empty strings to 0 (charges column doesn't allow null)
        $this->amount = is_numeric($this->amount) && $this->amount !== '' ? $this->amount : 0;
        $this->charges = is_numeric($this->charges) && $this->charges !== '' ? $this->charges : 0;

        // Ensure only one payee id is set
        if ($this->payee_type === 'vendor') {
            $this->staff_id = null;
        } else {
            $this->vendor_id = null;
        }

        $validated = $this->validate([
            'program_id' => ['nullable', 'exists:programs,id'],
            'vendor_id' => ['nullable', 'required_if:payee_type,vendor', 'exists:vendors,id'],
            'staff_id' => ['nullable', 'required_if:payee_type,staff', 'exists:staff,id'],
            'account_id' => ['required', 'exists:accounts,id'],
            'expense_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'charges' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'exists:currencies,code'],
            'status' => ['required', 'in:pending,approved,rejected'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:1000'],
            'receipt' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf'],
        ]);

        $expense = Expense::findOrFail($this->expenseId);

        if ($this->receipt) {
            // Replace existing receipt
            if ($expense->receipt_path && \Storage::disk('public')->exists($expense->receipt_path)) {
                \Storage::disk('public')->delete($expense->receipt_path);
            }
            $validated['receipt_path'] = $this->receipt->store('receipts', 'public');
        }

        $expense->update($validated);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Expense updated successfully.'
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
                Edit Expense
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Update an existing business expense</p>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gradient-to-r from-red-500 to-pink-600 p-6">
            <h2 class="text-xl font-bold text-white">Expense Details</h2>
        </div>

        <form wire:submit="updateExpense" class="p-6 space-y-6">
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
                </div>

                <!-- Charges -->
                <div>
                    <label for="charges" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Charges ({{ $currency }})
                    </label>
                    <input type="number" 
                           id="charges"
                           wire:model.live="charges"
                           step="0.01"
                           min="0"
                           placeholder="0.00"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white">
                    @error('charges')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Optional additional charges (bank fees, processing fees, etc.)
                    </p>
                </div>

                <!-- Total (Computed) -->
                @php
                    $total = ((float)($amount ?: 0)) + ((float)($charges ?: 0));
                @endphp
                <div class="md:col-span-2">
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Total Expense Amount:</span>
                            <span class="text-xl font-bold text-blue-600 dark:text-blue-400">
                                {{ $currency }} {{ number_format($total, 2) }}
                            </span>
                        </div>
                        @if($convertedAmount && $currency !== $baseCurrency->code)
                            <p class="mt-2 text-xs text-blue-600 dark:text-blue-400 text-right">
                                ≈ {{ $baseCurrency->symbol }} {{ number_format($convertedAmount, 0) }} (Base currency)
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Program -->
                <div>
                    <label for="program_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Program
                    </label>
                    <select id="program_id"
                            wire:model.defer="program_id"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Select Program (Optional)</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}">{{ $program->name }}</option>
                        @endforeach
                    </select>
                    @error('program_id')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Payee Type -->
                <div>
                    <label for="payee_type" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Pay To <span class="text-red-500">*</span>
                    </label>
                    <select id="payee_type"
                            wire:model.live="payee_type"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white"
                            required>
                        <option value="vendor">Vendor</option>
                        <option value="staff">Staff Member</option>
                    </select>
                </div>

                <!-- Vendor Selection -->
                @if($payee_type === 'vendor')
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
                @endif

                <!-- Staff Selection -->
                @if($payee_type === 'staff')
                <div>
                    <label for="staff_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Staff Member <span class="text-red-500">*</span>
                    </label>
                    <select id="staff_id"
                            wire:model.defer="staff_id"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white"
                            required>
                        <option value="">Select Staff Member</option>
                        @foreach($staffMembers as $staff)
                            <option value="{{ $staff->id }}">{{ $staff->first_name }} {{ $staff->last_name }} ({{ $staff->employee_number }})</option>
                        @endforeach
                    </select>
                    @error('staff_id')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        <a href="{{ route('staff.create') }}" class="text-red-600 hover:underline">+ Add new staff</a>
                    </p>
                </div>
                @endif

                <!-- Expense Account -->
                <div class="md:col-span-2">
                    <label for="account_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Expense Account <span class="text-red-500">*</span>
                    </label>
                    <select id="account_id"
                            wire:model.defer="account_id"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white"
                            required>
                        <option value="">Select Account</option>

                        <optgroup label="🏢 5000 - Administrative Expenses">
                            @foreach($expenseAccounts->filter(fn($a) => $a->code >= 5000 && $a->code < 5100) as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="👥 5100 - Staff & Facilitator Costs">
                            @foreach($expenseAccounts->filter(fn($a) => $a->code >= 5100 && $a->code < 5200) as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="📚 5200 - Program Expenses">
                            @foreach($expenseAccounts->filter(fn($a) => $a->code >= 5200 && $a->code < 5300) as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="📢 5300 - Marketing & Outreach">
                            @foreach($expenseAccounts->filter(fn($a) => $a->code >= 5300 && $a->code < 5400) as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="🚗 5400 - Transport & Travel">
                            @foreach($expenseAccounts->filter(fn($a) => $a->code >= 5400 && $a->code < 5500) as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="💻 5500 - ICT & Technical Infrastructure">
                            @foreach($expenseAccounts->filter(fn($a) => $a->code >= 5500 && $a->code < 5600) as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="🎉 5600 - Events & Competitions">
                            @foreach($expenseAccounts->filter(fn($a) => $a->code >= 5600 && $a->code < 5700) as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="⚖️ 5700 - Professional Services">
                            @foreach($expenseAccounts->filter(fn($a) => $a->code >= 5700 && $a->code < 5800) as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="🏷️ 5800 - Asset & Depreciation">
                            @foreach($expenseAccounts->filter(fn($a) => $a->code >= 5800 && $a->code < 5900) as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="💰 5900 - Taxes & Statutory Payments">
                            @foreach($expenseAccounts->filter(fn($a) => $a->code >= 5900 && $a->code < 6000) as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="🤝 6000 - Miscellaneous">
                            @foreach($expenseAccounts->filter(fn($a) => $a->code >= 6000 && $a->code < 6100) as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                    @error('account_id')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Select the appropriate chart of accounts code for this expense
                    </p>
                </div>

                <!-- Category/Tags -->
                <div class="md:col-span-2">
                    <label for="category" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Additional Category / Tags
                        <span class="text-xs font-normal text-gray-500">(Optional)</span>
                    </label>
                    <input type="text" 
                           id="category"
                           wire:model.defer="category"
                           list="expense-categories"
                           placeholder="Add custom tags, cost center, or additional categorization..."
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white">
                    <datalist id="expense-categories">
                        <option value="Q1 Program">Quarter tags</option>
                        <option value="Q2 Program">Quarter tags</option>
                        <option value="Q3 Program">Quarter tags</option>
                        <option value="Q4 Program">Quarter tags</option>
                        <option value="Innovation Hub">Project tags</option>
                        <option value="Youth Training">Project tags</option>
                        <option value="Community Outreach">Project tags</option>
                        <option value="Research">Project tags</option>
                        <option value="Urgent">Priority tags</option>
                        <option value="Recurring">Priority tags</option>
                        <option value="One-time">Priority tags</option>
                        <option value="Grant-funded">Funding tags</option>
                        <option value="Self-funded">Funding tags</option>
                    </datalist>
                    @error('category')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

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
                    @if ($existing_receipt_path)
                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">Current: 
                            <a href="{{ \Storage::disk('public')->url($existing_receipt_path) }}" target="_blank" class="text-red-600 hover:underline">View</a>
                        </p>
                    @endif
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
                          wire:model.defer="description"
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
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-red-500 to-pink-600 text-white rounded-xl hover:shadow-xl transition-all duration-200 font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
                        wire:loading.attr="disabled">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg class="w-5 h-5 inline mr-2 animate-spin" fill="none" viewBox="0 0 24 24" wire:loading>
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove>Update Expense</span>
                    <span wire:loading>Processing...</span>
                </button>
                <a href="{{ route('expenses.index') }}"
                   class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-200 font-semibold">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <!-- Loading Overlay -->
    <div wire:loading wire:target="updateExpense" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 w-full max-w-sm">
            <div class="flex flex-col items-center gap-4">
                <svg class="w-16 h-16 text-red-500 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <div class="text-center">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Updating Expense...</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Please wait while we process your request</p>
                </div>
            </div>
        </div>
    </div>
</div>
