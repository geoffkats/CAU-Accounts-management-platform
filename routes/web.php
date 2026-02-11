<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Volt::route('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'role:admin,accountant'])->group(function () {
    // Programs
    Volt::route('programs', 'programs.index')->name('programs.index');
    Volt::route('programs/create', 'programs.create')->name('programs.create');
    
    // Accounts (Chart of Accounts)
    Volt::route('accounts', 'accounts.index')->name('accounts.index');
    Volt::route('accounts/create', 'accounts.create')->name('accounts.create');
    Volt::route('accounts/{id}/edit', 'accounts.edit')->name('accounts.edit');
    // Opening Balances Wizard
    Volt::route('accounts/opening-balances', 'accounts.opening-balances')->name('accounts.opening-balances');
    
    // Sales / Income
    Volt::route('sales', 'sales.index')->name('sales.index');
    Volt::route('sales/create', 'sales.create')->name('sales.create');
    Volt::route('sales/{id}', 'sales.show')->name('sales.show');
    Volt::route('sales/{id}/edit', 'sales.edit')->name('sales.edit');
    Route::get('sales/{id}/print', function ($id) {
        $sale = \App\Models\Sale::with(['program','customer','account'])->findOrFail($id);
        $settings = \App\Models\CompanySetting::first();
        $currency = \App\Models\Currency::getBaseCurrency();

        $docType = strtolower(trim((string) $sale->document_type));
        $docPrefix = strtoupper(strtok((string) $sale->invoice_number, '-'));
        $typeFromNumber = match ($docPrefix) {
            'EST' => \App\Models\Sale::DOC_ESTIMATE,
            'QUO' => \App\Models\Sale::DOC_QUOTATION,
            'SO' => \App\Models\Sale::DOC_SALES_ORDER,
            'TILL' => \App\Models\Sale::DOC_TILL_SALE,
            default => null,
        };
        $knownTypes = [
            \App\Models\Sale::DOC_INVOICE,
            \App\Models\Sale::DOC_ESTIMATE,
            \App\Models\Sale::DOC_QUOTATION,
            \App\Models\Sale::DOC_SALES_ORDER,
            \App\Models\Sale::DOC_TILL_SALE,
            'sales-order',
            'sales order',
            'till-sale',
            'till sale',
        ];

        $resolvedType = $docType;
        if (!in_array($docType, $knownTypes, true)) {
            $resolvedType = $typeFromNumber ?? '';
        }

        if ($docType === \App\Models\Sale::DOC_INVOICE && $typeFromNumber) {
            $resolvedType = $typeFromNumber;
        }

        $view = match ($resolvedType) {
            \App\Models\Sale::DOC_ESTIMATE => 'print.sales-estimate',
            \App\Models\Sale::DOC_QUOTATION => 'print.sales-quotation',
            \App\Models\Sale::DOC_SALES_ORDER, 'sales-order', 'sales order' => 'print.sales-order',
            \App\Models\Sale::DOC_TILL_SALE, 'till-sale', 'till sale' => 'print.till-sale-receipt',
            default => 'print.sale-invoice',
        };

        return view($view, compact('sale', 'settings', 'currency'));
    })->name('sales.print');
    
    // Expenses
    Volt::route('expenses', 'expenses.index')->name('expenses.index');
    Volt::route('expenses/create', 'expenses.create')->name('expenses.create');
    Volt::route('expenses/outstanding', 'expenses.outstanding-report')->name('expenses.outstanding');
    Volt::route('expenses/{id}', 'expenses.show')->name('expenses.show');
    Volt::route('expenses/{id}/edit', 'expenses.edit')->name('expenses.edit');
    
    // Payment Vouchers
    Volt::route('payment-vouchers', 'payment-vouchers.index')->name('payment-vouchers.index');
    Volt::route('payment-vouchers/{id}', 'payment-vouchers.show')->name('payment-vouchers.show');
    Volt::route('payment-vouchers/{id}/edit', 'payment-vouchers.edit')->name('payment-vouchers.edit');
    Route::get('payment-vouchers/create/{expense?}', \App\Livewire\PaymentVoucher::class)->name('payment-vouchers.create');
    Route::get('expenses/{id}/payment-voucher', \App\Livewire\PaymentVoucher::class)->name('expenses.payment-voucher');
    
    // Payment Voucher PDF
    Route::get('payment-vouchers/{id}/pdf', function($id) {
        $payment = \App\Models\Payment::with(['expense.vendor', 'expense.staff', 'expense.program', 'paymentAccount'])->findOrFail($id);
        $settings = \App\Models\CompanySetting::first();
        $currency = \App\Models\Currency::getBaseCurrency();
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.payment-voucher', compact('payment', 'settings', 'currency'));
        return $pdf->download('payment-voucher-' . $payment->voucher_number . '.pdf');
    })->name('payment-vouchers.pdf');
    
    // Vendors
    Volt::route('vendors', 'vendors.index')->name('vendors.index');
    Volt::route('vendors/create', 'vendors.create')->name('vendors.create');
    Volt::route('vendors/{vendor}/edit', 'vendors.edit')->name('vendors.edit');
    
    // Vendor Invoices (Accounts Payable)
    Volt::route('vendor-invoices', 'vendor-invoices.index')->name('vendor-invoices.index');
    Volt::route('vendor-invoices/create', 'vendor-invoices.create')->name('vendor-invoices.create');
    Volt::route('vendor-invoices/{id}', 'vendor-invoices.show')->name('vendor-invoices.show');
    
    // Customers
    Volt::route('customers', 'customers.index')->name('customers.index');
    Volt::route('customers/create', 'customers.create')->name('customers.create');
    Volt::route('customers/{customer}/edit', 'customers.edit')->name('customers.edit');
    
    // Staff Management (admin only)
    Volt::route('staff', 'staff.index')->middleware(['role:admin'])->name('staff.index');
    Volt::route('staff/create', 'staff.create')->middleware(['role:admin'])->name('staff.create');
    Volt::route('staff/edit/{id}', 'staff.create')->middleware(['role:admin'])->name('staff.edit');
    Volt::route('staff/assignments/{id}', 'staff.assignments')->middleware(['role:admin'])->name('staff.assignments');
    
    // Payroll (admin only)
    Volt::route('payroll', 'payroll.index')->middleware(['role:admin'])->name('payroll.index');
    Volt::route('payroll/create', 'payroll.create')->middleware(['role:admin'])->name('payroll.create');
    Volt::route('payroll/{id}', 'payroll.show')->middleware(['role:admin'])->name('payroll.show');
    
    // Asset Management
    Volt::route('assets', 'assets.index')->name('assets.index');
    Volt::route('assets/create', 'assets.create')->name('assets.create');
    Volt::route('assets/{id}', 'assets.show')->name('assets.show');
    Volt::route('assets/{id}/edit', 'assets.create')->name('assets.edit');
    Volt::route('maintenance', 'assets.maintenance')->name('maintenance.index');
    Volt::route('assets/{id}/maintenance', 'assets.maintenance')->name('assets.maintenance');
    
    // Asset Categories
    Volt::route('asset-categories', 'asset-categories.index')->name('asset-categories.index');
    
    // Students & Fees (Accounts Receivable)
    Volt::route('students', 'students.index')->name('students.index');
    Volt::route('students/create', 'students.create')->name('students.create');
    Volt::route('students/{id}', 'students.show')->name('students.show');
    Volt::route('students/{id}/edit', 'students.create')->name('students.edit');
    
    // Fee Structures
    Volt::route('fees', 'fees.index')->name('fees.index');
    Volt::route('fees/create', 'fees.create')->name('fees.create');
    Volt::route('fees/{id}/edit', 'fees.create')->name('fees.edit');
    
    // Student Invoices
    Volt::route('invoices', 'invoices.index')->name('invoices.index');
    Volt::route('invoices/create', 'invoices.create')->name('invoices.create');
    Volt::route('invoices/bulk-generate', 'invoices.bulk-generate')->name('invoices.bulk-generate');
    Volt::route('invoices/{id}', 'invoices.show')->name('invoices.show');
    Volt::route('invoices/{id}/edit', 'invoices.create')->name('invoices.edit');
    
    // Student Payments
    Volt::route('payments', 'payments.index')->name('payments.index');
    Volt::route('payments/create', 'payments.create')->name('payments.create');
    Volt::route('payments/{id}', 'payments.show')->name('payments.show');
    
    // Scholarships
    Volt::route('scholarships', 'scholarships.index')->name('scholarships.index');
    Volt::route('scholarships/create', 'scholarships.create')->name('scholarships.create');
    Volt::route('scholarships/{id}/edit', 'scholarships.create')->name('scholarships.edit');
    
    // Reports
    Volt::route('general-ledger', 'general-ledger')->name('general-ledger');
    Volt::route('reports/balance-sheet', 'balance-sheet')->name('reports.balance-sheet');
    Volt::route('reports/profit-loss', 'reports.profit-loss')->name('reports.profit-loss');
    Volt::route('reports/expense-breakdown', 'reports.expense-breakdown')->name('reports.expense-breakdown');
    Volt::route('reports/sales-by-program', 'reports.sales-by-program')->name('reports.sales-by-program');
    Volt::route('reports/currency-conversion', 'reports.currency-conversion')->name('reports.currency-conversion');
    
    // Balance Sheet Print View
    Route::get('reports/balance-sheet/print', function () {
        $asOfDate = request('as_of_date') ?: now()->toDateString();
        $showComparative = request('show_comparative', '1') === '1';
        
        $start = '1900-01-01';
        $end = $asOfDate;
        $settings = \App\Models\CompanySetting::get();
        $asOfLabel = \Carbon\Carbon::parse($end)->format($settings->date_format ?? 'Y-m-d');
        $priorEnd = \Carbon\Carbon::parse($end)->subYear()->toDateString();
        $priorLabel = \Carbon\Carbon::parse($priorEnd)->format($settings->date_format ?? 'Y-m-d');

        $assets = \App\Models\Account::active()->ofType('asset')->orderBy('code')->get();
        $liabilities = \App\Models\Account::active()->ofType('liability')->orderBy('code')->get();
        $equity = \App\Models\Account::active()->ofType('equity')->orderBy('code')->get();
        $income = \App\Models\Account::active()->ofType('income')->orderBy('code')->get();
        $expenses = \App\Models\Account::active()->ofType('expense')->orderBy('code')->get();

        $assetRows = $assets->map(fn($a) => [
            'id' => $a->id,
            'code' => $a->code, 'name' => $a->name,
            'category' => $a->category,
            'balance' => round($a->calculateBalance($start, $end), 2),
            'prior' => round($a->calculateBalance($start, $priorEnd), 2),
        ])->filter(fn($r) => abs($r['balance']) > 0.0001 || abs($r['prior']) > 0.0001)->values();

        $liabilityRows = $liabilities->map(fn($a) => [
            'id' => $a->id,
            'code' => $a->code, 'name' => $a->name,
            'category' => $a->category,
            'balance' => round($a->calculateBalance($start, $end), 2),
            'prior' => round($a->calculateBalance($start, $priorEnd), 2),
        ])->filter(fn($r) => abs($r['balance']) > 0.0001 || abs($r['prior']) > 0.0001)->values();

        $equityRows = $equity->map(fn($a) => [
            'id' => $a->id,
            'code' => $a->code, 'name' => $a->name,
            'balance' => round($a->calculateBalance($start, $end), 2),
            'prior' => round($a->calculateBalance($start, $priorEnd), 2),
        ])->filter(fn($r) => abs($r['balance']) > 0.0001 || abs($r['prior']) > 0.0001)->values();

        $isFixedAsset = fn($row) => $row['category'] === 'long_term'
            ? true
            : ($row['category'] === 'short_term'
                ? false
                : (int) $row['code'] >= 1500);
        $isLongTermLiability = fn($row) => $row['category'] === 'long_term'
            ? true
            : ($row['category'] === 'short_term'
                ? false
                : ((int) $row['code'] >= 2500 || str_contains(strtolower($row['name'] ?? ''), 'loan') || str_contains(strtolower($row['name'] ?? ''), 'long')));

        $fixedAssets = $assetRows->filter($isFixedAsset)->values();
        $currentAssets = $assetRows->reject($isFixedAsset)->values();
        $longTermLiabilities = $liabilityRows->filter($isLongTermLiability)->values();
        $shortTermLiabilities = $liabilityRows->reject($isLongTermLiability)->values();

        $totalIncome = round($income->sum(fn($a) => $a->calculateBalance($start, $end)), 2);
        $totalExpenses = round($expenses->sum(fn($a) => $a->calculateBalance($start, $end)), 2);
        $netIncome = round($totalIncome - $totalExpenses, 2);

        $priorIncome = round($income->sum(fn($a) => $a->calculateBalance($start, $priorEnd)), 2);
        $priorExpenses = round($expenses->sum(fn($a) => $a->calculateBalance($start, $priorEnd)), 2);
        $netIncomePrior = round($priorIncome - $priorExpenses, 2);

        $totalAssets = round(array_sum(array_column($assetRows->all(), 'balance')), 2);
        $totalLiabilities = round(array_sum(array_column($liabilityRows->all(), 'balance')), 2);
        $totalEquity = round(array_sum(array_column($equityRows->all(), 'balance')), 2);
        $equityWithEarnings = round($totalEquity + $netIncome, 2);

        $totalAssetsPrior = round(array_sum(array_column($assetRows->all(), 'prior')), 2);
        $totalLiabilitiesPrior = round(array_sum(array_column($liabilityRows->all(), 'prior')), 2);
        $totalEquityPrior = round(array_sum(array_column($equityRows->all(), 'prior')), 2);
        $equityWithEarningsPrior = round($totalEquityPrior + $netIncomePrior, 2);

        $totalFixedAssets = round(array_sum(array_column($fixedAssets->all(), 'balance')), 2);
        $totalCurrentAssets = round(array_sum(array_column($currentAssets->all(), 'balance')), 2);
        $totalFixedAssetsPrior = round(array_sum(array_column($fixedAssets->all(), 'prior')), 2);
        $totalCurrentAssetsPrior = round(array_sum(array_column($currentAssets->all(), 'prior')), 2);

        $totalLongTermLiabilities = round(array_sum(array_column($longTermLiabilities->all(), 'balance')), 2);
        $totalShortTermLiabilities = round(array_sum(array_column($shortTermLiabilities->all(), 'balance')), 2);
        $totalLongTermLiabilitiesPrior = round(array_sum(array_column($longTermLiabilities->all(), 'prior')), 2);
        $totalShortTermLiabilitiesPrior = round(array_sum(array_column($shortTermLiabilities->all(), 'prior')), 2);

        $baseCurrency = \App\Models\Currency::getBaseCurrency();

        return view('print.balance-sheet', compact(
            'fixedAssets','currentAssets','longTermLiabilities','shortTermLiabilities','equityRows',
            'totalFixedAssets','totalCurrentAssets','totalLongTermLiabilities','totalShortTermLiabilities',
            'totalFixedAssetsPrior','totalCurrentAssetsPrior','totalLongTermLiabilitiesPrior','totalShortTermLiabilitiesPrior',
            'totalAssets','totalLiabilities','totalEquity','netIncome','equityWithEarnings',
            'totalAssetsPrior','totalLiabilitiesPrior','totalEquityPrior','netIncomePrior','equityWithEarningsPrior',
            'baseCurrency','settings','asOfLabel','priorLabel','showComparative'
        ));
    })->name('reports.balance-sheet.print');
    
    // General Ledger & Accounting
    Volt::route('general-ledger', 'general-ledger-detailed')->name('general-ledger');
    Volt::route('trial-balance', 'trial-balance')->name('trial-balance');
    Volt::route('account-statement/{id}', 'account-statement')->name('account-statement');
    Volt::route('cashbook', 'cashbook.index')->name('cashbook.index');
    
    // Cashbook Exports
    Route::get('cashbook/export/pdf', function() {
        $accountId = request('account_id');
        $startDate = request('start_date');
        $endDate = request('end_date');
        $transactionType = request('transaction_type', 'all');
        
        $account = \App\Models\Account::findOrFail($accountId);
        $settings = \App\Models\CompanySetting::first();
        $currency = \App\Models\Currency::getBaseCurrency();
        
        $openingBalance = $account->calculateBalance('1900-01-01', \Carbon\Carbon::parse($startDate)->subDay()->format('Y-m-d'));
        
        $query = \App\Models\JournalEntryLine::where('account_id', $accountId)
            ->whereHas('journalEntry', function($q) use ($startDate, $endDate) {
                $q->where('status', 'posted')->whereBetween('date', [$startDate, $endDate]);
            });
        
        if ($transactionType === 'receipts') $query->where('debit', '>', 0);
        if ($transactionType === 'payments') $query->where('credit', '>', 0);
        
        $transactions = $query->with(['journalEntry'])
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->orderBy('journal_entries.date')->select('journal_entry_lines.*')->get();
        
        $runningBalance = $openingBalance;
        $transactions = $transactions->map(function($t) use (&$runningBalance) {
            $t->running_balance = $runningBalance + $t->debit - $t->credit;
            $runningBalance = $t->running_balance;
            return $t;
        });
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.cashbook', compact('account', 'transactions', 'openingBalance', 'runningBalance', 'startDate', 'endDate', 'settings', 'currency'));
        return $pdf->download('cashbook-' . $account->code . '-' . now()->format('Y-m-d') . '.pdf');
    })->name('cashbook.export.pdf');
    
    Route::get('cashbook/export/excel', function() {
        $accountId = request('account_id');
        $startDate = request('start_date');
        $endDate = request('end_date');
        $transactionType = request('transaction_type', 'all');
        
        $account = \App\Models\Account::findOrFail($accountId);
        $openingBalance = $account->calculateBalance('1900-01-01', \Carbon\Carbon::parse($startDate)->subDay()->format('Y-m-d'));
        
        $query = \App\Models\JournalEntryLine::where('account_id', $accountId)
            ->whereHas('journalEntry', function($q) use ($startDate, $endDate) {
                $q->where('status', 'posted')->whereBetween('date', [$startDate, $endDate]);
            });
        
        if ($transactionType === 'receipts') $query->where('debit', '>', 0);
        if ($transactionType === 'payments') $query->where('credit', '>', 0);
        
        $transactions = $query->with(['journalEntry'])
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->orderBy('journal_entries.date')->select('journal_entry_lines.*')->get();
        
        $out = fopen('php://temp', 'w+');
        fputcsv($out, ['Date', 'Description', 'Reference', 'Receipts', 'Payments', 'Balance']);
        fputcsv($out, ['', 'Opening Balance', '', '', '', number_format($openingBalance, 2)]);
        
        $runningBalance = $openingBalance;
        foreach ($transactions as $t) {
            $runningBalance += $t->debit - $t->credit;
            fputcsv($out, [
                $t->journalEntry->date->format('Y-m-d'),
                $t->description,
                $t->journalEntry->reference,
                $t->debit > 0 ? number_format($t->debit, 2) : '',
                $t->credit > 0 ? number_format($t->credit, 2) : '',
                number_format($runningBalance, 2)
            ]);
        }
        
        fputcsv($out, ['', 'Closing Balance', '', '', '', number_format($runningBalance, 2)]);
        rewind($out);
        
        return response(stream_get_contents($out), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="cashbook-' . $account->code . '-' . now()->format('Y-m-d') . '.csv"',
        ]);
    })->name('cashbook.export.excel');
    
    Route::get('cashbook/print', function() {
        $accountId = request('account_id');
        $startDate = request('start_date');
        $endDate = request('end_date');
        $transactionType = request('transaction_type', 'all');
        
        $account = \App\Models\Account::findOrFail($accountId);
        $settings = \App\Models\CompanySetting::first();
        $currency = \App\Models\Currency::getBaseCurrency();
        
        $openingBalance = $account->calculateBalance('1900-01-01', \Carbon\Carbon::parse($startDate)->subDay()->format('Y-m-d'));
        
        $query = \App\Models\JournalEntryLine::where('account_id', $accountId)
            ->whereHas('journalEntry', function($q) use ($startDate, $endDate) {
                $q->where('status', 'posted')->whereBetween('date', [$startDate, $endDate]);
            });
        
        if ($transactionType === 'receipts') $query->where('debit', '>', 0);
        if ($transactionType === 'payments') $query->where('credit', '>', 0);
        
        $transactions = $query->with(['journalEntry'])
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->orderBy('journal_entries.date')->select('journal_entry_lines.*')->get();
        
        $runningBalance = $openingBalance;
        $transactions = $transactions->map(function($t) use (&$runningBalance) {
            $t->running_balance = $runningBalance + $t->debit - $t->credit;
            $runningBalance = $t->running_balance;
            return $t;
        });
        
        return view('print.cashbook', compact('account', 'transactions', 'openingBalance', 'runningBalance', 'startDate', 'endDate', 'settings', 'currency', 'transactionType'));
    })->name('cashbook.print');
    
    // Journal Entries
    Volt::route('journal-entries', 'journal-entries.index')->name('journal-entries.index');
    Volt::route('journal-entries/create', 'journal-entries.create')->name('journal-entries.create');
    Volt::route('journal-entries/{id}/edit', 'journal-entries.edit')->name('journal-entries.edit');
    Volt::route('journal-entries/{id}', 'journal-entries.show')->name('journal-entries.show');
    
    // Budgets - specific routes first, then parameterized routes
    Volt::route('budgets', 'budgets.index')->name('budgets.index');
    Volt::route('budgets/create', 'budgets.create')->name('budgets.create');
    Volt::route('budgets/alerts', 'budgets.alerts')->name('budgets.alerts');
    
    // Budget Reallocations - must come before {id} routes
    Volt::route('budgets/reallocations', 'budgets.reallocations.index')->name('budgets.reallocations.index');
    Volt::route('budgets/reallocations/create', 'budgets.reallocations.create')->name('budgets.reallocations.create');
    
    // Budget parameterized routes - must come last
    Volt::route('budgets/{id}', 'budgets.show')->name('budgets.show');
    Volt::route('budgets/{id}/edit', 'budgets.create')->name('budgets.edit');
    
    // AI Assistant
    Volt::route('ai-assistant', 'ai-assistant-page')->name('ai-assistant');

    // Settings (admin/accountant only)
    Volt::route('settings/company', 'settings.company')->middleware(['role:admin'])->name('settings.company');
    Volt::route('settings/currencies', 'settings.currencies')->middleware(['role:admin'])->name('settings.currencies');
});

