# Immutable Journal Entry & Audit Trail Feature

## 1. Overview
This feature implements a robust, double-entry accounting system with:
- **Immutable journal entries**: Edits void old entries and create new replacements.
- **Base currency posting**: All journal lines use base currency (`amount_base`).
- **Audit trail**: Every journal entry and line is tracked with hash-chained logs.
- **UI badges**: Users see “Replaces”, “Replaced by”, and “Voided at” badges for full traceability.

---

## 2. Data Model Changes

### `journal_entries` Table
- `replaces_entry_id`: FK to previous journal entry (self-referencing, nullable)
- `voided_at`: Timestamp when entry was voided

### Model Updates
- `JournalEntry` and `JournalEntryLine` use `LogsActivity` trait for audit logging.
- Relationships:
  - `replaces()`: Points to the previous entry
  - `replacements()`: Points to all entries that replaced this one

---

## 3. Immutable Posting Logic

### On Transaction Edit (Expense, Sale, CustomerPayment, VendorPayment)
1. **Find current journal entry** for the transaction.
2. **Void the old entry**: Set `status='void'`, `voided_at=now()`.
3. **Create a new posted entry**:
   - All lines use `amount_base` (base currency).
   - Set `replaces_entry_id` to the old entry’s id.
4. **Audit log**: Both void and create actions are logged with before/after changes, user, IP, and hash chain.

### On Transaction Create
- Create a posted journal entry with lines in base currency.

---

## 4. Double-Entry Formulas

### Expense
- **Dr. Expense Account (5xxx)**: `amount_base`
- **Cr. Bank (1100) or Accounts Payable (2000)**: `amount_base`
  - If `payment_status == 'paid'`: Cr. Bank
  - Else: Cr. Accounts Payable

### Sale
- **Dr. Bank (1100) or Accounts Receivable (1200)**: `amount_base`
  - If `status == 'paid' || 'partially_paid'`: Dr. Bank
  - Else: Dr. Accounts Receivable
- **Cr. Income Account (4xxx)**: `amount_base`

### Customer Payment
- **Dr. Bank (1100)**: `amount_base`
- **Cr. Accounts Receivable (1200)**: `amount_base`

### Vendor Payment
- **Dr. Accounts Payable (2000)**: `amount_base`
- **Cr. Bank (1100)**: `amount_base`

---

## 5. UI Features

- **Show View**: Displays badges for “Replaces”, “Replaced by”, and “Voided at” with links and timestamps.
- **Index View**: (Optional) Can show compact badges for voided/replaced entries.
- **Audit Panel**: (Optional) Can show created/updated/voided timestamps and user.

---

## 6. Audit Trail

- **Trait**: `LogsActivity` on all journal models.
- **Logs**: Every create, update, delete, and void action.
- **Fields**: User, IP, URL, user agent, before/after changes, hash chain.
- **Tamper detection**: Hash chain links all logs for forensic integrity.

---

## 7. Test Specifications

### Functional Tests
- **Create Transaction**: Expense/Sale/Payment posts a journal entry in base currency.
- **Edit Transaction**: Old entry is voided, new entry created with `replaces_entry_id`.
- **UI Badges**: Show correct “Replaces”, “Replaced by”, and “Voided at” info.
- **Audit Log**: All actions appear in `activity_logs` with correct before/after and hash.

### Edge Cases
- **Currency Change**: Changing currency or exchange rate updates journal lines to new base amount.
- **Account Change**: Changing account updates journal lines and audit log.
- **Multiple Edits**: Chain of replacements is navigable via badges.

### Example Test (PHP Artisan Tinker)
```php
// Create expense, edit amount, check journal chain
$expense = App\Models\Expense::first();
$oldEntry = $expense->journalEntry;
$expense->amount = $expense->amount + 1000;
$expense->save();
$newEntry = $expense->journalEntry;
assert($oldEntry->status === 'void');
assert($newEntry->replaces_entry_id === $oldEntry->id);
assert($newEntry->lines->sum('debit') === $expense->amount_base);
```

---

## 8. Logic Summary

- **All journal entries are immutable**: No edits to posted entries; only void-and-replace.
- **All lines use base currency**: Ensures consistent reporting and cross-ledger calculations.
- **Audit logs are hash-chained**: Any tampering is detectable.
- **UI shows full replacement/void history**: Users can trace every change.

---

## 9. Security & Compliance

- **Audit logs**: Meet forensic and compliance requirements.
- **Immutable posting**: Prevents silent changes to financial records.
- **Base currency**: Prevents FX drift in reporting.

---

## 10. Extensibility

- Easily add more transaction types (e.g., manual adjustments) with same pattern.
- UI can be extended to show audit panel, chain navigation, and more.

---

If you want this as a PDF, run:

```powershell
pandoc docs/journal-entry-system-spec.md -o docs/journal-entry-system-spec.pdf
```
