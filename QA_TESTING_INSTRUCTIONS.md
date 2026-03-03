# QA Testing Instructions - Accounting System Fixes

## Overview
We've fixed critical accounting bugs. The QA team needs to verify these fixes and investigate one remaining issue.

---

## PRIORITY 1: Verify Sales & Accounts Receivable Fix (CRITICAL)

### What Was Fixed
Sales journal entries were being incorrectly voided when payment status changed, causing AR balances to be wrong.

### Test Case 1: Unpaid Sale with Full Payment

**Steps:**
1. Create a new sale for **100,000 UGX** (unpaid)
2. Note the sale ID: `_______`
3. Go to Journal Entries and find the entry for this sale
4. Record the journal entry reference: `_______`
5. Verify it shows:
   - **Debit:** Accounts Receivable (1200) - 100,000
   - **Credit:** Income Account - 100,000
   - **Status:** Posted

6. Now create a customer payment for **100,000 UGX** for this sale
7. Go to Journal Entries and find the PAYMENT entry
8. Verify it shows:
   - **Debit:** Bank (1100) - 100,000
   - **Credit:** Accounts Receivable (1200) - 100,000
   - **Status:** Posted

9. **CRITICAL CHECK:** Go back to the ORIGINAL sale journal entry (from step 4)
   - **Expected:** Status should still be "Posted" (NOT voided)
   - **If voided:** THE BUG IS NOT FIXED ❌

10. Check the sale record - status should now show "Paid"

11. Run this SQL query (replace `[sale_id]` with actual ID from step 2):
```sql
SELECT 
    je.id,
    je.reference,
    je.type,
    je.status,
    je.voided_at,
    jel.debit,
    jel.credit,
    a.code,
    a.name
FROM journal_entries je
JOIN journal_entry_lines jel ON jel.journal_entry_id = je.id
JOIN accounts a ON jel.account_id = a.id
WHERE je.sales_id = [sale_id]
ORDER BY je.id, jel.id;
```

**Expected Results:**
- Should show ONLY 2 journal entry lines (1 entry with 2 lines)
- Status should be "posted" (NOT "void")
- One line: Debit AR 100,000
- One line: Credit Income 100,000

**If you see 4 lines or status = 'void':** THE BUG IS NOT FIXED ❌

12. Check Balance Sheet report:
    - Accounts Receivable balance should be **0** (not 100,000 or -100,000)

### Test Case 2: Partial Payment

**Steps:**
1. Create a new sale for **200,000 UGX** (unpaid)
2. Note the sale ID: `_______`
3. Create customer payment for **80,000 UGX**
4. Check sale status - should show "Partially Paid"
5. Check Accounts Receivable balance - should show **120,000**
6. Create another payment for **120,000 UGX**
7. Check sale status - should show "Paid"
8. Check Accounts Receivable balance - should show **0**

**Record Results:**
- [ ] Sale status updated correctly
- [ ] AR balance = 120,000 after first payment
- [ ] AR balance = 0 after second payment
- [ ] Original sale journal entry NOT voided

---

## PRIORITY 2: Investigate Charge Doubling Issue

### What We Need to Verify
User reported that charges are being doubled (50,000 + 1,000 charge showing as 52,000 instead of 51,000).

### Test Case: Create Expense with Charges

**Steps:**
1. Create a new expense:
   - Amount: **50,000 UGX**
   - Charges: **1,000 UGX**
   - Currency: UGX
   - Vendor: Any vendor
   - Account: Any expense account

2. Note the expense ID: `_______`

3. Go to Journal Entries and find the expense entry

4. **CRITICAL:** Record what you see:
   - Expense Account Debit amount: `_______`
   - Accounts Payable Credit amount: `_______`
   - **Expected:** Both should be **51,000**
   - **If you see 52,000:** BUG CONFIRMED ✓