// User Management - admin only
Route::middleware(['auth', 'role:admin'])->group(function () {
    Volt::route('settings/users', 'users.index')->name('users.index');
    Volt::route('settings/users/create', 'users.create')->name('users.create');
    Volt::route('settings/users/{id}/edit', 'users.create')->name('users.edit');
});

// Audit Trail - admin only
Route::middleware(['auth', 'role:admin'])->group(function () {
    Volt::route('settings/audit', 'settings.audit')->name('settings.audit');
    Volt::route('settings/audit/verify', 'settings.audit-verify')->name('settings.audit.verify');

    // Audit exports
    Route::get('settings/audit/export/csv', function () {
        $query = \App\Models\ActivityLog::with('user')
            ->when(request('action') && request('action') !== 'all', fn($q) => $q->where('action', request('action')))
            ->when(request('model') && request('model') !== 'all', fn($q) => $q->where('model_type', request('model')))
            ->when(request('userId'), fn($q) => $q->where('user_id', request('userId')))
            ->when(request('startDate'), fn($q) => $q->whereDate('created_at', '>=', request('startDate')))
            ->when(request('endDate'), fn($q) => $q->whereDate('created_at', '<=', request('endDate')))
            ->latest();

        $rows = $query->get();
        $out = fopen('php://temp', 'w+');
        fputcsv($out, ['Date','User','Action','Model','Model ID','Changed Fields','IP','URL','Hash']);
        foreach ($rows as $log) {
            $changed = implode(', ', array_keys(($log->changes['after'] ?? []) ?: []));
            fputcsv($out, [
                $log->created_at->format('Y-m-d H:i'),
                $log->user->name ?? 'System',
                $log->action,
                class_basename($log->model_type),
                $log->model_id,
                $changed,
                $log->ip_address,
                $log->url,
                $log->hash,
            ]);
        }
        rewind($out);
        return response(stream_get_contents($out), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="audit-logs-' . now()->format('Y-m-d') . '.csv"',
        ]);
    })->name('settings.audit.export.csv');

    Route::get('settings/audit/print', function () {
        $query = \App\Models\ActivityLog::with('user')
            ->when(request('action') && request('action') !== 'all', fn($q) => $q->where('action', request('action')))
            ->when(request('model') && request('model') !== 'all', fn($q) => $q->where('model_type', request('model')))
            ->when(request('userId'), fn($q) => $q->where('user_id', request('userId')))
            ->when(request('startDate'), fn($q) => $q->whereDate('created_at', '>=', request('startDate')))
            ->when(request('endDate'), fn($q) => $q->whereDate('created_at', '<=', request('endDate')))
            ->latest();
        $logs = $query->get();
        return view('print.audit', compact('logs'));
    })->name('settings.audit.print');
});

