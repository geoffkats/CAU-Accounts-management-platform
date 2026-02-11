<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "All Opening Balance Journals:\n";
$journals = App\Models\JournalEntry::where('type', 'opening_balance')
    ->orderBy('id', 'desc')
    ->get();

foreach ($journals as $j) {
    $linesCount = App\Models\JournalEntryLine::where('journal_entry_id', $j->id)->count();
    echo sprintf(
        "ID: %d | Status: %s | Date: %s | Lines: %d\n",
        $j->id,
        $j->status,
        $j->date,
        $linesCount
    );
    
    if ($linesCount > 0) {
        $lines = App\Models\JournalEntryLine::where('journal_entry_id', $j->id)
            ->with('account')
            ->take(3)
            ->get();
        foreach ($lines as $line) {
            echo sprintf(
                "  -> %s: DR=%.2f CR=%.2f\n",
                $line->account->code ?? 'N/A',
                $line->debit,
                $line->credit
            );
        }
    }
    echo "\n";
}
