<?php

use App\Models\Account;
use App\Models\Currency;
use Livewire\Volt\Component;

new class extends Component {
    public string $startDate = '';
    public string $endDate = '';
    public bool $hideZero = true;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
    }

    public function with(): array
    {
        $accounts = Account::active()->orderBy('code')->get();
        $rows = $accounts->map(function ($a) {
            $q = $a->journalEntryLines()->whereHas('journalEntry', function ($q) {
                $q->where('status', 'posted')
                  ->whereBetween('date', [$this->startDate, $this->endDate]);
            });
            $debits = (float) $q->sum('debit');
            $credits = (float) $q->sum('credit');
            $net = round($debits - $credits, 2);
            $dr = $net > 0 ? $net : 0.0;
            $cr = $net < 0 ? abs($net) : 0.0;
            return (object) [
                'id' => $a->id,
                'code' => $a->code,
                'name' => $a->name,
                'type' => $a->type,
                'debit' => $dr,
                'credit' => $cr,
            ];
        });

        if ($this->hideZero) {
            $rows = $rows->filter(fn($r) => abs($r->debit) > 0.0001 || abs($r->credit) > 0.0001)->values();
        }
        $totals = [
            'debit' => round($rows->sum('debit'), 2),
            'credit' => round($rows->sum('credit'), 2),
        ];
        return [
            'rows' => $rows,
            'totals' => $totals,
            'baseCurrency' => Currency::getBaseCurrency(),
            'imbalanced' => abs($totals['debit'] - $totals['credit']) > 0.01,
            'difference' => round($totals['debit'] - $totals['credit'], 2),
        ];
    }
    public function setThisMonth(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
    }

    public function setThisQuarter(): void
    {
        $this->startDate = now()->startOfQuarter()->format('Y-m-d');
        $this->endDate = now()->endOfQuarter()->format('Y-m-d');
    }

    public function setYTD(): void
    {
        $this->startDate = now()->startOfYear()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }
}; ?>

<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 via-blue-600 to-indigo-600 bg-clip-text text-transparent">Trial Balance</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Debits and credits by account for the selected period.</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start Date</label>
                <input type="date" wire:model.live="startDate" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">End Date</label>
                <input type="date" wire:model.live="endDate" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white" />
            </div>
            <div class="flex items-end gap-2">
                <button wire:click="setThisMonth" class="px-3 py-2 text-xs rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">This Month</button>
                <button wire:click="setThisQuarter" class="px-3 py-2 text-xs rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">This Quarter</button>
                <button wire:click="setYTD" class="px-3 py-2 text-xs rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">YTD</button>
            </div>
            <div class="md:col-span-1 flex items-end justify-end gap-3">
                <button wire:click="$toggle('hideZero')" class="px-4 py-2 rounded-lg border {{ $hideZero ? 'bg-emerald-50 border-emerald-300 text-emerald-700 dark:bg-emerald-900/20 dark:border-emerald-700 dark:text-emerald-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white border-gray-300 dark:border-gray-600' }}">{{ $hideZero ? '✓ Hide Zeros' : 'Show Zeros' }}</button>
                <a href="/reports/trial-balance/export/csv?start_date={{ $startDate }}&end_date={{ $endDate }}" class="px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white">Export CSV</a>
                <a href="/reports/trial-balance/print?start_date={{ $startDate }}&end_date={{ $endDate }}" target="_blank" class="px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white">Print</a>
            </div>
        </div>
    </div>

    @if($imbalanced)
        <div class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200 border border-red-200 dark:border-red-800">
            ⚠ Debits and Credits differ by {{ $baseCurrency->symbol ?? '' }} {{ number_format(abs($difference), 2) }}. Investigate postings or periods.
        </div>
    @else
        <div class="p-4 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200 border border-green-200 dark:border-green-800">✓ Debits equal Credits</div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-purple-500 to-indigo-600 text-white">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold uppercase">Code</th>
                    <th class="px-6 py-4 text-left text-xs font-bold uppercase">Account</th>
                    <th class="px-6 py-4 text-left text-xs font-bold uppercase">Type</th>
                    <th class="px-6 py-4 text-right text-xs font-bold uppercase">Debit</th>
                    <th class="px-6 py-4 text-right text-xs font-bold uppercase">Credit</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-900 dark:text-gray-100">
                @forelse($rows as $r)
                    <tr>
                        <td class="px-6 py-3 font-mono text-sm font-bold">{{ $r->code }}</td>
                        <td class="px-6 py-3">{{ $r->name }}</td>
                        <td class="px-6 py-3">{{ ucfirst($r->type) }}</td>
                        <td class="px-6 py-3 text-right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($r->debit, 2) }}</td>
                        <td class="px-6 py-3 text-right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($r->credit, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">No data for selected period</td>
                    </tr>
                @endforelse
                </tbody>
                <tfoot>
                <tr class="bg-gray-50 dark:bg-gray-900/30 text-gray-900 dark:text-gray-100">
                    <th colspan="3" class="px-6 py-4 text-right">Totals</th>
                    <th class="px-6 py-4 text-right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($totals['debit'], 2) }}</th>
                    <th class="px-6 py-4 text-right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($totals['credit'], 2) }}</th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
