<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\JournalEntry;

// Find all journal entries that were accidentally voided
// Types: payment, customer_payment (they don't have accrual logic)
$entries = JournalEntry::whereIn('type', ['payment', 'customer_payment'])
    ->where('status', 'void')
    ->where('voided_at', '>=', now()->subMinutes(30))
    ->get();

echo "Found " . $entries->count() . " voided payments to restore.\n";

foreach ($entries as $entry) {
    echo "Restoring: " . $entry->reference . " (" . $entry->type . ")\n";
    $entry->update([
        'status' => 'posted',
        'voided_at' => null
    ]);
}
echo "✓ Done restoring accidental voids.\n";
