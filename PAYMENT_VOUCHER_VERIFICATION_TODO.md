# Payment Voucher System - Verification TODO

## ✅ COMPLETED
- [x] Created payments table with voucher numbering (PV-0001, PV-0002, etc.)
- [x] Separated expenses from payments (proper accounting separation)
- [x] Auto-generate journal entries for expenses and payments
- [x] Computed payment_status (unpaid/partial/paid) from actual payments
- [x] Added audit trail logging for all payment activities
- [x] Made expense rows clickable in table
- [x] Truncated long columns for better table layout

## 🔍 CRITICAL VERIFICATION NEEDED

### 1. Journal Entries Verification
**Test Case 1: Create Expense**
- [ ] Create expense for $1,000 (Utilities)
- [ ] Verify journal entry created:
  - Dr. Utilities Expense Account: $1,000
  - Cr. Accounts Payable (2000): $1,000
- [ ] Check in General Ledger that both accounts show the transaction
- [ ] Verify entry has expense_id linked

**Test Case 2: Create Payment Voucher**
- [ ] Create payment voucher for $400 (partial payment)
- [ ] Verify journal entry created:
  - Dr. Accounts Payable (2000): $400
  - Cr. Bank/Cash Account: $400
- [ ] Check payment_id is linked to journal entry
- [ ] Verify voucher number generated (PV-0001)

**Test Case 3: Multiple Payments**
- [ ] Create 2nd payment voucher for $600 (remaining balance)
- [ ] Verify 2nd journal entry created correctly
- [ ] Check expense payment_status changes: unpaid → partial → paid
- [ ] Verify voucher number increments (PV-0002)

### 2. Account Balance Verification

**Check Account Statement Page**
- [ ] Navigate to Account Statement for Bank Account
- [ ] Verify opening balance shows correctly
- [ ] Verify payment transactions appear with:
  - Correct date
  - Credit amount (money out)
  - Running balance decreases
- [ ] Check closing balance = opening - total payments

**Check Accounts Payable**
- [ ] View Account Statement for Accounts Payable (2000)
- [ ] Verify expense creates credit (liability increases)
- [ ] Verify payment creates debit (liability decreases)
- [ ] Check balance = total unpaid expenses

### 3. Trial Balance Verification
- [ ] Navigate to Trial Balance report
- [ ] Verify all accounts with transactions appear
- [ ] Check debits = credits (must balance!)
- [ ] Verify account balances match:
  - Assets (Bank/Cash) show correct balances
  - Liabilities (Accounts Payable) show correct balance
  - Expenses show total expense amounts

### 4. Balance Sheet Verification
- [ ] Navigate to Balance Sheet report
- [ ] Verify Assets section shows:
  - Bank accounts with correct balances
  - Cash accounts with correct balances
- [ ] Verify Liabilities section shows:
  - Accounts Payable with correct balance
- [ ] Check Assets = Liabilities + Equity (must balance!)

### 5. General Ledger Verification
- [ ] Navigate to General Ledger
- [ ] Filter by date range covering test transactions
- [ ] Verify all journal entries appear
- [ ] Check each account shows:
  - All debits and credits
  - Correct running balance
  - Proper descriptions

### 6. Payment Status Logic
- [ ] Create expense for $1,000
- [ ] Verify shows "Unpaid" status
- [ ] Create payment for $300
- [ ] Verify shows "Partial" status
- [ ] Create payment for $700
- [ ] Verify shows "Paid" status
- [ ] Check outstanding balance = $0

### 7. Edge Cases to Test
- [ ] Try to overpay (should show error)
- [ ] Delete a payment (verify journal entry handling)
- [ ] Edit an expense after payment made
- [ ] Multiple expenses to same vendor
- [ ] Payment from different accounts (Cash vs Bank)

## 🐛 KNOWN ISSUES TO FIX
- [ ] Ensure payment_id column exists in journal_entries table
- [ ] Verify JournalEntry::createEntry() method handles payment_id
- [ ] Check all reports query journal_entries correctly
- [ ] Ensure audit trail captures payment voucher creation

## 📊 REPORTS TO VERIFY
1. **General Ledger** - All transactions visible
2. **Trial Balance** - Debits = Credits
3. **Balance Sheet** - Assets = Liabilities + Equity
4. **Account Statement** - Individual account details
5. **Profit & Loss** - Expense totals correct
6. **Expense Breakdown** - Payment status accurate

## 🔧 FINAL CHECKS
- [ ] All migrations run successfully
- [ ] No orphaned payment records
- [ ] Journal entries link to payments correctly
- [ ] Audit trail shows all activities
- [ ] Payment voucher numbers sequential
- [ ] No duplicate voucher numbers
- [ ] Account balances accurate across all reports
