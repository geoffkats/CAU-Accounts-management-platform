# Expense Creditor Change - Duplicate Payables Issue

## Issue Description

When editing an expense and changing the creditor (e.g., from Anitah to Scolar), the system appeared to show duplicate payables in the general journal - one for the old creditor and one for the new creditor.

## Root Cause Analysis

The system uses a **void-and-recreate** pattern for journal entry updates:

1. When an expense is updated, the `Expense::updated()` observer:
   - Finds the existing journal entry
   - Calls `void()` to mark it as voided (sets `status = 'void'` and `voided_at = timestamp`)
   - Creates a new journal entry with updated information
   - Links them via `replaces_entry_id` for audit trail

2. The voided entry remains in the database for audit purposes but is excluded from all financial calculations

## Why This is Actually Correct Behavior

### Financial Reports Are Accurate
All balance calculations and financial reports properly filter by `status = 'posted'`:
- Trial Balance
- General Ledger
- Balance Sheet
- Profit & Loss
- Cashbook
- Accounts Payable balance

The `Account::calculateBalance()` method explicitly filters:
```php
$query->whereHas('journalEntry', function ($q) {
    $q->where('status', 'posted');
});
```

### The Confusion Source
The issue only appears when viewing the **Journal Entries index page** with the status filter set to **"All Status"** instead of "Posted". In this view, both entries are visible:
- Voided entry (old creditor) - status: void
- Active entry (new creditor) - status: posted

## Solution Implemented

### 1. Visual Distinction for Voided Entries
Enhanced the Journal Entries index page to make voided entries clearly distinguishable:
- Reduced opacity (50%) for voided entry rows
- Gray background for voided entries
- Strikethrough text on date and reference
- Added "Replacement" badge when an entry replaces another

### 2. User Education
Added an informational notice when viewing "All Status":
```
Note: Showing all entries including voided ones. Voided entries (marked with strikethrough) 
are excluded from all financial reports and balance calculations. They represent historical 
records that have been replaced or corrected.
```

### 3. Audit Trail Preservation
The system maintains a complete audit trail:
- Voided entries are never deleted
- `replaces_entry_id` links old and new entries
- `voided_at` timestamp records when the entry was voided
- Users can view the full history of changes

## How to Verify the Fix

### Test Scenario
1. Create an expense for 2,000 with Anitah as creditor
2. Check Accounts Payable balance - should show 2,000
3. Edit the expense and change creditor to Scolar
4. Check Accounts Payable balance - should still show 2,000 (not 4,000)

### Viewing Journal Entries
1. Go to Journal Entries page
2. Set status filter to "Posted" (default) - only see the active entry with Scolar
3. Set status filter to "All Status" - see both entries, but voided one is clearly marked

### Checking Reports
1. View Trial Balance - Accounts Payable shows correct balance
2. View General Ledger for Accounts Payable - only posted entries appear
3. View Balance Sheet - Accounts Payable shows correct amount

## Technical Details

### Database Schema
```sql
journal_entries:
  - status: enum('draft', 'posted', 'void')
  - voided_at: timestamp (nullable)
  - replaces_entry_id: foreign key to journal_entries.id (nullable)
```

### Observer Pattern
```php
// app/Models/Expense.php
static::updated(function (self $expense) {
    $entry = JournalEntry::where('expense_id', $expense->id)->latest('id')->first();
    $oldId = $entry?->id;
    
    if ($entry) {
        $entry->void(); // Sets status='void', voided_at=now()
    }
    
    $newEntry = $expense->createJournalEntry();
    
    if ($oldId) {
        $newEntry->update(['replaces_entry_id' => $oldId]);
    }
});
```

### Why Not Delete Old Entries?
1. **Audit Trail**: Maintains complete history of all transactions
2. **Compliance**: Many accounting standards require transaction history
3. **Debugging**: Helps identify and fix data issues
4. **Reversibility**: Can trace back through changes if needed

## Best Practices

### For Users
1. Use "Posted" status filter (default) for normal operations
2. Use "All Status" only when investigating history or debugging
3. Look for "Replacement" badges to understand entry relationships
4. Check the "Replaced by" link on voided entries to see current version

### For Developers
1. Always filter by `status = 'posted'` in queries
2. Use `->whereHas('journalEntry', fn($q) => $q->where('status', 'posted'))` for journal entry lines
3. Never delete journal entries - always void them
4. Maintain the `replaces_entry_id` link for audit trail

## Related Files
- `app/Models/Expense.php` - Expense model with journal entry creation
- `app/Models/JournalEntry.php` - Journal entry model with void() method
- `app/Models/Account.php` - Account balance calculations
- `resources/views/livewire/journal-entries/index.blade.php` - Journal entries list view
- `resources/views/livewire/journal-entries/show.blade.php` - Journal entry detail view

## Conclusion

The system is working correctly. The "duplicate payables" were actually:
1. One voided (inactive) entry - excluded from all calculations
2. One posted (active) entry - included in all calculations

The UI improvements make this distinction clear to users, preventing confusion while maintaining the complete audit trail required for proper accounting practices.
