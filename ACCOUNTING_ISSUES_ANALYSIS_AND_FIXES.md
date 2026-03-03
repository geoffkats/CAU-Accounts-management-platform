# Accounting System Issues - Analysis and Fixes

## Issues Identified

### 1. Charge Doubling in Journal Entries
**Problem:** When creating an expense with charges (e.g., 50,000 + 1,000 charge), the journal shows 52,000 instead of 51,000.

**Root Cause:** NOT CONFIRMED YET - Need to verify actual behavior. The code shows charges are correctly added once:
```php
// In Expense::createJournalEntry()
'debit' => (float) ($this->amount_base ?? $this->amount) + (is_numeric($this->charges) ? (float) $this->charges : 0)
```

**Status:** NEEDS VERIFICATION - Please provide:
- Actual journal entry details showing the doubling
- The expense record details (amount, charges, amount_base)
- Currency conversion rate if applicable

### 2. Partial Payments Not Reflected in Journal Entries
**Problem:** When an expense is partially paid, the payment amounts are not showing in the general journal or journal entries.

**Root Cause:** CONFIRMED - Payments DO create journal entries, but there may be a display or filtering issue.

**How Payments Work:**
1. Expense created: Dr. Expense Account, Cr. Accounts Payable (full amount including charges)
2. Payment made: Dr. Accounts Payable, Cr. Cash/Bank (payment amount)
3. Partial payment: Same as above, but for partial amount

**Verification Needed:**
- Check if payment journal entries exist in database
- Check if they're being filtered out in the view
- Verify payment_id is correctly set on journal entries

### 3. Void-and-Recreate Pattern for Expense Edits
**Problem:** When expenses are edited, the system voids the old entry and creates a new one, rather than updating the original.

**Current Behavior:** This is BY DESIGN for audit trail purposes.

**Why This Approach:**
- Maintains complete transaction history
- Complies with accounting standards (no deletion of posted entries)
- Provides audit trail via `replaces_entry_id`
- Allows tracing all changes

**Alternative Approach:** Update journal entries in place (NOT RECOMMENDED)

**Recommendation:** Keep current approach but improve UI to hide voided entries by default.

### 4. Accounts Receivable Balance Mismatch
**Problem:** The amounts in the sales ledger differ from those in the balance sheet.

**Root Cause:** NEEDS INVESTIGATION

**Possible Causes:**
1. Sales with different statuses (paid vs unpaid) creating different journal entries
2. Customer payments not properly reducing Accounts Receivable
3. Currency conversion issues
4. Filtering issues in reports

**How It Should Work:**
- Unpaid sale: Dr. Accounts Receivable (1200), Cr. Income
- Customer payment: Dr. Cash/Bank, Cr. Accounts Receivable (1200)
- Balance Sheet should show: Accounts Receivable balance = Sum of unpaid sales - customer payments

## Detailed Analysis

### Issue 1: Charge Doubling - Code Review

**Expense Creation:**
```php
// app/Models/Expense.php - createJournalEntry()
'debit' => (float) ($this->amount_base ?? $this->amount) + (is_numeric($this->charges) ? (float) $this->charges : 0)
```

**Payment Creation:**
```php
// app/Models/Payment.php - createJournalEntry()
'debit' => $this->amount  // Uses payment amount, NOT expense amount
```

**Conclusion:** Code appears correct. Charges are added once during expense creation. Payment uses its own amount field.

**Possible Issue:** If `amount_base` calculation is doubling charges:
```php
// In Expense::booted() - saving hook
$totalAmount = $amount + $charges;
$expense->amount_base = $totalAmount * $rate;
```

This looks correct too. Need actual data to diagnose.

### Issue 2: Partial Payments - Investigation

**Payment Journal Entry Creation:**
```php
// app/Models/Payment.php - created observer
static::created(function (self $payment) {
    $payment->createJournalEntry();
});
```

**Journal Entry Structure:**
- Dr. Accounts Payable (2000) - reduces liability
- Cr. Cash/Bank - reduces asset

**Verification Steps:**
1. Check if journal entries exist: `SELECT * FROM journal_entries WHERE payment_id IS NOT NULL`
2. Check if they're posted: `WHERE status = 'posted'`
3. Check if they appear in views with proper filters

### Issue 3: Sales and Accounts Receivable

**Sale Journal Entry Logic:**
```php
// app/Models/Sale.php - createJournalEntry()
if ($this->status === self::STATUS_PAID || $this->status === self::STATUS_PARTIALLY_PAID) {
    $debitAccount = Account::where('code', '1100')->first(); // Bank
} else {
    $debitAccount = Account::where('code', '1200')->first(); // Accounts Receivable
}
```

**Problem:** When a sale status changes from unpaid to paid, the journal entry is voided and recreated:
- Old entry: Dr. Accounts Receivable, Cr. Income
- New entry: Dr. Bank, Cr. Income

**Issue:** The old Accounts Receivable debit is voided, but there's no corresponding credit to clear it!

**This is a BUG!** When a sale is marked as paid, we should:
1. Keep the original entry (Dr. AR, Cr. Income)
2. Create a NEW entry for the payment (Dr. Bank, Cr. AR)

NOT void and recreate the sale entry.

## Recommended Fixes

### Fix 1: Charge Doubling
**Action:** Need more data to diagnose. Please provide:
```sql
-- Get expense details
SELECT id, amount, charges, amount_base, exchange_rate, currency 
FROM expenses 
WHERE id = [expense_id];

-- Get journal entry lines
SELECT jel.*, je.reference, je.type, je.status, a.code, a.name
FROM journal_entry_lines jel
JOIN journal_entries je ON jel.journal_entry_id = je.id
JOIN accounts a ON jel.account_id = a.id
WHERE je.expense_id = [expense_id]
ORDER BY je.id, jel.id;
```

### Fix 2: Partial Payments Display
**Action:** Verify payments are creating journal entries and appearing in views.

**Test Query:**
```sql
SELECT je.*, p.voucher_number, p.amount as payment_amount
FROM journal_entries je
JOIN payments p ON je.payment_id = p.id
WHERE je.status = 'posted'
ORDER BY je.date DESC
LIMIT 10;
```

### Fix 3: Keep Void-and-Recreate (with UI improvements)
**Action:** Already implemented in previous fix - visual distinction for voided entries.

**Additional Recommendation:** Add a setting to hide voided entries by default in reports.

### Fix 4: Sales and Accounts Receivable - CRITICAL BUG FIX

**Problem:** Sale status changes void the original AR entry without proper clearing.

**Solution:** Change the Sale model to NOT recreate journal entries on status change.

**Proper Flow:**
1. Sale created (unpaid): Dr. AR (1200), Cr. Income
2. Customer payment received: Dr. Bank, Cr. AR (1200) - via CustomerPayment model
3. Sale status updates to "paid" automatically based on payments

**DO NOT** void and recreate the sale journal entry when status changes!

## Implementation Priority

1. **HIGH PRIORITY:** Fix Sales/Accounts Receivable journal entry logic
2. **MEDIUM PRIORITY:** Investigate and fix charge doubling (if confirmed)
3. **MEDIUM PRIORITY:** Verify partial payment journal entries are displaying
4. **LOW PRIORITY:** UI improvements for voided entries (already done)

## Next Steps

1. Run diagnostic queries to confirm issues
2. Implement Sales/AR fix
3. Test with sample data
4. Verify all reports show correct balances
5. Document the corrected flow
