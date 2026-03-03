<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\JournalEntry;
use App\Models\Expense;

$expense = Expense::where('description', 'like', '%Filling PAYE%')
    ->where('expense_date', '2025-12-29')
    ->first();

if (!$expense) {
    die("Expense not found.\n");
}

echo "Expense ID: " . $expense->id . "\n";
echo "Amount: " . $expense->amount . "\n";
echo "Charges: " . $expense->charges . "\n";
echo "Amount Base: " . $expense->amount_base . "\n";

$entries = JournalEntry::where('expense_id', $expense->id)
    ->with('lines')
    ->get();

foreach ($entries as $entry) {
    echo "Entry Reference: " . $entry->reference . " Status: " . $entry->status . "\n";
    foreach ($entry->lines as $line) {
        echo "  Account: " . $line->account_id . " Debit: " . $line->debit . " Credit: " . $line->credit . "\n";
    }
}
