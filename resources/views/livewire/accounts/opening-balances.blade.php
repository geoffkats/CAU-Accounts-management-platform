<?php

use App\Models\Account;
use App\Models\Currency;
use App\Services\OpeningBalanceService;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public string $date = '';
    public array $rows = [];
    public $csv = null; // uploaded file
    public bool $showHelp = false;

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
        // Seed with active accounts (no children) for quick entry
        $accounts = Account::active()->orderBy('code')->get();
        $this->rows = $accounts->map(function ($a) {
            return [
                'account_id' => $a->id,
                'code' => $a->code,
                'name' => $a->name,
                'type' => $a->type,
                'debit' => 0,
                'credit' => 0,
            ];
        })->values()->all();
    }

    public function updatedCsv(): void
    {
        $this->validate([
            'csv' => 'file|mimes:csv,txt|max:2048',
        ]);

        $path = $this->csv->store('tmp');
        $full = Storage::path($path);
        $handle = fopen($full, 'r');
        $rows = [];
        $header = null;
        while (($data = fgetcsv($handle)) !== false) {
            if (!$header) { $header = array_map('strtolower', $data); continue; }
            $row = array_combine($header, $data);
            if (!$row) continue;
            // Expect columns: code, debit, credit (or amount, side)
            $code = trim($row['code'] ?? '');
            if ($code === '') continue;
            $account = Account::where('code', $code)->first();
            if (!$account) continue;
            $debit = (float) ($row['debit'] ?? 0);
            $credit = (float) ($row['credit'] ?? 0);
            if (isset($row['amount']) && isset($row['side'])) {
                $amt = (float) $row['amount'];
                if (strtolower(trim($row['side'])) === 'debit' || strtolower(trim($row['side'])) === 'dr') {
                    $debit = $amt; $credit = 0;
                } else {
                    $credit = $amt; $debit = 0;
                }
            }
            $rows[] = [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'debit' => round(max(0, $debit), 2),
                'credit' => round(max(0, $credit), 2),
            ];
        }
        fclose($handle);
        $this->rows = $rows;
    }

    public function post(): void
    {
        $this->validate([
            'date' => 'required|date',
        ]);

        $lines = [];
        foreach ($this->rows as $r) {
            $debit = (float) ($r['debit'] ?? 0);
            $credit = (float) ($r['credit'] ?? 0);
            if ($debit == 0 && $credit == 0) continue;
            $lines[] = [
                'account_id' => (int) $r['account_id'],
                'debit' => round($debit, 2),
                'credit' => round($credit, 2),
            ];
        }

        $service = app(OpeningBalanceService::class);
        $entry = $service->postOpeningBalances($this->date, $lines, auth()->id());

        session()->flash('success', 'Opening balances posted as journal '.$entry->reference);
        // Refresh rows to zero to prevent duplicate posting
        $this->mount();
    }

    public function openHelp(): void { $this->showHelp = true; }
    public function closeHelp(): void { $this->showHelp = false; }

    public function with(): array
    {
        $base = Currency::getBaseCurrency();
        $totDr = array_sum(array_map(fn($r) => (float) ($r['debit'] ?? 0), $this->rows));
        $totCr = array_sum(array_map(fn($r) => (float) ($r['credit'] ?? 0), $this->rows));
        return [
            'baseCurrency' => $base,
            'totalDebits' => round($totDr, 2),
            'totalCredits' => round($totCr, 2),
            'difference' => round($totDr - $totCr, 2),
            'accounts' => Account::active()->orderBy('code')->get(['id','code','name','type']),
        ];
    }
}; ?>

