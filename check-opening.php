<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$journal = App\Models\JournalEntry::where('type', 'opening_balance')
    ->where('status', 'posted')
    ->with('lines.account')
    ->latest()
    ->first();

if ($journal) {
    echo "Opening Balance Journal Found:\n";
    echo "ID: {$journal->id}\n";
    echo "Date: {$journal->date}\n";
    echo "Lines: {$journal->lines->count()}\n\n";
    
    echo "Sample lines:\n";
    foreach ($journal->lines->take(5) as $line) {
        echo sprintf(
            "  %s - %s | Debit: %.2f | Credit: %.2f\n",
            $line->account->code,
            $line->account->name,
            $line->debit,
            $line->credit
        );
    }
} else {
    echo "No opening balance journal found\n";
}
