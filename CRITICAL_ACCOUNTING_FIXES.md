# Critical Accounting System Fixes

## Executive Summary

After thorough investigation, I've identified several critical issues in the accounting system:

1. **CRITICAL:** Sales journal entries are being voided and recreated when status changes, breaking Accounts Receivable tracking
2. **CONFIRMED:** Partial payments DO create journal entries correctly
3. **DESIGN DECISION:** Void-and-recreate pattern for edits is intentional for audit trail
4. **NEEDS VERIFICATION:** Charge doubling issue requires actual data to diagnose

## Issue 1: Sales and Accounts Receivable Mismatch (CRITICAL BUG)

### The Problem

When a sale's status changes (e.g., from unpaid to paid), the system voids the original journal entry and creates a new one with a different debit account:

**Original Entry (Unpaid):**
- Dr. Accounts Receivable (1200) - 100,000
- Cr. Income Account (4xxx) - 100,000

**After Status Change to Paid:**
- VOIDS the above entry
- Creates: Dr. Bank (1100) - 100,000, Cr. Income (4xxx) - 100,000

**The Bug:**
- The original AR debit is voided (removed from balance)
- The new entry debits Bank instead of AR
- Result: AR balance is WRONG, Bank balance is WRONG
- The customer payment that should reduce AR never gets matched properly

### The Correct Flow

**Step 1 - Sale Created (Unpaid):**
```
Dr. Accounts Receivable (1200)  100,000
    Cr. Income Account (4xxx)   100,000
```

**Step 2 - Customer Makes Payment:**
```
Dr. Bank (1100)                 100,000
    Cr. Accounts Receivable (1200)  100,000
```

**Result:**
- Income: 100,000 (correct)
- AR: 100,000 - 100,000 = 0 (correct)
- Bank: 100,000 (correct)

### Current Broken Flow

**Step 1 - Sale Created (Unpaid):**
```
Dr. Accounts Receivable (1200)  100,000
    Cr. Income Account (4xxx)   100,000
```

**Step 2 - Customer Makes Payment:**
```
Dr. Bank (1100)                 100,000
    Cr. Accounts Receivable (1200)  100,000
```

**Step 3 - Sale Status Updated (BUG HAPPENS HERE):**
- Voids Entry from Step 1
- Creates:
```
Dr. Bank (1100)                 100,000
    Cr. Income Account (4xxx)   100,000
```

**Result:**
- Income: 100,000 (correct - only one posted entry)
- AR: 0 - 100,000 = -100,000 (WRONG! Should be 0)
- Bank: 100,000 + 100,000 = 200,000 (WRONG! Should be 100,000)

### The Fix

**DO NOT void and recreate sale journal entries when status changes!**

The sale status should be computed based on payments, not manually changed. The journal entry should remain as originally created.

**Code Changes Required:**

1. Remove the `updated` observer that voids/recreates sale journal entries
2. Keep only the `created` observer
3. Let the status be computed from payments (already implemented via `updatePaymentStatus()`)

## Issue 2: Partial Payments in Journal Entries

### Investigation Results

**CONFIRMED:** Partial payments DO create journal entries correctly.

**How It Works:**
1. Expense created: Dr. Expense, Cr. Accounts Payable (full amount)
2. Partial payment: Dr. Accounts Payable, Cr. Bank (partial amount)
3. Remaining balance stays in Accounts Payable

**Example:**
- Expense: 50,000 + 1,000 charges = 51,000
- Payment 1: 20,000
- Payment 2: 31,000

**Journal Entries:**
```
Entry 1 (Expense):
Dr. Expense Account     51,000
    Cr. Accounts Payable    51,000

Entry 2 (Payment 1):
Dr. Accounts Payable    20,000
    Cr. Bank                20,000

Entry 3 (Payment 2):
Dr. Accounts Payable    31,000
    Cr. Bank                31,000
```

**Accounts Payable Balance:** 51,000 - 20,000 - 31,000 = 0 ✓

### Verification

If payments aren't showing in journal entries view, check:
1. Status filter is set to "Posted" (not "Draft")
2. Date range includes payment dates
3. Type filter includes "Payment" entries

## Issue 3: Charge Doubling

### Current Code Analysis

**Expense Journal Entry Creation:**
```php
'debit' => (float) ($this->amount_base ?? $this->amount) + (is_numeric($this->charges) ? (float) $this->charges : 0)
```

**Base Amount Calculation:**
```php
$totalAmount = $amount + $charges;
$expense->amount_base = $totalAmount * $rate;
```

**This is CORRECT!** Charges are added once.

### Possible Causes

1. **Currency Conversion Issue:** If `amount_base` already includes charges, and then charges are added again
2. **Display Issue:** UI showing wrong calculation
3. **Data Entry Issue:** Charges entered twice by mistake

### Verification Needed

