<?php

use App\Models\VendorInvoice;
use App\Models\Vendor;
use App\Models\Program;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Expense;
use Livewire\Volt\Component;

new class extends Component {
    public ?int $vendor_id = null;
    public ?int $program_id = null;
    public ?int $account_id = null;
    public string $invoice_date = '';
    public ?string $due_date = null;
    public float $amount = 0;
    public string $currency = 'UGX';
    public string $payment_terms = 'net_30';
    public string $category = '';
    public string $description = '';
    public string $vendor_reference = '';
    public string $notes = '';

    public function mount(): void
    {
        $this->invoice_date = now()->format('Y-m-d');
        $this->currency = Currency::getBaseCurrency()->code;
    }

    public function updatedPaymentTerms(): void
    {
        $this->calculateDueDate();
    }

    public function updatedInvoiceDate(): void
    {
        $this->calculateDueDate();
    }

    private function calculateDueDate(): void
    {
        if (!$this->invoice_date) return;

        $days = match($this->payment_terms) {
            'immediate' => 0,
            'net_7' => 7,
            'net_15' => 15,
            'net_30' => 30,
            'net_60' => 60,
            'net_90' => 90,
            default => 30,
        };

        $this->due_date = date('Y-m-d', strtotime($this->invoice_date . ' + ' . $days . ' days'));
    }

    public function save(): void
    {
        $validated = $this->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'program_id' => ['required', 'exists:programs,id'],
            'account_id' => ['required', 'exists:accounts,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'exists:currencies,code'],
            'payment_terms' => ['required', 'string', 'in:immediate,net_7,net_15,net_30,net_60,net_90'],
            'category' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'vendor_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        VendorInvoice::create($validated);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Vendor invoice created successfully.'
        ]);

        $this->redirect(route('vendor-invoices.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'vendors' => Vendor::active()->orderBy('name')->get(),
            'programs' => Program::orderBy('name')->get(),
            'accounts' => Account::orderBy('name')->get(),
            'currencies' => Currency::all(),
            'categories' => Expense::getCategories(),
        ];
    }
}; ?>

<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('vendor-invoices.index') }}" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 bg-clip-text text-transparent">Create Vendor Invoice</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Record a bill from a vendor</p>
        </div>
    </div>

    <form wire:submit="save" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-8 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Vendor <span class="text-red-500">*</span></label>
                <select wire:model="vendor_id" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Select vendor...</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                    @endforeach
                </select>
                @error('vendor_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Vendor Invoice # <span class="text-gray-500 text-xs">(Optional)</span></label>
                <input type="text" wire:model="vendor_reference" placeholder="Their invoice number" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                @error('vendor_reference')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Program <span class="text-red-500">*</span></label>
                <select wire:model="program_id" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Select program...</option>
                    @foreach($programs as $program)
                        <option value="{{ $program->id }}">{{ $program->name }}</option>
                    @endforeach
                </select>
                @error('program_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Account <span class="text-red-500">*</span></label>
                <select wire:model="account_id" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Select account...</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->code }})</option>
                    @endforeach
                </select>
                @error('account_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Invoice Date <span class="text-red-500">*</span></label>
                <input type="date" wire:model.live="invoice_date" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                @error('invoice_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Payment Terms <span class="text-red-500">*</span></label>
                <select wire:model.live="payment_terms" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                    <option value="immediate">Immediate</option>
                    <option value="net_7">Net 7 days</option>
                    <option value="net_15">Net 15 days</option>
                    <option value="net_30">Net 30 days</option>
                    <option value="net_60">Net 60 days</option>
                    <option value="net_90">Net 90 days</option>
                </select>
                @error('payment_terms')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Due Date</label>
                <input type="date" wire:model="due_date" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                @error('due_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Category</label>
                <select wire:model="category" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                    <option value="">Select category...</option>
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('category')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Amount <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" wire:model="amount" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                @error('amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Currency <span class="text-red-500">*</span></label>
                <select wire:model="currency" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                    @foreach($currencies as $curr)
                        <option value="{{ $curr->code }}">{{ $curr->code }} - {{ $curr->name }}</option>
                    @endforeach
                </select>
                @error('currency')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Description</label>
            <textarea wire:model="description" rows="3" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"></textarea>
            @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Notes</label>
            <textarea wire:model="notes" rows="2" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"></textarea>
            @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex items-center gap-4 pt-4">
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-xl hover:shadow-xl transition-all duration-200 font-semibold">
                Create Invoice
            </button>
            <a href="{{ route('vendor-invoices.index') }}" class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors font-semibold">
                Cancel
            </a>
        </div>
    </form>
</div>
