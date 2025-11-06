<?php

use App\Models\JournalEntry;
use App\Models\Currency;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $type = 'all';
    public string $status = 'posted';
    public string $startDate = '';
    public string $endDate = '';

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function with(): array
    {
        $query = JournalEntry::with(['lines.account', 'creator', 'expense', 'income', 'sale', 'customerPayment'])
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->latest('date')
            ->latest('id');

        if ($this->type !== 'all') {
            $query->where('type', $this->type);
        }

        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('reference', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        return [
            'entries' => $query->paginate(20),
            'baseCurrency' => Currency::getBaseCurrency(),
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
                Journal Entries
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">All accounting transactions and adjustments</p>
        </div>
        <a href="{{ route('journal-entries.create') }}" 
           class="px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all shadow-lg hover:shadow-xl">
            + New Journal Entry
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
                <input type="text" wire:model.live="search" placeholder="Reference or description..." 
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Type</label>
                <select wire:model.live="type" 
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                    <option value="all">All Types</option>
                    <option value="expense">Expense</option>
                    <option value="income">Income</option>
                    <option value="payment">Payment</option>
                    <option value="adjustment">Adjustment</option>
                    <option value="opening">Opening Balance</option>
                    <option value="closing">Closing Entry</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                <select wire:model.live="status" 
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                    <option value="all">All Status</option>
                    <option value="draft">Draft</option>
                    <option value="posted">Posted</option>
                    <option value="void">Void</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start Date</label>
                <input type="date" wire:model.live="startDate" 
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">End Date</label>
                <input type="date" wire:model.live="endDate" 
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
            </div>
        </div>
    </div>

    <!-- Entries Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-gray-900 dark:to-gray-800">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Date</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Reference</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Type</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Description</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Debits</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Credits</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Status</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($entries as $entry)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            {{ $entry->date->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-mono font-bold text-gray-900 dark:text-white">
                                {{ $entry->reference }}
                            </span>
                            @if(!$entry->isBalanced())
                                <span title="Entry not balanced" class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">Imbalance</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 text-xs font-semibold rounded-full
                                @if($entry->type === 'expense') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                                @elseif($entry->type === 'income') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                @elseif($entry->type === 'payment') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                                @else bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400
                                @endif">
                                {{ ucfirst($entry->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white max-w-md truncate">
                            {{ $entry->description }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-bold text-green-600 dark:text-green-400">
                            {{ $baseCurrency->symbol }} {{ number_format($entry->totalDebits(), 2) }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-bold text-red-600 dark:text-red-400">
                            {{ $baseCurrency->symbol }} {{ number_format($entry->totalCredits(), 2) }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 text-xs font-bold rounded-full
                                @if($entry->status === 'posted') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                @elseif($entry->status === 'void') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                                @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                                @endif">
                                {{ ucfirst($entry->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <a href="{{ route('journal-entries.show', $entry->id) }}" 
                               class="text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300 text-sm font-medium">
                                View Details →
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            No journal entries found for the selected filters
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $entries->links() }}
        </div>
    </div>
</div>