5. Run this SQL query (replace `[expense_id]` with actual ID):
```sql
SELECT 
    e.id,
    e.amount,
    e.charges,
    e.amount_base,
    e.exchange_rate,
    e.currency,
    je.reference,
    je.status,
    jel.debit,
    jel.credit,
    a.code,
    a.name
FROM expenses e
JOIN journal_entries je ON je.expense_id = e.id
JOIN journal_entry_lines jel ON jel.journal_entry_id = je.id
JOIN accounts a ON jel.account_id = a.id
WHERE e.id = [expense_id]
AND je.status = 'posted'
ORDER BY je.id, jel.id;
```

6. **Copy the COMPLETE query results** and provide them to the development team

**What to Record:**
```
Expense ID: _______
Amount: _______
Charges: _______
Amount Base: _______
Exchange Rate: _______
Currency: _______

Journal Entry Line 1 (Expense Account):
- Account Code: _______
- Account Name: _______
- Debit: _______
- Credit: _______

Journal Entry Line 2 (Accounts Payable):
- Account Code: _______
- Account Name: _______
- Debit: _______
- Credit: _______

Is the bug present? [ ] Yes (debit = 52,000) [ ] No (debit = 51,000)
```

### Test Case: Expense with Charges and Payment

**Steps:**
1. Use the expense created above (50,000 + 1,000 charges)
2. Create a payment voucher for **51,000 UGX**
3. Go to Journal Entries and find the PAYMENT entry
4. Verify it shows:
   - **Debit:** Accounts Payable (2000) - 51,000
   - **Credit:** Bank - 51,000

5. Check Accounts Payable balance - should be **0**

**Record Results:**
- [ ] Payment journal entry created correctly
- [ ] AP balance = 0 after payment
- [ ] No doubling in payment entry

---

## PRIORITY 3: Verify Partial Payments Appear in Journal

### Test Case: Multiple Partial Payments

**Steps:**
1. Create expense for **100,000 UGX** (no charges)
2. Note expense ID: `_______`
3. Create payment voucher for **30,000 UGX**
4. Note payment voucher number: `_______`
5. Create another payment for **40,000 UGX**
6. Note payment voucher number: `_______`

7. Go to Journal Entries page
8. Set filters:
   - Status: **Posted**
   - Type: **All Types** (or specifically "Payment")
   - Date range: Include today

9. **Search for the payment voucher numbers** from steps 4 and 6

**Record Results:**
- [ ] First payment entry visible (PV-XXXX)
- [ ] Second payment entry visible (PV-XXXX)
- [ ] Each shows: Dr. AP, Cr. Bank
- [ ] Amounts are correct (30,000 and 40,000)

10. Check Accounts Payable balance - should be **30,000** (100,000 - 30,000 - 40,000)

**If payments NOT visible:**
- Check status filter (must be "Posted" or "All Status")
- Check date range includes payment dates
- Check type filter includes "Payment"
- Take screenshot and provide to dev team

---

## PRIORITY 4: Verify Voided Entries Display Correctly

### Test Case: Edit Expense and Check Voided Entry

**Steps:**
1. Create expense with Vendor A for **20,000 UGX**
2. Note the journal entry reference: `_______`
3. Edit the expense and change vendor to Vendor B
4. Go to Journal Entries page
5. Set status filter to **"All Status"**
6. Find the original journal entry (from step 2)

**Verify Visual Indicators:**
- [ ] Entry has reduced opacity (looks faded/grayed out)
- [ ] Entry has gray background
- [ ] Date and reference have strikethrough
- [ ] Status badge shows "Void" in red
- [ ] There's a blue informational notice at the top explaining voided entries

7. Find the NEW journal entry for the same expense
8. Verify it shows:
   - [ ] Status: Posted
   - [ ] Vendor B in description
   - [ ] "Replacement" badge visible
   - [ ] Normal appearance (not faded)

9. Set status filter to **"Posted"**
10. Verify:
    - [ ] Only the NEW entry is visible
    - [ ] The voided entry is hidden