Run this query to check actual values:
```sql
SELECT 
    e.id,
    e.amount,
    e.charges,
    e.amount_base,
    e.exchange_rate,
    e.currency,
    jel.debit,
    jel.credit,
    a.code,
    a.name
FROM expenses e
JOIN journal_entries je ON je.expense_id = e.id
JOIN journal_entry_lines jel ON jel.journal_entry_id = je.id
JOIN accounts a ON jel.account_id = a.id
WHERE e.id = [your_expense_id]
AND je.status = 'posted'
ORDER BY jel.id;
```

Expected results for 50,000 + 1,000 charge:
- Expense row: amount=50000, charges=1000, amount_base=51000
- Journal line 1 (Expense): debit=51000, credit=0
- Journal line 2 (AP): debit=0, credit=51000

If you see debit=52000, then there's a bug in the calculation.

## Issue 4: Void-and-Recreate Pattern

### Current Behavior

When expenses are edited, the system:
1. Voids the old journal entry (status='void')
2. Creates a new journal entry with updated values
3. Links them via `replaces_entry_id`

### Why This Is Correct

**Accounting Standards:**
- Posted journal entries should never be deleted
- All changes must be traceable
- Audit trail is mandatory

**Benefits:**
- Complete transaction history
- Can trace all changes
- Complies with regulations
- Helps with debugging

### UI Improvements (Already Implemented)

- Voided entries shown with reduced opacity
- Strikethrough text
- "Replacement" badges
- Informational notice when viewing all statuses

### Recommendation

**KEEP THIS PATTERN** - It's correct accounting practice.

## Implementation Plan

### Priority 1: Fix Sales Journal Entry Bug (CRITICAL)

**File:** `app/Models/Sale.php`

**Change:** Remove or modify the `updated` observer to NOT void/recreate journal entries on status changes.

**Rationale:** 
- Sale status should be computed from payments
- Original journal entry (Dr. AR, Cr. Income) should remain
- Customer payments handle the AR reduction

### Priority 2: Verify Charge Calculation

**Action:** Run diagnostic queries on actual expense data

**If bug confirmed:** Fix the calculation in `Expense::createJournalEntry()`

### Priority 3: Document Correct Flows

**Action:** Create user documentation explaining:
- How sales and payments work together
- Why voided entries appear
- How to read journal entries correctly

## Testing Plan

### Test Case 1: Sales and AR

1. Create unpaid sale for 100,000
2. Verify journal: Dr. AR 100,000, Cr. Income 100,000
3. Create customer payment for 100,000
4. Verify journal: Dr. Bank 100,000, Cr. AR 100,000
5. Check AR balance: Should be 0
6. Check Bank balance: Should be 100,000
7. Check Income: Should be 100,000

### Test Case 2: Partial Payments

1. Create expense for 50,000 + 1,000 charges
2. Verify journal: Dr. Expense 51,000, Cr. AP 51,000
3. Create payment for 20,000
4. Verify journal: Dr. AP 20,000, Cr. Bank 20,000
5. Check AP balance: Should be 31,000
6. Create payment for 31,000
7. Check AP balance: Should be 0

### Test Case 3: Expense Edit

1. Create expense with vendor A
2. Note journal entry ID
3. Edit expense, change to vendor B
4. Verify old entry is voided
5. Verify new entry exists with vendor B
6. Verify `replaces_entry_id` links them
7. Check AP balance: Should reflect only new entry

## Next Steps

1. **IMMEDIATE:** Implement Sales journal entry fix
2. **URGENT:** Test with sample data
3. **HIGH:** Verify charge calculation with real data
4. **MEDIUM:** Create user documentation
5. **LOW:** Additional UI improvements

## Code Changes Preview

### Fix for Sale Model

**Current (BROKEN):**
```php
static::updated(function (self $sale) {
    $entry = JournalEntry::where('sales_id', $sale->id)->latest('id')->first();
    $oldId = $entry?->id;
    if ($entry) {
        $entry->void(); // BUG: This breaks AR tracking
    }
    if ($sale->postsToLedger()) {
        $newEntry = $sale->createJournalEntry();
        if ($oldId) {
            $newEntry->update(['replaces_entry_id' => $oldId]);
        }
    }
});
```

**Proposed Fix (Option 1 - Remove observer):**
```php
// Remove the updated observer entirely
// Let status be computed from payments
// Journal entry stays as originally created
```

**Proposed Fix (Option 2 - Only recreate for specific changes):**
```php
static::updated(function (self $sale) {
    // Only recreate if amount or account changes, NOT status
    if ($sale->isDirty(['amount', 'account_id', 'sale_date'])) {
        $entry = JournalEntry::where('sales_id', $sale->id)->latest('id')->first();
        $oldId = $entry?->id;
        if ($entry) {
            $entry->void();
        }
        if ($sale->postsToLedger()) {
            $newEntry = $sale->createJournalEntry();
            if ($oldId) {
                $newEntry->update(['replaces_entry_id' => $oldId]);
            }
        }
    }
});
```

**Recommendation:** Use Option 2 - Only recreate for material changes, not status changes.
