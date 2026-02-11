<?php

use Livewire\Volt\Component;
use App\Models\Payment;
use App\Models\Account;

new class extends Component {
    public int $paymentId;
    public ?Payment $payment = null;
    public $expense;

    public string $payment_date = '';
    public ?int $payment_account_id = null;
    public $amount = 0;
    public ?string $payment_method = null;
    public ?string $payment_reference = null;
    public ?string $notes = null;
    public float $originalAmount = 0.0;

    public function mount(int $id): void
    {
        $this->paymentId = $id;
        $this->payment = Payment::with(['expense.vendor', 'expense.staff', 'expense.program', 'paymentAccount'])->findOrFail($id);
        $this->expense = $this->payment->expense;

        $this->payment_date = optional($this->payment->payment_date)->format('Y-m-d') ?: now()->format('Y-m-d');
        $this->payment_account_id = $this->payment->payment_account_id;
        $this->amount = $this->payment->amount;
        $this->originalAmount = (float) $this->payment->amount;
        $this->payment_method = $this->payment->payment_method;
        $this->payment_reference = $this->payment->payment_reference;
        $this->notes = $this->payment->notes;
    }

    public function with(): array
    {
        $accountsByType = Account::where('is_active', true)
            ->orderBy('type')
            ->orderBy('code')
            ->get()
            ->groupBy('type');

        return [
            'accountsByType' => $accountsByType,
        ];
    }

    public function updatePayment(): void
    {
        $this->payment_account_id = $this->payment_account_id ? (int) $this->payment_account_id : null;
        $this->amount = is_numeric($this->amount) && $this->amount !== '' ? $this->amount : 0;

        $maxAmount = $this->expense
            ? (float) $this->expense->outstanding_balance + $this->originalAmount
            : (float) $this->amount;

        $validated = $this->validate([
            'payment_date' => ['required', 'date'],
            'payment_account_id' => ['required', 'exists:accounts,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:' . $maxAmount],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ], [
            'amount.max' => 'Payment cannot exceed the outstanding balance of ' . number_format($maxAmount, 2),
        ]);

        $payment = Payment::findOrFail($this->paymentId);
        $payment->update($validated);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Payment voucher updated successfully.'
        ]);

        $this->redirect(route('payment-vouchers.show', $payment->id));
    }
}; ?>

<div class="max-w-4xl mx-auto p-6 space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('payment-vouchers.show', $paymentId) }}"
           class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Edit Payment Voucher</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $payment->voucher_number ?? '' }}</p>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-800 shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Voucher Details</h2>
            @if($expense)
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    For Expense: {{ $expense->description }}
                    (Outstanding: {{ number_format($expense->outstanding_balance, 2) }})
                </p>
            @endif
        </div>

        <form wire:submit="updatePayment" class="p-6 space-y-6">
            @if($expense)
                <div class="bg-gray-50 dark:bg-zinc-900 p-4 rounded-lg">
                    <h3 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Expense Details</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Amount:</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ number_format($expense->amount, 2) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Total Paid:</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ number_format($expense->total_paid, 2) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Outstanding:</span>
                            <span class="font-medium text-red-600">{{ number_format($expense->outstanding_balance, 2) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Status:</span>
                            <span class="font-medium capitalize text-gray-900 dark:text-white">{{ $expense->payment_status }}</span>
                        </div>
                    </div>
                </div>
            @endif

            <div>
                <label for="payment_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Payment Date *
                </label>
                <input type="date"
                       id="payment_date"
                       wire:model="payment_date"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-zinc-900 dark:text-white">
                @error('payment_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="payment_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Payment Account (Debit From) *
                </label>
                <select id="payment_account_id"
                        wire:model="payment_account_id"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-zinc-900 dark:text-white">
                    <option value="">-- Select Account --</option>
                    @forelse($accountsByType as $type => $accounts)
                        <optgroup label="{{ ucfirst($type) }} Accounts">
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">
                                    {{ $account->name }} ({{ $account->code }})
                                </option>
                            @endforeach
                        </optgroup>
                    @empty
                        <option disabled>No accounts available</option>
                    @endforelse
                </select>
                @error('payment_account_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Payment Amount *
                </label>
                <input type="number"
                       id="amount"
                       wire:model="amount"
                       step="0.01"
                       min="0.01"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-zinc-900 dark:text-white">
                @error('amount')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="payment_method" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Payment Method
                </label>
                <select id="payment_method"
                        wire:model="payment_method"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-zinc-900 dark:text-white">
                    <option value="">Select Method</option>
                    <optgroup label="Cash">
                        <option value="cash">Cash</option>
                    </optgroup>
                    <optgroup label="Bank">
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cheque">Cheque</option>
                        <option value="card">Card</option>
                    </optgroup>
                    <optgroup label="Mobile Money">
                        <option value="mobile_money_airtel">Mobile Money - Airtel</option>
                        <option value="mobile_money_mtn">Mobile Money - MTN</option>
                        <option value="mobile_money">Mobile Money - Other</option>
                    </optgroup>
                    <optgroup label="Other">
                        <option value="other">Other</option>
                    </optgroup>
                </select>
                @error('payment_method')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="payment_reference" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Payment Reference
                </label>
                <input type="text"
                       id="payment_reference"
                       wire:model="payment_reference"
                       placeholder="Transaction ID, Cheque number, etc."
                       class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-zinc-900 dark:text-white">
                @error('payment_reference')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Notes
                </label>
                <textarea id="notes"
                          wire:model="notes"
                          rows="3"
                          class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-zinc-900 dark:text-white"></textarea>
                @error('notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-zinc-700">
                <a href="{{ route('payment-vouchers.show', $paymentId) }}"
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-zinc-800 dark:text-gray-300 dark:border-zinc-700 dark:hover:bg-zinc-700">
                    Cancel
                </a>
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Update Payment Voucher
                </button>
            </div>
        </form>
    </div>
</div>
