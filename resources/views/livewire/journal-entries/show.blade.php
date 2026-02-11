<?php

use App\Models\JournalEntry;
use App\Models\Currency;
use Livewire\Volt\Component;

new class extends Component {
    public int $entryId;
    public ?JournalEntry $entry = null;

    public function mount(int $id): void
    {
        $this->entryId = $id;
        $this->entry = JournalEntry::with(['lines.account', 'creator', 'expense', 'income'])->findOrFail($id);
    }

    public function voidEntry(): void
    {
        if ($this->entry->status !== 'posted') {
            session()->flash('error', 'Only posted entries can be voided.');
            return;
        }

        try {
            $this->entry->void();
            session()->flash('success', 'Journal entry voided successfully.');
            $this->entry->refresh();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to void entry: ' . $e->getMessage());
        }
    }

    public function with(): array
    {
        return [
            'baseCurrency' => Currency::getBaseCurrency(),
            'entry' => $this->entry,
            'replacement' => $this->entry
                ? \App\Models\JournalEntry::where('replaces_entry_id', $this->entry->id)->latest('id')->first()
                : null,
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
                Journal Entry Details
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Reference: <span class="font-mono font-bold">{{ $entry->reference }}</span></p>
        </div>
        <div class="flex items-center gap-3">
            @if($entry->status !== 'void')
                <a href="{{ route('journal-entries.edit', $entry->id) }}" 
                   class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors">
                    Edit Entry
                </a>
            @endif
            <a href="{{ route('journal-entries.index') }}" 
               class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                ← Back to List
            </a>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <!-- Entry Header Info -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <div class="text-sm opacity-90 mb-1">Date</div>
                <div class="text-xl font-bold">{{ $entry->date->format('M d, Y') }}</div>
            </div>
            <div>
                <div class="text-sm opacity-90 mb-1">Type</div>
                <div class="text-xl font-bold">{{ ucfirst($entry->type) }}</div>
            </div>
            <div>
                <div class="text-sm opacity-90 mb-1">Status</div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 text-sm font-bold rounded-full
                        @if($entry->status === 'posted') bg-green-100 text-green-800
                        @elseif($entry->status === 'void') bg-red-100 text-red-800
                        @else bg-yellow-100 text-yellow-800
                        @endif">
                        {{ ucfirst($entry->status) }}
                    </span>
                </div>
            </div>
            <div>
                <div class="text-sm opacity-90 mb-1">Created By</div>
                <div class="text-xl font-bold">{{ $entry->creator?->name ?? 'System' }}</div>
            </div>
        </div>

        
        <div class="mt-4 flex flex-wrap gap-3">
            @if($entry->replaces_entry_id)
                <a href="{{ route('journal-entries.show', $entry->replaces_entry_id) }}" class="px-3 py-1 rounded-full text-xs font-semibold bg-white/20 hover:bg-white/30 transition">
                    Replaces: <span class="font-mono">{{ $entry->replaces?->reference ?? ('#'.$entry->replaces_entry_id) }}</span>
                </a>
            @endif
            @if($entry->status === 'void')
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-500/20">
                    Voided @ {{ optional($entry->voided_at)->format('M d, Y H:i') ?? $entry->updated_at->format('M d, Y H:i') }}
                </span>
                @if($replacement)
                    <a href="{{ route('journal-entries.show', $replacement->id) }}" class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-500/20 hover:bg-blue-500/30 transition">
                        Replaced by: <span class="font-mono">{{ $replacement->reference }}</span>
                    </a>
                @endif
            @endif
        </div>

        <div class="mt-4 pt-4 border-t border-white/20">
            <div class="text-sm opacity-90 mb-1">Description</div>
            <div class="text-base">{{ $entry->description }}</div>
        </div>
    </div>

    <!-- Source Transaction Links -->
    @if($entry->expense || $entry->income)
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
        <div class="flex items-center gap-2 text-blue-700 dark:text-blue-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-medium">
                This entry was automatically created from:
                @if($entry->expense)
                    <a href="{{ route('expenses.index') }}" class="underline hover:text-blue-900 dark:hover:text-blue-300">
                        Expense #{{ $entry->expense_id }}
                    </a>
                @elseif($entry->income)
                    <a href="{{ route('sales.index') }}" class="underline hover:text-blue-900 dark:hover:text-blue-300">
                        Sale/Payment #{{ $entry->income_id }}
                    </a>
                @endif
            </span>
        </div>
    </div>
    @endif

    <!-- Journal Lines -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gradient-to-r from-purple-500 to-indigo-600 p-6">
            <h2 class="text-xl font-bold text-white">Journal Lines</h2>
            <p class="text-white/80 text-sm mt-1">Detailed debit and credit entries</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Account</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Description</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Debit</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Credit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($entry->lines as $line)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-bold text-sm text-gray-900 dark:text-white">
                                    {{ $line->account->code }}
                                </span>
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $line->account->name }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                Type: <span class="font-medium">{{ ucfirst($line->account->type) }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                            {{ $line->description ?: '—' }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if($line->debit > 0)
                                <span class="text-sm font-bold text-green-600 dark:text-green-400">
                                    {{ $baseCurrency->symbol }} {{ number_format($line->debit, 2) }}
                                </span>
                            @else
                                <span class="text-sm text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if($line->credit > 0)
                                <span class="text-sm font-bold text-red-600 dark:text-red-400">
                                    {{ $baseCurrency->symbol }} {{ number_format($line->credit, 2) }}
                                </span>
                            @else
                                <span class="text-sm text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-gray-900 dark:to-gray-800">
                    <tr>
                        <td colspan="2" class="px-6 py-4 text-sm font-bold text-gray-900 dark:text-white">
                            Totals
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="text-sm font-bold text-green-600 dark:text-green-400">
                                {{ $baseCurrency->symbol }} {{ number_format($entry->totalDebits(), 2) }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="text-sm font-bold text-red-600 dark:text-red-400">
                                {{ $baseCurrency->symbol }} {{ number_format($entry->totalCredits(), 2) }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="px-6 py-3 text-sm font-bold text-gray-900 dark:text-white">
                            Balance Check
                        </td>
                        <td colspan="2" class="px-6 py-3 text-right">
                            @if($entry->isBalanced())
                                <span class="px-3 py-1 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full text-sm font-bold">
                                    ✓ Balanced (Difference: {{ $baseCurrency->symbol }} {{ number_format($entry->getImbalance(), 2) }})
                                </span>
                            @else
                                <span class="px-3 py-1 bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 rounded-full text-sm font-bold">
                                    ⚠ Out of Balance ({{ $baseCurrency->symbol }} {{ number_format($entry->getImbalance(), 2) }})
                                </span>
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Metadata -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Entry Metadata</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-600 dark:text-gray-400">Created At:</span>
                <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $entry->created_at->format('M d, Y h:i A') }}</span>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">Posted At:</span>
                <span class="ml-2 font-medium text-gray-900 dark:text-white">
                    {{ $entry->posted_at ? $entry->posted_at->format('M d, Y h:i A') : '—' }}
                </span>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">Created By:</span>
                <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $entry->creator?->name ?? 'System' }}</span>
            </div>
            <div>
                <span class="text-gray-600 dark:text-gray-400">Number of Lines:</span>
                <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $entry->lines->count() }}</span>
            </div>
        </div>
    </div>

    <!-- Actions -->
    @if($entry->status === 'posted')
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-6">
        <div class="flex items-start gap-4">
            <div class="flex-1">
                <h3 class="text-lg font-bold text-yellow-900 dark:text-yellow-400 mb-2">Void This Entry?</h3>
                <p class="text-sm text-yellow-800 dark:text-yellow-500">
                    Voiding this entry will mark it as void and prevent it from affecting account balances. 
                    This action cannot be undone. To reverse the entry, create a new reversing journal entry instead.
                </p>
            </div>
            <button wire:click="voidEntry" 
                    wire:confirm="Are you sure you want to void this journal entry? This action cannot be undone."
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                Void Entry
            </button>
        </div>
    </div>
    @endif
</div>
