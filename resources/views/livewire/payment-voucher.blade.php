<div class="max-w-4xl mx-auto p-6">
    <div class="bg-white shadow-lg rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Create Payment Voucher</h2>
            @if($expense)
                <p class="text-sm text-gray-600 mt-1">
                    For Expense: {{ $expense->description }} 
                    (Outstanding: {{ number_format($expense->outstanding_balance, 2) }})
                </p>
            @endif
        </div>

        <form wire:submit="createPayment" class="p-6 space-y-6">
            @if($expense)
                <!-- Expense Details -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="font-medium text-gray-900 mb-2">Expense Details</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Amount:</span>
                            <span class="font-medium">{{ number_format($expense->amount, 2) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Total Paid:</span>
                            <span class="font-medium">{{ number_format($expense->total_paid, 2) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Outstanding:</span>
                            <span class="font-medium text-red-600">{{ number_format($expense->outstanding_balance, 2) }}</span>
                        </div>
                        @if($expense->charges > 0)
                        <div>
                            <span class="text-gray-600">Charges:</span>
                            <span class="font-medium">{{ number_format($expense->charges, 2) }}</span>
                        </div>
                        @endif
                        <div>
                            <span class="text-gray-600">Status:</span>
                            <span class="font-medium capitalize">{{ $expense->payment_status }}</span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Payment Date -->
            <div>
                <label for="payment_date" class="block text-sm font-medium text-gray-700 mb-1">
                    Payment Date *
                </label>
                <input type="date" 
                       id="payment_date"
                       wire:model="payment_date"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @error('payment_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Payment Account - All Charts of Accounts -->
            <div>
                <label for="payment_account_id" class="block text-sm font-medium text-gray-700 mb-1">
                    Payment Account (Debit From) *
                </label>
                <select id="payment_account_id"
                        wire:model="payment_account_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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

            <!-- Amount -->
            <div>
                <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">
                    Payment Amount *
                </label>
                <input type="number" 
                       id="amount"
                       wire:model="amount"
                       step="0.01"
                       min="0.01"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @error('amount')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Payment Method -->
            <div>
                <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-1">
                    Payment Method
                </label>
                <select id="payment_method"
                        wire:model="payment_method"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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

            <!-- Payment Reference -->
            <div>
                <label for="payment_reference" class="block text-sm font-medium text-gray-700 mb-1">
                    Payment Reference
                </label>
                <input type="text" 
                       id="payment_reference"
                       wire:model="payment_reference"
                       placeholder="Transaction ID, Cheque number, etc."
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @error('payment_reference')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Notes -->
            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                    Notes
                </label>
                <textarea id="notes"
                          wire:model="notes"
                          rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                @error('notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="{{ $expense ? route('expenses.show', $expense->id) : route('expenses.index') }}" 
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </a>
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Create Payment Voucher
                </button>
            </div>
        </form>
    </div>
</div>
