<?php

use App\Models\Expense;
use App\Models\Program;
use App\Models\Currency;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public string $categoryFilter = '';
    public ?int $programFilter = null;
    public bool $showPaymentModal = false;
    public ?int $selectedExpenseId = null;
    public string $paymentReference = '';

    public function with(): array
    {
        $query = Expense::with(['program', 'vendor', 'staff', 'account'])
            ->when($this->search, function ($q) {
                $q->where('description', 'like', '%' . $this->search . '%')
                  ->orWhereHas('vendor', function ($q) {
                      $q->where('name', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('staff', function ($q) {
                      $q->where('first_name', 'like', '%' . $this->search . '%')
                        ->orWhere('last_name', 'like', '%' . $this->search . '%');
                  });
            })
            ->when($this->statusFilter !== 'all', function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->when($this->categoryFilter, function ($q) {
                $q->where('category', $this->categoryFilter);
            })
            ->when($this->programFilter, function ($q) {
                $q->where('program_id', $this->programFilter);
            })
            ->latest('expense_date');

        return [
            'expenses' => $query->paginate(15),
            'programs' => Program::orderBy('name')->get(),
            'categories' => Expense::getCategories(),
            'totalExpenses' => Expense::sum('amount_base') ?: Expense::sum('amount'),
            'totalPaid' => Expense::where('payment_status', 'paid')->sum('amount_base') ?: Expense::where('payment_status', 'paid')->sum('amount'),
            'totalUnpaid' => Expense::where('payment_status', 'unpaid')->sum('amount_base') ?: Expense::where('payment_status', 'unpaid')->sum('amount'),
            'baseCurrency' => Currency::getBaseCurrency(),
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedProgramFilter(): void
    {
        $this->resetPage();
    }

    public function openPaymentModal(int $id): void
    {
        $this->selectedExpenseId = $id;
        $this->paymentReference = '';
        $this->showPaymentModal = true;
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
        $this->selectedExpenseId = null;
        $this->paymentReference = '';
    }

    public function markAsPaid(): void
    {
        if (!$this->selectedExpenseId) return;

        $expense = Expense::findOrFail($this->selectedExpenseId);
        $expense->markAsPaid($this->paymentReference ?: null);

        $this->closePaymentModal();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Expense marked as paid.'
        ]);
    }

    public function deleteExpense(int $id): void
    {
        $expense = Expense::findOrFail($id);
        
        if ($expense->receipt_path && \Storage::disk('public')->exists($expense->receipt_path)) {
            \Storage::disk('public')->delete($expense->receipt_path);
        }
        
        $expense->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Expense deleted successfully.'
        ]);
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-red-600 via-pink-600 to-rose-600 bg-clip-text text-transparent">
                Expenses
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Track and manage business expenses</p>
        </div>
        <a href="{{ route('expenses.create') }}" 
           class="px-6 py-3 bg-gradient-to-r from-red-500 to-pink-600 text-white rounded-xl hover:shadow-xl transition-all duration-200 font-semibold">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            New Expense
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 p-6 rounded-xl border-2 border-blue-200 dark:border-blue-800 shadow-lg">
            <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Total Expenses</div>
            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $baseCurrency->symbol }} {{ number_format($totalExpenses, 0) }}</div>
        </div>
        <div class="bg-gradient-to-br from-red-50 to-pink-50 dark:from-red-900/20 dark:to-pink-900/20 p-6 rounded-xl border-2 border-red-200 dark:border-red-800 shadow-lg">
            <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Paid</div>
            <div class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $baseCurrency->symbol }} {{ number_format($totalPaid, 0) }}</div>
        </div>
        <div class="bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 p-6 rounded-xl border-2 border-orange-200 dark:border-orange-800 shadow-lg">
            <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Unpaid (Accounts Payable)</div>
            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ $baseCurrency->symbol }} {{ number_format($totalUnpaid, 0) }}</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
                <input type="text" 
                       wire:model.live.debounce.300ms="search"
                       placeholder="Description or vendor..."
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                <select wire:model.live="statusFilter"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white">
                    <option value="all">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Category</label>
                <select wire:model.live="categoryFilter"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white">
                    <option value="">All Categories</option>
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Program</label>
                <select wire:model.live="programFilter"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white">
                    <option value="">All Programs</option>
                    @foreach($programs as $program)
                        <option value="{{ $program->id }}">{{ $program->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Expenses Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Date</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Description</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Account</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Paid To</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Program</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Amount</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Payment Status</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Receipt</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($expenses as $expense)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            {{ $expense->expense_date->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $expense->description }}</div>
                            @if($expense->category)
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">🏷️ {{ $expense->category }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($expense->account)
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $expense->account->code }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $expense->account->name }}</div>
                            @else
                                <span class="text-xs text-gray-400">N/A</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            @if($expense->vendor)
                                {{ $expense->vendor->name }}
                            @elseif($expense->staff)
                                {{ $expense->staff->first_name }} {{ $expense->staff->last_name }}
                                <span class="text-xs text-gray-500">(Staff)</span>
                            @else
                                <span class="text-gray-400">N/A</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            @if($expense->program)
                                {{ $expense->program->name }}
                            @else
                                <span class="text-gray-400 italic">No Program</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="text-sm font-bold text-red-600 dark:text-red-400">
                                {{ $expense->currency ?? $baseCurrency->code }} {{ number_format($expense->amount, 0) }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col items-center gap-1">
                                @if($expense->payment_status === 'paid')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                        ✓ Paid
                                    </span>
                                    @if($expense->payment_date)
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $expense->payment_date->format('M d, Y') }}</span>
                                    @endif
                                    @if($expense->payment_reference)
                                        <span class="text-xs text-gray-600 dark:text-gray-400" title="Payment Reference">Ref: {{ $expense->payment_reference }}</span>
                                    @endif
                                @elseif($expense->payment_status === 'partial')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                        Partially Paid
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400">
                                        Unpaid
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if($expense->receipt_path)
                                <a href="{{ $expense->getReceiptUrlAttribute() }}" 
                                   target="_blank"
                                   class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                   title="View Receipt">
                                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('expenses.edit', $expense->id) }}"
                                   class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                   title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5h2m-1 14v-4m-5 4h10a2 2 0 002-2v-5.586a1 1 0 00-.293-.707l-6.414-6.414a1 1 0 00-1.414 0L5.293 9.707A1 1 0 005 10.414V17a2 2 0 002 2z" />
                                    </svg>
                                </a>
                                @if($expense->payment_status !== 'paid')
                                    <button wire:click="openPaymentModal({{ $expense->id }})"
                                            class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                            title="Mark as Paid">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                @endif
                                <button wire:click="deleteExpense({{ $expense->id }})"
                                        wire:confirm="Are you sure you want to delete this expense?"
                                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                        title="Delete">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400 font-medium mb-4">No expenses found</p>
                            <a href="{{ route('expenses.create') }}" 
                               class="inline-flex items-center px-6 py-2 bg-gradient-to-r from-red-500 to-pink-600 text-white rounded-lg hover:shadow-lg transition-all duration-200 text-sm font-medium">
                                Create First Expense
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($expenses->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $expenses->links() }}
        </div>
        @endif
    </div>

    <!-- Mark as Paid Modal -->
    @if($showPaymentModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showPaymentModal') }" x-show="show" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <!-- Backdrop -->
            <div class="fixed inset-0 transition-opacity bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75" 
                 wire:click="closePaymentModal"></div>

            <!-- Modal -->
            <div class="relative inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-2xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Mark Expense as Paid</h3>
                    <button wire:click="closePaymentModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Record the payment for this expense. This will update the payment status and record the payment date.
                    </p>

                    <div>
                        <label for="payment_reference" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Payment Reference (Optional)
                        </label>
                        <input type="text" 
                               id="payment_reference"
                               wire:model="paymentReference"
                               placeholder="Check #, Transaction ID, etc."
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Enter check number, bank transaction ID, or any payment reference
                        </p>
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button wire:click="markAsPaid"
                            class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors">
                        Confirm Payment
                    </button>
                    <button wire:click="closePaymentModal"
                            class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
