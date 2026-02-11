<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\JournalEntry;
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
    public ?int $existingOpeningId = null;
    public bool $showVoidConfirm = false;
    public ?int $focusAccountId = null;
    public ?Account $focusAccount = null;

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
        $focusId = (int) request()->query('account_id');
        $this->focusAccountId = $focusId > 0 ? $focusId : null;
        if ($this->focusAccountId) {
            $this->focusAccount = Account::active()->find($this->focusAccountId);
            if (!$this->focusAccount) {
                $this->focusAccountId = null;
            }
        }
        // Find the latest opening balance journal that actually has lines (ignore empty ones)
        $existing = JournalEntry::where('type', 'opening_balance')
            ->where('status', 'posted')
            ->has('lines') // Only journals with lines
            ->with('lines.account')
            ->orderByDesc('id')
            ->first();
        
        $this->existingOpeningId = $existing?->id;
        
        // Seed with active accounts (no children) for quick entry
        $accountQuery = Account::active()->orderBy('code');
        if ($this->focusAccountId) {
            $accountQuery->where('id', $this->focusAccountId);
        }
        $accounts = $accountQuery->get();
        $this->rows = $accounts->map(function ($a) use ($existing) {
            $debit = 0;
            $credit = 0;
            
            // If opening balance exists, populate from existing journal lines
            if ($existing) {
                $line = $existing->lines->firstWhere('account_id', $a->id);
                if ($line) {
                    $debit = (float) $line->debit;
                    $credit = (float) $line->credit;
                }
            }
            
            return [
                'account_id' => $a->id,
                'code' => $a->code,
                'name' => $a->name,
                'type' => $a->type,
                'debit' => $debit,
                'credit' => $credit,
            ];
        })->values()->all();
        
        // Set the date from existing journal if present
        if ($existing) {
            $this->date = $existing->date->format('Y-m-d');
        }
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

    /**
     * Auto-zero the opposite side when user edits debit/credit to avoid invalid lines.
     */
    public function updated($name, $value): void
    {
        if (preg_match('/^rows\.(\d+)\.(debit|credit)$/', (string) $name, $m)) {
            $idx = (int) $m[1];
            $field = $m[2];
            $val = (float) ($value ?? 0);
            $this->rows[$idx][$field] = round(max(0, $val), 2);
            if ($field === 'debit' && $this->rows[$idx]['debit'] > 0) {
                $this->rows[$idx]['credit'] = 0;
            }
            if ($field === 'credit' && $this->rows[$idx]['credit'] > 0) {
                $this->rows[$idx]['debit'] = 0;
            }
        }
    }

    public function post(): void
    {
        $this->validate([
            'date' => 'required|date',
        ]);

        $lines = [];
        foreach ($this->rows as $i => $r) {
            $debit = (float) ($r['debit'] ?? 0);
            $credit = (float) ($r['credit'] ?? 0);
            if ($debit > 0 && $credit > 0) {
                $this->addError('rows.'.$i.'.debit', 'Enter either debit or credit, not both.');
                return;
            }
            if ($debit == 0 && $credit == 0) continue;
            $lines[] = [
                'account_id' => (int) $r['account_id'],
                'debit' => round($debit, 2),
                'credit' => round($credit, 2),
            ];
        }

        // Opening balances should only be posted once - use Adjust Balances if corrections needed
        if ($this->existingOpeningId) {
            $this->addError('rows', 'Opening Balances have already been posted. Use "Adjust Balances" button above to make corrections.');
            return;
        }

        $service = app(OpeningBalanceService::class);
        try {
            $entry = $service->postOpeningBalances($this->date, $lines, auth()->id());
        } catch (\Throwable $e) {
            $this->addError('rows', $e->getMessage());
            return;
        }

        // Redirect to the posted journal review page (best accounting practice)
        session()->flash('success', 'Opening Balances posted successfully. Review the journal below.');
        $this->redirect(route('journal-entries.show', ['id' => $entry->id]), navigate: true);
    }

    public function openHelp(): void { $this->showHelp = true; }
    public function closeHelp(): void { $this->showHelp = false; }
    public function openVoidConfirm(): void { $this->showVoidConfirm = true; }
    public function closeVoidConfirm(): void { $this->showVoidConfirm = false; }

    public function with(): array
    {
        $base = Currency::getBaseCurrency();
        $totDr = array_sum(array_map(fn($r) => (float) ($r['debit'] ?? 0), $this->rows));
        $totCr = array_sum(array_map(fn($r) => (float) ($r['credit'] ?? 0), $this->rows));
        $accountQuery = Account::active()->orderBy('code');
        if ($this->focusAccountId) {
            $accountQuery->where('id', $this->focusAccountId);
        }
        return [
            'baseCurrency' => $base,
            'totalDebits' => round($totDr, 2),
            'totalCredits' => round($totCr, 2),
            'difference' => round($totDr - $totCr, 2),
            'accounts' => $accountQuery->get(['id','code','name','type']),
            'existingOpeningId' => $this->existingOpeningId,
            'focusAccount' => $this->focusAccount,
        ];
    }

    public function voidOnly(): void
    {
        if (!$this->existingOpeningId) {
            $this->addError('rows', 'No Opening Balance journal to void.');
            return;
        }
        try {
            $entry = JournalEntry::findOrFail($this->existingOpeningId);
            $entry->void();
            $this->existingOpeningId = null;
            $this->showVoidConfirm = false;
            session()->flash('success', 'Opening Balance journal voided. You can now enter corrected opening balances.');
        } catch (\Throwable $e) {
            $this->addError('rows', 'Failed to void Opening Balance journal: '.$e->getMessage());
        }
    }

    public function voidAndRepost(): void
    {
        if (!$this->existingOpeningId) {
            $this->addError('rows', 'No Opening Balance journal to void.');
            return;
        }
        // Void first
        try {
            $entry = JournalEntry::findOrFail($this->existingOpeningId);
            $entry->void();
            $this->existingOpeningId = null;
        } catch (\Throwable $e) {
            $this->addError('rows', 'Failed to void Opening Balance journal: '.$e->getMessage());
            return;
        }

        // Then repost current lines
        $this->showVoidConfirm = false;
        $this->post();
    }
}; ?>

