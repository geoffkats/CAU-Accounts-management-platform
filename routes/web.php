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
    
    // Sales / Income
    Volt::route('sales', 'sales.index')->name('sales.index');
    Volt::route('sales/create', 'sales.create')->name('sales.create');
    Volt::route('sales/{id}', 'sales.show')->name('sales.show');
    
    // Expenses
    Volt::route('expenses', 'expenses.index')->name('expenses.index');
    Volt::route('expenses/create', 'expenses.create')->name('expenses.create');
    
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
    Volt::route('reports/profit-loss', 'reports.profit-loss')->name('reports.profit-loss');
    Volt::route('reports/expense-breakdown', 'reports.expense-breakdown')->name('reports.expense-breakdown');
    Volt::route('reports/sales-by-program', 'reports.sales-by-program')->name('reports.sales-by-program');
    Volt::route('reports/currency-conversion', 'reports.currency-conversion')->name('reports.currency-conversion');
    
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
    
    // Settings (admin/accountant only)
    Volt::route('settings/company', 'settings.company')->middleware(['role:admin'])->name('settings.company');
    Volt::route('settings/currencies', 'settings.currencies')->middleware(['role:admin'])->name('settings.currencies');
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
    Route::get('reports/profit-loss/export/csv', function () {
        $programId = request('program_id');
        $start = request('start_date') ?: now()->startOfMonth()->toDateString();
        $end = request('end_date') ?: now()->endOfMonth()->toDateString();
        $programs = \App\Models\Program::query()->when($programId, fn($q) => $q->where('id', $programId))->get();
        $rows = [];
        foreach ($programs as $program) {
            $income = $program->sales()->whereBetween('sale_date', [$start, $end])->sum('amount');
            $expenses = $program->expenses()->whereBetween('expense_date', [$start, $end])->sum('amount');
            $profit = $income - $expenses;
            $margin = $income > 0 ? ($profit / $income) * 100 : 0;
            $rows[] = [$program->name, $program->code, $income, $expenses, $profit, round($margin, 2)];
        }
        $out = fopen('php://temp', 'w+');
        fputcsv($out, ['Program','Code','Income','Expenses','Profit','Margin %']);
        foreach ($rows as $r) { fputcsv($out, $r); }
        rewind($out);
        return response(stream_get_contents($out), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="profit-loss-' . now()->format('Y-m-d') . '.csv"',
        ]);
    })->name('reports.profit-loss.export.csv');

    Route::get('reports/profit-loss/print', function () {
        $programId = request('program_id');
        $start = request('start_date') ?: now()->startOfMonth()->toDateString();
        $end = request('end_date') ?: now()->endOfMonth()->toDateString();
        $programs = \App\Models\Program::query()->when($programId, fn($q) => $q->where('id', $programId))->get();
        $reportData = $programs->map(function ($program) use ($start, $end) {
            $income = $program->sales()->whereBetween('sale_date', [$start, $end])->sum('amount');
            $expenses = $program->expenses()->whereBetween('expense_date', [$start, $end])->sum('amount');
            $profit = $income - $expenses;
            return [
                'program' => $program->name,
                'code' => $program->code,
                'income' => $income,
                'expenses' => $expenses,
                'profit' => $profit,
                'margin' => $income > 0 ? ($profit / $income) * 100 : 0,
            ];
        })->all();
        return view('print.profit-loss', [
            'rows' => $reportData,
            'start' => $start,
            'end' => $end,
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
