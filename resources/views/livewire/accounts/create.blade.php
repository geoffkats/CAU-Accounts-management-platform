<?php

use App\Models\Account;
use Livewire\Volt\Component;

new class extends Component {
    public string $code = '';
    public string $name = '';
    public string $type = 'asset';
    public string $category = '';
    public ?int $parent_id = null;
    public string $description = '';

    public function with(): array
    {
        return [
            'parentAccounts' => Account::whereNull('parent_id')
                ->orderBy('code')
                ->get(),
        ];
    }

    public function updatedType(): void
    {
        if (!in_array($this->type, ['asset', 'liability'])) {
            $this->category = '';
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'code' => ['required', 'string', 'max:20', 'unique:accounts,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:asset,liability,equity,income,expense'],
            'category' => ['nullable', 'in:short_term,long_term'],
            'parent_id' => ['nullable', 'exists:accounts,id'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['category'] = $validated['category'] ?: null;

        Account::create($validated);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Account created successfully.'
        ]);

        $this->redirect(route('accounts.index'));
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('accounts.index') }}" 
           class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 via-cyan-600 to-teal-600 bg-clip-text text-transparent">
                Create New Account
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Add a new account to your chart of accounts</p>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-500 to-cyan-600 p-6">
            <h2 class="text-xl font-bold text-white">Account Details</h2>
        </div>

        <form wire:submit="save" class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Account Code -->
                <div>
                    <label for="code" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Account Code <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="code"
                           wire:model="code"
                           placeholder="e.g., 1000, 2000"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white font-mono"
                           required>
                    @error('code')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Unique identifier for this account</p>
                </div>

                <!-- Account Name -->
                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Account Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="name"
                           wire:model="name"
                           placeholder="e.g., Cash in Bank"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                           required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Account Type -->
                <div>
                    <label for="type" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Account Type <span class="text-red-500">*</span>
                    </label>
                    <select id="type"
                            wire:model="type"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                            required>
                        <option value="asset">Asset</option>
                        <option value="liability">Liability</option>
                        <option value="equity">Equity</option>
                        <option value="income">Income</option>
                        <option value="expense">Expense</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Account Category -->
                <div>
                    <label for="category" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Account Category
                    </label>
                    <select id="category"
                            wire:model="category"
                            @disabled(!in_array($type, ['asset','liability']))
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Not set</option>
                        <option value="short_term">Short-term (Current)</option>
                        <option value="long_term">Long-term (Non-current)</option>
                    </select>
                    @error('category')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Used for assets and liabilities on the balance sheet.</p>
                </div>

                <!-- Parent Account -->
                <div>
                    <label for="parent_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Parent Account (Optional)
                    </label>
                    <select id="parent_id"
                            wire:model="parent_id"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">None (Main Account)</option>
                        @foreach($parentAccounts as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->code }} - {{ $parent->name }}</option>
                        @endforeach
                    </select>
                    @error('parent_id')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Create hierarchical account structure</p>
                </div>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Description (Optional)
                </label>
                <textarea id="description"
                          wire:model="description"
                          rows="4"
                          placeholder="Describe the purpose of this account..."
                          class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white resize-none"></textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror>
            </div>

            <!-- Account Type Guide -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-200 dark:border-blue-800 rounded-xl p-4">
                <h3 class="text-sm font-bold text-blue-900 dark:text-blue-300 mb-2">Account Types Guide:</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs text-blue-800 dark:text-blue-300">
                    <div><strong>Asset:</strong> Resources owned (cash, inventory, equipment)</div>
                    <div><strong>Liability:</strong> Debts owed (loans, payables)</div>
                    <div><strong>Equity:</strong> Owner's investment and retained earnings</div>
                    <div><strong>Income:</strong> Revenue from sales and services</div>
                    <div><strong>Expense:</strong> Costs of operations</div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="submit"
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-500 to-cyan-600 text-white rounded-xl hover:shadow-xl transition-all duration-200 font-semibold">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Create Account
                </button>
                <a href="{{ route('accounts.index') }}"
                   class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-200 font-semibold">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
