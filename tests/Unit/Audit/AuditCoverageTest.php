<?php

use App\Models\Account;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\Program;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createAccount(string $code, string $type, ?string $name = null): Account
{
    return Account::firstOrCreate(
        ['code' => $code],
        [
            'name' => $name ?? $code . ' Account',
            'type' => $type,
            'is_active' => true,
        ]
    );
}

function createProgram(): Program
{
    $code = 'PRG-' . strtoupper(substr(uniqid(), -5));

    return Program::create([
        'name' => 'Program A',
        'code' => $code,
        'start_date' => now()->toDateString(),
        'status' => 'active',
    ]);
}

it('computes expense payment status and outstanding balance', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $expenseAccount = createAccount('5000', 'expense', 'Office Rent');
    createAccount('2000', 'liability', 'Accounts Payable');
    $paymentAccount = createAccount('1000', 'asset', 'Cash on Hand');

    $expense = Expense::create([
        'program_id' => createProgram()->id,
        'account_id' => $expenseAccount->id,
        'expense_date' => now()->toDateString(),
        'amount' => 100,
        'charges' => 10,
        'currency' => 'UGX',
        'description' => 'Rent',
    ]);

    expect($expense->payment_status)->toBe('unpaid');
    expect($expense->outstanding_balance)->toBe(110.0);

    Payment::create([
        'expense_id' => $expense->id,
        'payment_date' => now()->toDateString(),
        'payment_account_id' => $paymentAccount->id,
        'amount' => 50,
    ]);

    $expense->refresh();
    expect($expense->payment_status)->toBe('partial');
    expect($expense->outstanding_balance)->toBe(60.0);

    Payment::create([
        'expense_id' => $expense->id,
        'payment_date' => now()->toDateString(),
        'payment_account_id' => $paymentAccount->id,
        'amount' => 60,
    ]);

    $expense->refresh();
    expect($expense->payment_status)->toBe('paid');
    expect($expense->outstanding_balance)->toBe(0.0);
});

it('creates payment voucher journal entry with correct lines', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $expenseAccount = createAccount('5000', 'expense', 'Office Rent');
    $accountsPayable = createAccount('2000', 'liability', 'Accounts Payable');
    $paymentAccount = createAccount('1100', 'asset', 'Bank Account - Main');

    $expense = Expense::create([
        'program_id' => createProgram()->id,
        'account_id' => $expenseAccount->id,
        'expense_date' => now()->toDateString(),
        'amount' => 200,
        'charges' => 0,
        'currency' => 'UGX',
        'description' => 'Supplies',
    ]);

    $payment = Payment::create([
        'expense_id' => $expense->id,
        'payment_date' => now()->toDateString(),
        'payment_account_id' => $paymentAccount->id,
        'amount' => 200,
    ]);

    $entry = JournalEntry::where('payment_id', $payment->id)->first();

    expect($entry)->not()->toBeNull();
    expect($entry->isBalanced())->toBeTrue();

    $apLine = $entry->lines()->where('account_id', $accountsPayable->id)->first();
    $bankLine = $entry->lines()->where('account_id', $paymentAccount->id)->first();

    expect((float) ($apLine?->debit ?? 0))->toBe(200.0);
    expect((float) ($apLine?->credit ?? 0))->toBe(0.0);
    expect((float) ($bankLine?->debit ?? 0))->toBe(0.0);
    expect((float) ($bankLine?->credit ?? 0))->toBe(200.0);
});

it('respects sales document posting rules', function () {
    $sale = new Sale();

    $sale->document_type = Sale::DOC_ESTIMATE;
    expect($sale->postsToLedger())->toBeFalse();

    $sale->document_type = Sale::DOC_QUOTATION;
    expect($sale->postsToLedger())->toBeFalse();

    $sale->document_type = Sale::DOC_SALES_ORDER;
    expect($sale->postsToLedger())->toBeFalse();

    $sale->document_type = Sale::DOC_INVOICE;
    expect($sale->postsToLedger())->toBeTrue();

    $sale->document_type = Sale::DOC_TILL_SALE;
    expect($sale->postsToLedger())->toBeTrue();
});

it('creates sales journal entry for posting documents', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $bank = createAccount('1100', 'asset', 'Bank Account - Main');
    $accountsReceivable = createAccount('1200', 'asset', 'Accounts Receivable');
    $incomeAccount = createAccount('4000', 'income', 'Program Fees');

    $program = createProgram();
    $customer = Customer::create(['name' => 'Customer A']);

    $unpaidSale = Sale::create([
        'program_id' => $program->id,
        'customer_id' => $customer->id,
        'account_id' => $incomeAccount->id,
        'document_type' => Sale::DOC_INVOICE,
        'invoice_number' => 'INV-1001',
        'sale_date' => now()->toDateString(),
        'amount' => 300,
        'currency' => 'UGX',
        'status' => Sale::STATUS_UNPAID,
        'description' => 'Invoice sale',
    ]);

    $unpaidEntry = JournalEntry::where('sales_id', $unpaidSale->id)->first();
    expect($unpaidEntry)->not()->toBeNull();
    expect($unpaidEntry->isBalanced())->toBeTrue();
    expect((float) $unpaidEntry->lines()->where('account_id', $accountsReceivable->id)->value('debit'))->toBe(300.0);

    $paidSale = Sale::create([
        'program_id' => $program->id,
        'customer_id' => $customer->id,
        'account_id' => $incomeAccount->id,
        'document_type' => Sale::DOC_TILL_SALE,
        'invoice_number' => 'INV-1002',
        'sale_date' => now()->toDateString(),
        'amount' => 150,
        'currency' => 'UGX',
        'status' => Sale::STATUS_PAID,
        'description' => 'Till sale',
    ]);

    $paidEntry = JournalEntry::where('sales_id', $paidSale->id)->first();
    expect($paidEntry)->not()->toBeNull();
    expect((float) $paidEntry->lines()->where('account_id', $bank->id)->value('debit'))->toBe(150.0);
});

it('rejects unbalanced journal entries', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $debitAccount = createAccount('1000', 'asset', 'Cash on Hand');
    $creditAccount = createAccount('4000', 'income', 'Income');

    $create = fn () => JournalEntry::createEntry(
        [
            'date' => now()->toDateString(),
            'type' => 'adjustment',
            'description' => 'Unbalanced test',
            'created_by' => $user->id,
            'status' => 'posted',
        ],
        [
            ['account_id' => $debitAccount->id, 'debit' => 100, 'credit' => 0],
            ['account_id' => $creditAccount->id, 'debit' => 0, 'credit' => 80],
        ]
    );

    expect($create)->toThrow(Exception::class);
});