// Report exports (filtered by query string)
Route::middleware(['auth', 'role:admin,accountant'])->group(function () {
    Route::get('reports/trial-balance/export/csv', function () {
        $start = request('start_date') ?: now()->startOfMonth()->toDateString();
        $end = request('end_date') ?: now()->endOfMonth()->toDateString();
        $accounts = \App\Models\Account::active()->orderBy('code')->get();
        $rows = [];
        foreach ($accounts as $a) {
            $q = $a->journalEntryLines()->whereHas('journalEntry', function ($q) use ($start, $end) {
                $q->where('status', 'posted')->whereBetween('date', [$start, $end]);
            });
            $debits = (float) $q->sum('debit');
            $credits = (float) $q->sum('credit');
            $net = round($debits - $credits, 2);
            $dr = $net > 0 ? $net : 0.0;
            $cr = $net < 0 ? abs($net) : 0.0;
            if (abs($dr) < 0.005 && abs($cr) < 0.005) { continue; }
            $rows[] = [$a->code, $a->name, $dr, $cr];
        }
        $out = fopen('php://temp', 'w+');
        fputcsv($out, ['Code','Account','Debit','Credit']);
        foreach ($rows as $r) { fputcsv($out, $r); }
        $totDr = array_sum(array_column($rows, 2));
        $totCr = array_sum(array_column($rows, 3));
        fputcsv($out, ['Totals','', $totDr, $totCr]);
        rewind($out);
        return response(stream_get_contents($out), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="trial-balance-' . now()->format('Y-m-d') . '.csv"',
        ]);
    })->name('reports.trial-balance.export.csv');

    Route::get('reports/trial-balance/print', function () {
        $start = request('start_date') ?: now()->startOfMonth()->toDateString();
        $end = request('end_date') ?: now()->endOfMonth()->toDateString();
        $accounts = \App\Models\Account::active()->orderBy('code')->get();
        $rows = [];
        foreach ($accounts as $a) {
            $q = $a->journalEntryLines()->whereHas('journalEntry', function ($q) use ($start, $end) {
                $q->where('status', 'posted')->whereBetween('date', [$start, $end]);
            });
            $debits = (float) $q->sum('debit');
            $credits = (float) $q->sum('credit');
            $net = round($debits - $credits, 2);
            $dr = $net > 0 ? $net : 0.0;
            $cr = $net < 0 ? abs($net) : 0.0;
            if (abs($dr) < 0.005 && abs($cr) < 0.005) { continue; }
            $rows[] = ['code' => $a->code, 'name' => $a->name, 'debit' => $dr, 'credit' => $cr];
        }
        $totals = [
            'debit' => array_sum(array_map(fn($r) => $r['debit'], $rows)),
            'credit' => array_sum(array_map(fn($r) => $r['credit'], $rows)),
        ];
        return view('print.trial-balance', compact('rows','start','end','totals'));
    })->name('reports.trial-balance.print');
    Route::get('reports/profit-loss/export/csv', function () {
        $period = request('period', 'month');
        $asOf = request('as_of') ?: now()->toDateString();
        $asOfDate = \Carbon\Carbon::parse($asOf);
        if ($period === 'quarter') {
            $start = $asOfDate->copy()->startOfQuarter()->toDateString();
            $end = $asOfDate->copy()->endOfQuarter()->toDateString();
        } elseif ($period === 'year') {
            $start = $asOfDate->copy()->startOfYear()->toDateString();
            $end = $asOfDate->copy()->endOfYear()->toDateString();
        } else {
            $start = $asOfDate->copy()->startOfMonth()->toDateString();
            $end = $asOfDate->copy()->endOfMonth()->toDateString();
        }

        $incomeAccounts = \App\Models\Account::active()->ofType('income')->orderBy('code')->get();
        $expenseAccounts = \App\Models\Account::active()->ofType('expense')->orderBy('code')->get();

        $isCost = fn($a) => str_starts_with((string) $a->code, '52');
        $salesRows = $incomeAccounts->map(fn($a) => [
            'code' => $a->code,
            'name' => $a->name,
            'amount' => round($a->calculateBalance($start, $end), 2),
        ])->filter(fn($r) => abs($r['amount']) > 0.005)->values()->all();

        $costRows = $expenseAccounts->filter($isCost)->map(fn($a) => [
            'code' => $a->code,
            'name' => $a->name,
            'amount' => round($a->calculateBalance($start, $end), 2),
        ])->filter(fn($r) => abs($r['amount']) > 0.005)->values()->all();

        $expenseRows = $expenseAccounts->reject($isCost)->map(fn($a) => [
            'code' => $a->code,
            'name' => $a->name,
            'amount' => round($a->calculateBalance($start, $end), 2),
        ])->filter(fn($r) => abs($r['amount']) > 0.005)->values()->all();

        $totalSales = round(array_sum(array_column($salesRows, 'amount')), 2);
        $totalCost = round(array_sum(array_column($costRows, 'amount')), 2);
        $grossProfit = round($totalSales - $totalCost, 2);
        $totalExpenses = round(array_sum(array_column($expenseRows, 'amount')), 2);
        $netProfit = round($grossProfit - $totalExpenses, 2);

        $out = fopen('php://temp', 'w+');
        fputcsv($out, ['Section','Code','Name','Amount']);
        foreach ($salesRows as $r) { fputcsv($out, ['Sales', $r['code'], $r['name'], $r['amount']]); }
        fputcsv($out, ['Sales Total','','', $totalSales]);
        foreach ($costRows as $r) { fputcsv($out, ['Cost of Sales', $r['code'], $r['name'], $r['amount']]); }
        fputcsv($out, ['Cost of Sales Total','','', $totalCost]);
        fputcsv($out, ['Gross Profit','','', $grossProfit]);
        foreach ($expenseRows as $r) { fputcsv($out, ['Expenses', $r['code'], $r['name'], $r['amount']]); }
        fputcsv($out, ['Expenses Total','','', $totalExpenses]);
        fputcsv($out, ['Net Profit/Loss','','', $netProfit]);
        rewind($out);
        return response(stream_get_contents($out), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="profit-loss-' . now()->format('Y-m-d') . '.csv"',
        ]);
    })->name('reports.profit-loss.export.csv');

    Route::get('reports/profit-loss/print', function () {
        $period = request('period', 'month');
        $asOf = request('as_of') ?: now()->toDateString();
        $asOfDate = \Carbon\Carbon::parse($asOf);
        if ($period === 'quarter') {
            $start = $asOfDate->copy()->startOfQuarter()->toDateString();
            $end = $asOfDate->copy()->endOfQuarter()->toDateString();
        } elseif ($period === 'year') {
            $start = $asOfDate->copy()->startOfYear()->toDateString();
            $end = $asOfDate->copy()->endOfYear()->toDateString();
        } else {
            $start = $asOfDate->copy()->startOfMonth()->toDateString();
            $end = $asOfDate->copy()->endOfMonth()->toDateString();
        }

        $incomeAccounts = \App\Models\Account::active()->ofType('income')->orderBy('code')->get();
        $expenseAccounts = \App\Models\Account::active()->ofType('expense')->orderBy('code')->get();
        $isCost = fn($a) => str_starts_with((string) $a->code, '52');

        $salesRows = $incomeAccounts->map(fn($a) => [
            'code' => $a->code,
            'name' => $a->name,
            'amount' => round($a->calculateBalance($start, $end), 2),
        ])->filter(fn($r) => abs($r['amount']) > 0.005)->values()->all();

        $costRows = $expenseAccounts->filter($isCost)->map(fn($a) => [
            'code' => $a->code,
            'name' => $a->name,
            'amount' => round($a->calculateBalance($start, $end), 2),
        ])->filter(fn($r) => abs($r['amount']) > 0.005)->values()->all();

        $expenseRows = $expenseAccounts->reject($isCost)->map(fn($a) => [
            'code' => $a->code,
            'name' => $a->name,
            'amount' => round($a->calculateBalance($start, $end), 2),
        ])->filter(fn($r) => abs($r['amount']) > 0.005)->values()->all();

        $totalSales = round(array_sum(array_column($salesRows, 'amount')), 2);
        $totalCost = round(array_sum(array_column($costRows, 'amount')), 2);
        $grossProfit = round($totalSales - $totalCost, 2);
        $totalExpenses = round(array_sum(array_column($expenseRows, 'amount')), 2);
        $netProfit = round($grossProfit - $totalExpenses, 2);

        $baseCurrency = \App\Models\Currency::getBaseCurrency();

        return view('print.profit-loss', [
            'periodStart' => $start,
            'periodEnd' => $end,
            'salesRows' => $salesRows,
            'costRows' => $costRows,
            'expenseRows' => $expenseRows,
            'totalSales' => $totalSales,
            'totalCost' => $totalCost,
            'grossProfit' => $grossProfit,
            'totalExpenses' => $totalExpenses,
            'netProfit' => $netProfit,
            'baseCurrency' => $baseCurrency,
        ]);
    })->name('reports.profit-loss.print');

    Route::get('reports/expense-breakdown/export/csv', function () {
        $groupBy = request('groupBy', 'category');
        $programId = request('program_id');
        $start = request('start_date') ?: now()->startOfMonth()->toDateString();
        $end = request('end_date') ?: now()->endOfMonth()->toDateString();
        $query = \App\Models\Expense::with(['program','vendor'])
            ->whereBetween('expense_date', [$start, $end])
            ->when($programId, fn($q) => $q->where('program_id', $programId));
        if ($groupBy === 'program') {
            $items = $query->select('program_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as amount'))->groupBy('program_id')->orderByDesc('amount')->get()
                ->map(fn($i) => ['name' => optional(\App\Models\Program::find($i->program_id))->name ?? 'N/A','count'=>$i->count,'amount'=>$i->amount]);
        } elseif ($groupBy === 'vendor') {
            $items = $query->select('vendor_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as amount'))->groupBy('vendor_id')->orderByDesc('amount')->get()
                ->map(fn($i) => ['name' => optional($i->vendor)->name ?? 'No Vendor','count'=>$i->count,'amount'=>$i->amount]);
        } else {
            $items = $query->select('category', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as amount'))->groupBy('category')->orderByDesc('amount')->get()
                ->map(fn($i) => ['name' => $i->category ?: 'Uncategorized','count'=>$i->count,'amount'=>$i->amount]);
        }
        $out = fopen('php://temp', 'w+');
        fputcsv($out, [ucfirst($groupBy),'Count','Amount']);
        foreach ($items as $row) { fputcsv($out, [$row['name'], $row['count'], $row['amount']]); }
        rewind($out);
        return response(stream_get_contents($out), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="expense-breakdown-' . now()->format('Y-m-d') . '.csv"',
        ]);
    })->name('reports.expense-breakdown.export.csv');

    Route::get('reports/expense-breakdown/print', function () {
        $groupBy = request('groupBy', 'category');
        $programId = request('program_id');
        $start = request('start_date') ?: now()->startOfMonth()->toDateString();
        $end = request('end_date') ?: now()->endOfMonth()->toDateString();
        $query = \App\Models\Expense::with(['program','vendor'])
            ->whereBetween('expense_date', [$start, $end])
            ->when($programId, fn($q) => $q->where('program_id', $programId));
        if ($groupBy === 'program') {
            $rows = $query->select('program_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as amount'))->groupBy('program_id')->orderByDesc('amount')->get()
                ->map(fn($i) => ['name' => optional(\App\Models\Program::find($i->program_id))->name ?? 'N/A','count'=>$i->count,'amount'=>$i->amount]);
        } elseif ($groupBy === 'vendor') {
            $rows = $query->select('vendor_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as amount'))->groupBy('vendor_id')->orderByDesc('amount')->get()
                ->map(fn($i) => ['name' => optional($i->vendor)->name ?? 'No Vendor','count'=>$i->count,'amount'=>$i->amount]);
        } else {
            $rows = $query->select('category', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as amount'))->groupBy('category')->orderByDesc('amount')->get()
                ->map(fn($i) => ['name' => $i->category ?: 'Uncategorized','count'=>$i->count,'amount'=>$i->amount]);
        }
        $total = max(collect($rows)->sum('amount'), 1);
        return view('print.expense-breakdown', compact('rows','groupBy','start','end','total'));
    })->name('reports.expense-breakdown.print');
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
