# QA Team - Quick Reference Guide

## What to Tell Your QA Team

"We've fixed critical accounting bugs. I need you to verify the fixes and investigate one remaining issue. Follow the instructions in `QA_TESTING_INSTRUCTIONS.md`"

---

## Priority Order

### 1. CRITICAL: Sales & Accounts Receivable (30 minutes)
**What to test:** Create unpaid sale, make payment, verify original journal entry is NOT voided

**Key check:** Original sale journal entry should stay "Posted" after payment is made

**If it's voided:** Bug not fixed - report immediately

---

### 2. HIGH: Charge Doubling Investigation (15 minutes)
**What to test:** Create expense with 50,000 + 1,000 charges

**Key check:** Journal entry should show 51,000 (not 52,000)

**Must provide:** 
- SQL query results (query provided in instructions)
- Screenshot of journal entry
- Exact amounts seen

---

### 3. MEDIUM: Partial Payments Visibility (10 minutes)
**What to test:** Create expense, make 2 partial payments, check if they appear in journal entries

**Key check:** Payment vouchers should be visible in journal entries with status = "Posted"

**If not visible:** Provide screenshots with filters shown

---

### 4. LOW: Voided Entries Display (10 minutes)
**What to test:** Edit expense, verify old entry looks faded/grayed out

**Key check:** Voided entries should have visual indicators (opacity, strikethrough, gray background)

---

### 5. MEDIUM: Balance Verification (20 minutes)
**What to test:** Compare Balance Sheet AR/AP with SQL queries and sales/expenses pages

**Key check:** All three sources should show same balance

**If different:** Provide all three numbers and screenshots

---

## What QA Team Needs to Provide Back

### For Charge Doubling Issue (REQUIRED):
```
Expense ID: _______
Amount: _______
Charges: _______
Amount Base: _______

Journal Entry Debit Amount: _______
Expected: 51,000
Actual: _______

SQL Query Results: (copy/paste or screenshot)

Is bug present? [ ] Yes (52,000) [ ] No (51,000)
```

### For Sales/AR Fix (REQUIRED):
```
Sale ID: _______
Original Journal Entry Reference: _______
After payment, is original entry voided? [ ] Yes (BUG) [ ] No (FIXED)

Accounts Receivable Balance:
- After sale creation: _______
- After payment: _______
- Expected after payment: 0
- Match? [ ] Yes [ ] No
```

### For Any Issues Found:
- Screenshots
- Specific IDs (sale ID, expense ID, etc.)
- SQL query results
- Error messages (if any)

---

## SQL Queries They'll Need to Run

### Query 1: Check Expense with Charges
```sql
SELECT 
    e.id, e.amount, e.charges, e.amount_base,
    jel.debit, jel.credit, a.code, a.name
FROM expenses e
JOIN journal_entries je ON je.expense_id = e.id
JOIN journal_entry_lines jel ON jel.journal_entry_id = je.id
JOIN accounts a ON jel.account_id = a.id
WHERE e.id = [expense_id]
AND je.status = 'posted'
ORDER BY je.id, jel.id;
```

### Query 2: Check Sale Journal Entries
```sql
SELECT 
    je.id, je.reference, je.status, je.voided_at,
    jel.debit, jel.credit, a.code, a.name
FROM journal_entries je
JOIN journal_entry_lines jel ON jel.journal_entry_id = je.id
JOIN accounts a ON jel.account_id = a.id
WHERE je.sales_id = [sale_id]
ORDER BY je.id, jel.id;
```

### Query 3: Check AR Balance
```sql
SELECT SUM(jel.debit - jel.credit) as ar_balance
FROM journal_entry_lines jel
JOIN journal_entries je ON jel.journal_entry_id = je.id
JOIN accounts a ON jel.account_id = a.id
WHERE a.code = '1200' AND je.status = 'posted';
```

### Query 4: Check AP Balance
```sql
SELECT SUM(jel.credit - jel.debit) as ap_balance
FROM journal_entry_lines jel
JOIN journal_entries je ON jel.journal_entry_id = je.id
JOIN accounts a ON jel.account_id = a.id
WHERE a.code = '2000' AND je.status = 'posted';
```

---

## Expected Timeline

- **Total testing time:** ~1.5 hours
- **Critical tests:** 45 minutes
- **Balance verification:** 20 minutes
- **Documentation:** 15 minutes

---

## Red Flags to Watch For

🚨 **CRITICAL - Report Immediately:**
- Sale journal entry gets voided after payment
- AR balance is negative
- AR balance doesn't match sales ledger

⚠️ **HIGH - Report with Details:**
- Charges showing as 52,000 instead of 51,000
- Payment entries not visible in journal
- Balance Sheet numbers don't match SQL queries

ℹ️ **MEDIUM - Document and Report:**
- Voided entries not showing visual indicators
- Any error messages
- Unexpected behavior

---

## Quick Test (5 minutes)

If time is limited, do this quick smoke test:

1. Create sale for 100,000 (unpaid)
2. Make payment for 100,000
3. Check if original sale journal entry is voided
   - **If voided:** CRITICAL BUG ❌
   - **If posted:** Fix working ✓

4. Create expense: 50,000 + 1,000 charges
5. Check journal entry debit amount
   - **If 52,000:** Bug confirmed ❌
   - **If 51,000:** No bug ✓

---

## Files to Reference

- `QA_TESTING_INSTRUCTIONS.md` - Detailed step-by-step instructions
- `FIXES_IMPLEMENTED_SUMMARY.md` - What was fixed and why
- `CRITICAL_ACCOUNTING_FIXES.md` - Technical details

---

## Questions?

If QA team has questions, they should:
1. Check the detailed instructions first
2. Document what they see with screenshots
3. Provide specific IDs and query results
4. Note any error messages
