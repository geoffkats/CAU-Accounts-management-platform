<?php

use App\Models\Account;
use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\JournalEntryLine;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\VendorInvoice;
use Illuminate\Support\Facades\Schema;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $typeFilter = 'all';
    public bool $seeding = false;
    public bool $showPreview = false;
    public string $previewOutput = '';
    public bool $autoSeeded = false;
    public bool $showTransferModal = false;
    public ?int $transferSourceId = null;
    public string $transferTargetId = '';
    public array $transferTargets = [];

    public function mount(): void
    {
        // Auto-seed if no accounts exist (eliminates manual work!)
        if (Account::count() === 0) {
            $this->autoSeedAccounts();
        }
    }

    public function autoSeedAccounts(): void
    {
        if (!auth()->user() || !in_array(auth()->user()->role, ['admin','accountant'])) {
            return;
        }

        try {
            \Artisan::call('accounts:sync');
            $this->autoSeeded = true;
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => '✨ Welcome! Your Chart of Accounts has been automatically created with 87 standard accounts. No manual ledger creation needed!'
            ]);
        } catch (\Throwable $e) {
            \Log::error('Auto-seed COA failed: ' . $e->getMessage());
        }
    }

    public function with(): array
    {
        $query = Account::query()
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('code', 'like', '%' . $this->search . '%');
            })
            ->when($this->typeFilter !== 'all', function ($q) {
                $q->where('type', $this->typeFilter);
            })
            ->orderBy('code');

        return [
            'accounts' => $query->paginate(20),
            'accountsByType' => Account::select('type', \DB::raw('count(*) as total'))
                ->groupBy('type')
                ->get()
                ->pluck('total', 'type'),
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function deleteAccount(int $id): void
    {
        $account = Account::findOrFail($id);

        $expensePaymentUsage = 0;
        if (Schema::hasColumn('expenses', 'payment_account_id')) {
            $expensePaymentUsage = Expense::where('payment_account_id', $account->id)->count();
        }

        $usage = [
            'sales' => $account->sales()->count(),
            'expenses' => $account->expenses()->count(),
            'vendor_invoices' => $account->vendorInvoices()->count(),
            'journal_lines' => $account->journalEntryLines()->count(),
            'payments' => Payment::where('payment_account_id', $account->id)->count(),
            'customer_payments' => CustomerPayment::where('payment_account_id', $account->id)->count(),
            'expense_payments' => $expensePaymentUsage,
            'children' => $account->children()->count(),
        ];
        $hasUsage = array_sum($usage) > 0;
        
        // Check for related transactions
        if ($hasUsage) {
            $this->transferSourceId = $account->id;
            $this->transferTargetId = '';
            $this->transferTargets = Account::where('type', $account->type)
                ->where('id', '!=', $account->id)
                ->orderBy('code')
                ->get(['id', 'code', 'name'])
                ->map(fn($a) => ['id' => (string) $a->id, 'label' => $a->code . ' - ' . $a->name])
                ->all();
            $this->showTransferModal = true;
            return;
        }

        try {
            $account->delete();
        } catch (\Throwable $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Unable to delete account. It may be referenced by transactions or locked by database constraints.'
            ]);
            return;
        }
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Account deleted successfully.'
        ]);
        
        // Refresh the list
        $this->resetPage();
    }

    public function seedStandard(): void
    {
        if (!auth()->user() || !in_array(auth()->user()->role, ['admin','accountant'])) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You do not have permission to perform this action.'
            ]);
            return;
        }

        $this->seeding = true;
        try {
            \Artisan::call('accounts:sync');
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Standard Chart of Accounts synced successfully.'
            ]);
            $this->resetPage();
        } catch (\Throwable $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to seed chart of accounts: ' . $e->getMessage(),
            ]);
        } finally {
            $this->seeding = false;
        }
    }

    public function previewSeed(): void
    {
        if (!auth()->user() || !in_array(auth()->user()->role, ['admin','accountant'])) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You do not have permission to perform this action.'
            ]);
            return;
        }

        try {
            \Artisan::call('accounts:sync', ['--dry-run' => true]);
            $this->previewOutput = trim(\Artisan::output());
            $this->showPreview = true;
        } catch (\Throwable $e) {
            $this->previewOutput = 'Failed to run dry run: ' . $e->getMessage();
            $this->showPreview = true;
        }
    }

    public function confirmTransferDelete(): void
    {
        if (!$this->transferSourceId) {
            $this->showTransferModal = false;
            return;
        }

        $account = Account::findOrFail($this->transferSourceId);
        $target = null;

        if ($this->transferTargetId !== '' && $this->transferTargetId !== 'placeholder') {
            $target = Account::find($this->transferTargetId);
        }

        if (!$target) {
            $target = $this->getPlaceholderAccount($account);
        }

        try {
            Account::where('parent_id', $account->id)->update(['parent_id' => $target->id]);
            Sale::where('account_id', $account->id)->update(['account_id' => $target->id]);
            Expense::where('account_id', $account->id)->update(['account_id' => $target->id]);
            VendorInvoice::where('account_id', $account->id)->update(['account_id' => $target->id]);
            JournalEntryLine::where('account_id', $account->id)->update(['account_id' => $target->id]);
            Payment::where('payment_account_id', $account->id)->update(['payment_account_id' => $target->id]);
            CustomerPayment::where('payment_account_id', $account->id)->update(['payment_account_id' => $target->id]);

            if (Schema::hasColumn('expenses', 'payment_account_id')) {
                Expense::where('payment_account_id', $account->id)->update(['payment_account_id' => $target->id]);
            }

            $account->delete();
        } catch (\Throwable $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Unable to delete account. It may be referenced by transactions or locked by database constraints.'
            ]);
            return;
        }

        $this->showTransferModal = false;
        $this->transferSourceId = null;
        $this->transferTargetId = '';
        $this->transferTargets = [];

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Account deleted. Linked records were reassigned to the selected account.'
        ]);

        $this->resetPage();
    }

    private function getPlaceholderAccount(Account $account): Account
    {
        $baseCode = match ($account->type) {
            Account::TYPE_ASSET => '1999',
            Account::TYPE_LIABILITY => '2999',
            Account::TYPE_EQUITY => '3999',
            Account::TYPE_INCOME => '4999',
            Account::TYPE_EXPENSE => '5999',
            default => '9999',
        };

        $code = $baseCode;
        $suffix = 0;
        while (Account::where('code', $code)->exists()) {
            $suffix++;
            $code = $baseCode . '-' . $suffix;
        }

        return Account::firstOrCreate(
            ['code' => $code],
            [
                'name' => 'Deleted ' . ucfirst($account->type) . ' Account',
                'type' => $account->type,
                'category' => $account->category,
                'description' => 'System placeholder for deleted accounts.',
                'parent_id' => null,
                'is_active' => false,
            ]
        );
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Auto-Seed Success Banner -->
    @if($autoSeeded)
    <div class="mb-6 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-2 border-green-300 dark:border-green-700 rounded-2xl p-6 shadow-lg">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-green-500 dark:bg-green-600 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-xl font-bold text-green-900 dark:text-green-100 mb-2">
                    🎉 Chart of Accounts Automatically Created!
                </h3>
                <p class="text-green-800 dark:text-green-200 mb-3">
                    We've detected this is your first time, so we've automatically created <strong>87 standard accounts</strong> organized into:
                </p>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-3">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-green-200 dark:border-green-800">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">Assets</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Cash, Bank, AR</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-green-200 dark:border-green-800">
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">Liabilities</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">AP, Loans</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-green-200 dark:border-green-800">
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">Equity</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Capital, Retained</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-green-200 dark:border-green-800">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">Income</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Tuition, Grants</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-green-200 dark:border-green-800">
                        <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">Expenses</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">72 categories</div>
                    </div>
                </div>
                <div class="flex items-center gap-2 text-green-800 dark:text-green-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span class="font-semibold">No manual ledger creation required! Unlike QuickBooks, your accounts are ready to use instantly.</span>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 bg-clip-text text-transparent">
                Chart of Accounts
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2 text-lg">Manage your accounting structure</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('accounts.opening-balances') }}"
               class="px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-xl shadow hover:shadow-md transition">
                Opening Balances
            </a>
            <button wire:click="previewSeed"
                    class="px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-xl shadow hover:shadow-md transition">
                Preview Seed (Dry Run)
            </button>
            <button wire:click="seedStandard"
                    wire:confirm="This will create/update standard accounts. Continue?"
                    class="px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-xl shadow hover:shadow-md transition disabled:opacity-60"
                    @disabled("$seeding")>
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h4l2 2h8a1 1 0 011 1v2M3 4v13a1 1 0 001 1h16a1 1 0 001-1V8M3 4l5 5m4 4h4m-4 4h4m-8-4h.01" />
                </svg>
                Seed Standard COA
            </button>
            <a href="{{ route('accounts.create') }}" 
               class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 font-semibold inline-flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                New Account
            </a>
        </div>
    </div>

    @if($showPreview)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showPreview') }" x-show="show" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75" 
                 @click="show=false"></div>
            <div class="relative inline-block w-full max-w-3xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-2xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">COA Seed Preview (Dry Run)</h3>
                    <button @click="show=false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <pre class="max-h-[60vh] overflow-auto p-4 bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-100 rounded-lg text-xs whitespace-pre-wrap">{{ $previewOutput }}</pre>
                <div class="flex justify-end gap-3 mt-4">
                    <button @click="show=false" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg text-gray-800 dark:text-gray-200">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($showTransferModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showTransferModal') }" x-show="show" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75"
                 @click="show=false"></div>
            <div class="relative inline-block w-full max-w-xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-2xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Reassign Records Before Delete</h3>
                    <button @click="show=false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                    This account has linked transactions. Choose where to move those records before deleting.
                </p>

                <div class="space-y-3">
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">Transfer To</label>
                    <select wire:model="transferTargetId"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Select account</option>
                        <option value="placeholder">Use system placeholder (recommended)</option>
                        @foreach($transferTargets as $target)
                            <option value="{{ $target['id'] }}">{{ $target['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button @click="show=false"
                            class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg text-gray-800 dark:text-gray-200">
                        Cancel
                    </button>
                    <button wire:click="confirmTransferDelete"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
                        Reassign & Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Quick Links -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <a href="{{ route('budgets.index') }}" class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 border-2 border-indigo-200 dark:border-indigo-800 rounded-xl p-4 hover:shadow-lg transition-all duration-200 group">
            <div class="flex items-center gap-3">
                <div class="p-3 bg-indigo-200 dark:bg-indigo-800 rounded-lg group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-indigo-700 dark:text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <div class="font-bold text-indigo-900 dark:text-indigo-300">Budget vs Actual</div>
                    <div class="text-sm text-indigo-600 dark:text-indigo-400">Track program budgets</div>
                </div>
            </div>
        </a>
        <a href="{{ route('expenses.index') }}" class="bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 border-2 border-orange-200 dark:border-orange-800 rounded-xl p-4 hover:shadow-lg transition-all duration-200 group">
            <div class="flex items-center gap-3">
                <div class="p-3 bg-orange-200 dark:bg-orange-800 rounded-lg group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-orange-700 dark:text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div>
                    <div class="font-bold text-orange-900 dark:text-orange-300">View Expenses</div>
                    <div class="text-sm text-orange-600 dark:text-orange-400">Review spending by account</div>
                </div>
            </div>
        </a>
        <a href="{{ route('sales.index') }}" class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-2 border-green-200 dark:border-green-800 rounded-xl p-4 hover:shadow-lg transition-all duration-200 group">
            <div class="flex items-center gap-3">
                <div class="p-3 bg-green-200 dark:bg-green-800 rounded-lg group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-green-700 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
                <div>
                    <div class="font-bold text-green-900 dark:text-green-300">View Sales</div>
                    <div class="text-sm text-green-600 dark:text-green-400">Review income by account</div>
                </div>
            </div>
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 p-5 rounded-2xl border-2 border-green-200 dark:border-green-800 shadow-lg hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center justify-between mb-2">
                <div class="text-sm font-bold text-green-700 dark:text-green-300">ASSETS</div>
                <div class="p-2 bg-green-200 dark:bg-green-800 rounded-lg">
                    <svg class="w-5 h-5 text-green-700 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $accountsByType->get('asset', 0) }}</div>
        </div>
        <div class="bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 p-5 rounded-2xl border-2 border-red-200 dark:border-red-800 shadow-lg hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center justify-between mb-2">
                <div class="text-sm font-bold text-red-700 dark:text-red-300">LIABILITIES</div>
                <div class="p-2 bg-red-200 dark:bg-red-800 rounded-lg">
                    <svg class="w-5 h-5 text-red-700 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $accountsByType->get('liability', 0) }}</div>
        </div>
        <div class="bg-gradient-to-br from-purple-50 to-violet-50 dark:from-purple-900/20 dark:to-violet-900/20 p-5 rounded-2xl border-2 border-purple-200 dark:border-purple-800 shadow-lg hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center justify-between mb-2">
                <div class="text-sm font-bold text-purple-700 dark:text-purple-300">EQUITY</div>
                <div class="p-2 bg-purple-200 dark:bg-purple-800 rounded-lg">
                    <svg class="w-5 h-5 text-purple-700 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $accountsByType->get('equity', 0) }}</div>
        </div>
        <div class="bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 p-5 rounded-2xl border-2 border-blue-200 dark:border-blue-800 shadow-lg hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center justify-between mb-2">
                <div class="text-sm font-bold text-blue-700 dark:text-blue-300">INCOME</div>
                <div class="p-2 bg-blue-200 dark:bg-blue-800 rounded-lg">
                    <svg class="w-5 h-5 text-blue-700 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $accountsByType->get('income', 0) }}</div>
        </div>
        <div class="bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 p-5 rounded-2xl border-2 border-orange-200 dark:border-orange-800 shadow-lg hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center justify-between mb-2">
                <div class="text-sm font-bold text-orange-700 dark:text-orange-300">EXPENSES</div>
                <div class="p-2 bg-orange-200 dark:bg-orange-800 rounded-lg">
                    <svg class="w-5 h-5 text-orange-700 dark:text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ $accountsByType->get('expense', 0) }}</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Search Accounts
                </label>
                <input type="text" 
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search by name or code..."
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter by Type
                </label>
                <select wire:model.live="typeFilter"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white shadow-sm">
                    <option value="all">All Types</option>
                    <option value="asset">Assets</option>
                    <option value="liability">Liabilities</option>
                    <option value="equity">Equity</option>
                    <option value="income">Income</option>
                    <option value="expense">Expenses</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Accounts Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Code</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Account Name</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Parent Account</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($accounts as $account)
                    <tr class="hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 dark:hover:from-gray-700 dark:hover:to-gray-600 transition-all duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center">
                                    <span class="font-bold text-white text-xs">{{ strtoupper(substr($account->code, 0, 2)) }}</span>
                                </div>
                                <div class="ml-3">
                                    <span class="font-mono text-sm font-bold text-gray-900 dark:text-white">
                                        {{ $account->code }}
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $account->name }}</div>
                            @if($account->description)
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-1">{{ $account->description }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold shadow-sm
                                {{ $account->type === 'asset' ? 'bg-gradient-to-r from-green-500 to-emerald-600 text-white' : '' }}
                                {{ $account->type === 'liability' ? 'bg-gradient-to-r from-red-500 to-rose-600 text-white' : '' }}
                                {{ $account->type === 'equity' ? 'bg-gradient-to-r from-purple-500 to-violet-600 text-white' : '' }}
                                {{ $account->type === 'income' ? 'bg-gradient-to-r from-blue-500 to-cyan-600 text-white' : '' }}
                                {{ $account->type === 'expense' ? 'bg-gradient-to-r from-orange-500 to-amber-600 text-white' : '' }}">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <circle cx="10" cy="10" r="3"/>
                                </svg>
                                {{ ucfirst($account->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($account->category === 'long_term')
                                <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200">
                                    Long-term
                                </span>
                            @elseif($account->category === 'short_term')
                                <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200">
                                    Short-term
                                </span>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($account->parent)
                            <span class="text-sm text-gray-600 dark:text-gray-400 font-medium">
                                {{ $account->parent->name }}
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                                Main Account
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('accounts.opening-balances', ['account_id' => $account->id]) }}"
                                   class="inline-flex items-center p-2 bg-emerald-100 hover:bg-emerald-200 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400 rounded-lg transition-colors duration-150 group">
                                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </a>
                                <a href="{{ route('accounts.edit', $account->id) }}" 
                                   class="inline-flex items-center p-2 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 text-blue-600 dark:text-blue-400 rounded-lg transition-colors duration-150 group">
                                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                <button type="button"
                                    wire:click="deleteAccount({{ $account->id }})"
                                    wire:confirm="Are you sure you want to delete this account? This action cannot be undone."
                                    class="inline-flex items-center p-2 bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:hover:bg-red-900/50 text-red-600 dark:text-red-400 rounded-lg transition-colors duration-150 group">
                                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-24 h-24 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <p class="text-gray-500 dark:text-gray-400 font-bold text-lg mb-2">No accounts found</p>
                                <p class="text-gray-400 dark:text-gray-500 text-sm mb-6">Start building your chart of accounts</p>
                                <a href="{{ route('accounts.create') }}" 
                                   class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 font-semibold">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Create First Account
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($accounts->hasPages())
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
            {{ $accounts->links() }}
        </div>
        @endif
    </div>
</div>