<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 via-blue-600 to-indigo-600 bg-clip-text text-transparent">
            Opening Balances
        </h1>
        <div class="flex items-center gap-3 mt-1">
            <p class="text-gray-600 dark:text-gray-400">Enter opening balances by account and post a single balancing journal.</p>
            <button wire:click="openHelp" class="px-3 py-1.5 text-sm rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-200">Getting Started</button>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 rounded-lg bg-green-50 text-green-800 border border-green-200">{{ session('success') }}</div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date</label>
                <input type="date" wire:model.live="date" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"/>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Import CSV (code,debit,credit)</label>
                <input type="file" wire:model="csv" accept=".csv,text/csv" class="w-full" />
                <p class="text-xs text-gray-500 mt-1">Headers: code,debit,credit (or code,amount,side)</p>
            </div>
            <div class="flex items-end">
                <button wire:click="post" class="inline-flex items-center px-4 py-2 rounded-lg bg-purple-600 text-white font-semibold hover:bg-purple-700 disabled:opacity-50"
                        @disabled(abs($difference) >= 0.01)>
                    Post Opening Balances
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-purple-500 to-indigo-600 text-white">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase">Code</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase">Account</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase">Type</th>
                    <th class="px-4 py-3 text-right text-xs font-bold uppercase">Debit</th>
                    <th class="px-4 py-3 text-right text-xs font-bold uppercase">Credit</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($rows as $idx => $row)
                    <tr>
                        <td class="px-4 py-2">
                            <input type="hidden" wire:model.live="rows.{{ $idx }}.account_id" />
                            <input type="text" wire:model.live="rows.{{ $idx }}.code" disabled class="w-24 bg-gray-50 dark:bg-gray-700/50 px-2 py-1 rounded" />
                        </td>
                        <td class="px-4 py-2">
                            <input type="text" wire:model.live="rows.{{ $idx }}.name" disabled class="w-full bg-gray-50 dark:bg-gray-700/50 px-2 py-1 rounded" />
                        </td>
                        <td class="px-4 py-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ ucfirst($row['type']) }}</span>
                        </td>
                        <td class="px-4 py-2 text-right">
                            <input type="number" step="0.01" min="0" wire:model.live="rows.{{ $idx }}.debit" class="w-32 px-2 py-1 border rounded text-right" />
                        </td>
                        <td class="px-4 py-2 text-right">
                            <input type="number" step="0.01" min="0" wire:model.live="rows.{{ $idx }}.credit" class="w-32 px-2 py-1 border rounded text-right" />
                        </td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                <tr class="bg-gray-50 dark:bg-gray-900/30">
                    <th colspan="3" class="px-4 py-3 text-right">Totals</th>
                    <th class="px-4 py-3 text-right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($totalDebits, 2) }}</th>
                    <th class="px-4 py-3 text-right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($totalCredits, 2) }}</th>
                </tr>
                <tr>
                    <th colspan="3" class="px-4 py-3 text-right">Difference (Dr - Cr)</th>
                    <th colspan="2" class="px-4 py-3 text-right {{ abs($difference) < 0.01 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $baseCurrency->symbol ?? '' }} {{ number_format($difference, 2) }}
                    </th>
                </tr>
                </tfoot>
            </table>
        </div>

        <p class="text-sm text-gray-500">A balancing line to <strong>Opening Balance Equity</strong> will be added automatically if needed.</p>
    </div>

    <!-- Help Modal -->
    @if($showHelp)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showHelp') }" x-show="show" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <!-- Backdrop -->
            <div class="fixed inset-0 transition-opacity bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75" 
                 wire:click="closeHelp"></div>

            <!-- Modal -->
            <div class="relative inline-block w-full max-w-2xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-2xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Getting Started: Opening Balances</h3>
                    <button wire:click="closeHelp" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <ol class="list-decimal ms-5 space-y-2 text-sm text-gray-700 dark:text-gray-300">
                    <li>Choose your opening balance date (e.g., start of fiscal period).</li>
                    <li>Enter base currency debits for Assets and Expenses; credits for Liabilities, Equity, and Income.</li>
                    <li>Optionally import a CSV with headers <code>code,debit,credit</code> or <code>code,amount,side</code>.</li>
                    <li>The system enforces debits = credits by adding a balancing line to <strong>Opening Balance Equity</strong>.</li>
                    <li>Click “Post Opening Balances” to create a single immutable journal.</li>
                </ol>

                <div class="mt-4 p-3 rounded-lg bg-blue-50 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 text-sm">
                    Tip: You can still bulk import via CLI: <code>php artisan accounts:opening-balances --file storage\\app\\opening_balances.csv --date YYYY-MM-DD --currency BASE</code>
                </div>

                <div class="flex gap-3 mt-6">
                    <button wire:click="closeHelp"
                            class="px-4 py-2 bg-gray-800 text-white rounded-lg">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