<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 via-blue-600 to-indigo-600 bg-clip-text text-transparent">
            Opening Balances
        </h1>
        <div class="flex items-center gap-3 mt-1">
            <p class="text-gray-600 dark:text-gray-400">Enter opening balances by account and post a single balancing journal.</p>
            <button wire:click="openHelp" class="px-3 py-1.5 text-sm rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">Getting Started</button>
        </div>
    </div>

    @if ($focusAccount)
        <div class="p-4 rounded-lg bg-indigo-50 text-indigo-800 border border-indigo-200 flex items-center justify-between">
            <div class="text-sm">
                Showing opening balance for <strong>{{ $focusAccount->code }} - {{ $focusAccount->name }}</strong>.
            </div>
            <a href="{{ route('accounts.opening-balances') }}" class="text-sm font-semibold text-indigo-700 hover:text-indigo-900">Show all accounts</a>
        </div>
    @endif

    @if ($existingOpeningId)
        <div class="p-4 rounded-lg bg-amber-50 text-amber-800 border border-amber-200">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <strong>Opening Balances Already Posted</strong>
                    </div>
                    <p class="text-sm mb-1">Opening balances should typically only be set once when first starting the system or at the beginning of a new financial year.</p>
                    <p class="text-sm font-semibold">⚠️ Changing opening balances will impact: Trial Balance, Balance Sheet, P&L, and all financial reports. Proceed with caution, ideally with an accountant.</p>
                </div>
                <div class="flex items-center gap-2 ml-4">
                    <a href="{{ route('journal-entries.show', ['id' => $existingOpeningId]) }}"
                       class="inline-flex items-center px-3 py-1.5 rounded bg-amber-600 text-white hover:bg-amber-700 whitespace-nowrap">View Journal</a>
                    <button wire:click="openVoidConfirm" class="inline-flex items-center px-3 py-1.5 rounded bg-red-600 text-white hover:bg-red-700 whitespace-nowrap">Adjust Balances</button>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 space-y-4">
        @if ($errors->any())
            <div class="p-3 rounded-lg bg-red-50 text-red-700 border border-red-200 text-sm">
                {{ $errors->first() }}
            </div>
        @endif
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date</label>
                <input type="date" wire:model.live="date" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"/>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Import CSV (code,debit,credit)</label>
                <input type="file" wire:model="csv" accept=".csv,text/csv" class="w-full" />
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Headers: code,debit,credit (or code,amount,side)</p>
            </div>
            <div class="flex items-end">
                @if(!$existingOpeningId)
                    <button wire:click="post" class="inline-flex items-center px-4 py-2 rounded-lg bg-purple-600 text-white font-semibold hover:bg-purple-700">
                        Post Opening Balances
                    </button>
                @else
                    <a href="{{ route('journal-entries.show', ['id' => $existingOpeningId]) }}"
                       class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-700 text-white font-semibold hover:bg-gray-800">View Opening Journal</a>
                @endif
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
                            <input type="text" wire:model.live="rows.{{ $idx }}.code" disabled class="w-24 bg-gray-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-300 px-2 py-1 rounded" />
                        </td>
                        <td class="px-4 py-2">
                            <input type="text" wire:model.live="rows.{{ $idx }}.name" disabled class="w-full bg-gray-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-300 px-2 py-1 rounded" />
                        </td>
                        <td class="px-4 py-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ ucfirst($row['type']) }}</span>
                        </td>
                        <td class="px-4 py-2 text-right">
                            <input type="number" step="any" min="0" wire:model.blur="rows.{{ $idx }}.debit" 
                                   placeholder="0.00"
                                   class="w-36 px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-right focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" />
                        </td>
                        <td class="px-4 py-2 text-right">
                            <input type="number" step="any" min="0" wire:model.blur="rows.{{ $idx }}.credit" 
                                   placeholder="0.00"
                                   class="w-36 px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-right focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" />
                        </td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                <tr class="bg-gray-50 dark:bg-gray-900/30 text-gray-900 dark:text-gray-100 font-semibold">
                    <th colspan="3" class="px-4 py-3 text-right">Totals</th>
                    <th class="px-4 py-3 text-right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($totalDebits, 2) }}</th>
                    <th class="px-4 py-3 text-right">{{ $baseCurrency->symbol ?? '' }} {{ number_format($totalCredits, 2) }}</th>
                </tr>
                <tr class="border-t-2 {{ abs($difference) < 0.01 ? 'border-green-500' : 'border-amber-500' }}">
                    <th colspan="3" class="px-4 py-3 text-right text-sm font-medium text-gray-700 dark:text-gray-300">Difference (Dr - Cr)</th>
                    <th colspan="2" class="px-4 py-3 text-right text-lg font-bold {{ abs($difference) < 0.01 ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}">
                        {{ $baseCurrency->symbol ?? '' }} {{ number_format($difference, 2) }}
                    </th>
                </tr>
                @if(abs($difference) >= 0.01)
                <tr class="bg-amber-50 dark:bg-amber-900/20">
                    <td colspan="5" class="px-4 py-3 text-center text-sm">
                        <div class="flex items-center justify-center gap-2 text-amber-800 dark:text-amber-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span><strong>Auto-balancing:</strong> A {{ $difference > 0 ? 'credit' : 'debit' }} of {{ $baseCurrency->symbol ?? '' }}{{ number_format(abs($difference), 2) }} will be added to <strong>Opening Balance Equity (3999)</strong> to balance the entry.</span>
                        </div>
                    </td>
                </tr>
                @else
                <tr class="bg-green-50 dark:bg-green-900/20">
                    <td colspan="5" class="px-4 py-3 text-center text-sm">
                        <div class="flex items-center justify-center gap-2 text-green-800 dark:text-green-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span><strong>Balanced!</strong> Debits equal credits. Ready to post.</span>
                        </div>
                    </td>
                </tr>
                @endif
                </tfoot>
            </table>
        </div>

        <div class="p-4 rounded-lg bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-sm text-blue-800 dark:text-blue-300">
                    <p class="font-semibold mb-1">Accounting Rule: Debits Must Equal Credits</p>
                    <p>If your debits don't equal credits, the system will automatically add a balancing line to <strong>Opening Balance Equity (Account 3999)</strong>. This temporary account should ideally have a zero balance once all opening balances are correctly entered. You can then close it to Retained Earnings.</p>
                </div>
            </div>
        </div>
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
                    <li><strong>Accounting Rule:</strong> Debits must equal credits. If not balanced, the system adds a line to <strong>Opening Balance Equity (3999)</strong> automatically.</li>
                    <li>Click "Post Opening Balances" to create the journal entry (button is always enabled - auto-balancing handles differences).</li>
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

    <!-- Void & Repost Confirmation Modal -->
    @if($showVoidConfirm)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ open: @entangle('showVoidConfirm') }" x-show="open" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75" 
                 @click="open=false; $wire.closeVoidConfirm()"></div>

            <div class="relative inline-block w-full max-w-2xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-2xl">
                <div class="flex items-center gap-3 mb-4">
                    <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Adjust Opening Balances - Important Warning</h3>
                </div>
                
                <div class="space-y-3 text-sm text-gray-700 dark:text-gray-300">
                    <p class="font-semibold text-red-600 dark:text-red-400">⚠️ Changing opening balances has significant implications:</p>
                    <ul class="list-disc ml-6 space-y-1">
                        <li>This will <strong>void</strong> the existing Opening Balance journal (preserves audit trail)</li>
                        <li>All financial reports (Trial Balance, Balance Sheet, P&L) will be affected</li>
                        <li>Bank reconciliations may need to be redone</li>
                        <li>Tax calculations and liabilities may be misstated if incorrect</li>
                        <li>The "Opening Balance Equity" account balance should be zero after corrections</li>
                    </ul>
                    
                    <div class="mt-4 p-3 rounded bg-blue-50 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                        <strong>Best Practice:</strong> Consult with your accountant or bookkeeper before making changes to ensure all accounts remain balanced and accurate.
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-2">
                    <button class="px-4 py-2 rounded bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white hover:bg-gray-300" 
                            @click="open=false; $wire.closeVoidConfirm()">Cancel</button>
                    <button wire:click="voidOnly" 
                            class="px-4 py-2 rounded bg-amber-600 text-white hover:bg-amber-700">Void Only (Manual Re-entry)</button>
                    <button wire:click="voidAndRepost" 
                            class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700">Void & Repost Current Values</button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