**Record Results:**
- [ ] Voided entries are visually distinct
- [ ] Informational notice appears
- [ ] Posted filter hides voided entries
- [ ] All visual indicators working

---

## PRIORITY 5: Balance Sheet Verification

### Test Case: Accounts Receivable Balance

**Steps:**
1. Note current date: `_______`
2. Go to Balance Sheet report
3. Find "Accounts Receivable" line item
4. Note the balance: `_______`

5. Run this SQL query:
```sql
-- Calculate AR balance from journal entries
SELECT 
    SUM(jel.debit - jel.credit) as ar_balance
FROM journal_entry_lines jel
JOIN journal_entries je ON jel.journal_entry_id = je.id
JOIN accounts a ON jel.account_id = a.id
WHERE a.code = '1200'
AND je.status = 'posted';
```

6. Note the SQL result: `_______`

7. Go to Sales page and calculate:
   - Total unpaid sales: `_______`
   - Total partially paid sales (outstanding amount): `_______`
   - Expected AR = unpaid + partially paid outstanding: `_______`

**Verify:**
- [ ] Balance Sheet AR = SQL query result
- [ ] Balance Sheet AR = Expected AR from sales
- [ ] All three numbers match

**If numbers DON'T match:**
- Provide all three numbers to dev team
- Take screenshots of Balance Sheet and Sales page
- Run the SQL query and provide results

---

## PRIORITY 6: Accounts Payable Balance

### Test Case: Accounts Payable Balance

**Steps:**
1. Go to Balance Sheet report
2. Find "Accounts Payable" line item
3. Note the balance: `_______`

4. Run this SQL query:
```sql
-- Calculate AP balance from journal entries
SELECT 
    SUM(jel.credit - jel.debit) as ap_balance
FROM journal_entry_lines jel
JOIN journal_entries je ON jel.journal_entry_id = je.id
JOIN accounts a ON jel.account_id = a.id
WHERE a.code = '2000'
AND je.status = 'posted';
```

5. Note the SQL result: `_______`

6. Go to Expenses page and calculate:
   - Total unpaid expenses: `_______`
   - Total partially paid expenses (outstanding amount): `_______`
   - Expected AP = unpaid + partially paid outstanding: `_______`

**Verify:**
- [ ] Balance Sheet AP = SQL query result
- [ ] Balance Sheet AP = Expected AP from expenses
- [ ] All three numbers match

**If numbers DON'T match:**
- Provide all three numbers to dev team
- Take screenshots of Balance Sheet and Expenses page
- Run the SQL query and provide results

---

## Summary Checklist

### Critical Issues to Verify
- [ ] Sales journal entries NOT voided when status changes
- [ ] Accounts Receivable balance is correct
- [ ] Charge doubling issue confirmed or denied (with SQL results)
- [ ] Partial payments visible in journal entries
- [ ] Voided entries display correctly

### Information to Provide to Dev Team

**For Charge Doubling Issue:**
- Complete SQL query results from Priority 2
- Screenshots of expense form showing amount and charges
- Screenshots of journal entry showing debit/credit amounts

**For Any Balance Mismatches:**
- Balance Sheet amounts
- SQL query results
- Expected amounts from sales/expenses pages
- Screenshots of all three

**For Missing Payment Entries:**
- Payment voucher numbers
- Screenshots of journal entries page with filters shown
- Date range used
- Status filter used

---

## How to Run SQL Queries

1. Access the database using your preferred tool (phpMyAdmin, TablePlus, etc.)
2. Copy the SQL query from this document
3. Replace `[expense_id]` or `[sale_id]` with the actual ID number
4. Run the query
5. Copy ALL results (you can export to CSV or take screenshot)
6. Provide to dev team

---

## Contact

If you encounter any issues or need clarification:
- Document what you see with screenshots
- Provide the specific IDs (sale ID, expense ID, etc.)
- Include the SQL query results
- Note any error messages

**All test results should be documented and provided to the development team for final verification.**
