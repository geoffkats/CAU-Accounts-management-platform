# Immutable Journal Entry & Ledger System: Full Technical Specification

## 1. File & Symbol Inventory

### Models Changed
- `app/Models/Expense.php`: createJournalEntry, updated observer, base currency logic, immutable posting
- `app/Models/Sale.php`: createJournalEntry, updated observer, base currency logic, immutable posting
- `app/Models/CustomerPayment.php`: createJournalEntry, updated observer, base currency logic, immutable posting
- `app/Models/VendorPayment.php`: createJournalEntry, updated observer, base currency logic, immutable posting
- `app/Models/JournalEntry.php`: fillables, casts, relationships (replaces, replacements), void logic, LogsActivity trait
- `app/Models/JournalEntryLine.php`: LogsActivity trait

### Migrations Added
- `2025_11_05_170500_add_replaces_and_voided_at_to_journal_entries.php`: adds `replaces_entry_id` and `voided_at` columns

### Traits
- `app/Models/Concerns/LogsActivity.php`: hash-chained audit logging for all journal actions

### Views Changed
- `resources/views/livewire/journal-entries/show.blade.php`: badges for replaces, replaced-by, voided-at
- `resources/views/livewire/journal-entries/index.blade.php`: defaults to posted entries, shows imbalance badge, eager loads sale/customerPayment

### Routes
- `routes/web.php`: all Volt::route and Route::get for journal entries, ledgers, statements, expenses, sales, payments

---

## 2. Model Methods & Observers

### Expense, Sale, CustomerPayment, VendorPayment
- `createJournalEntry()`: creates posted entry in base currency
- `static::created`: auto-posts entry on create
- `static::updated`: voids old entry, creates new, links via `replaces_entry_id`

### JournalEntry
- `void()`: sets status to 'void', sets `voided_at`
- `replaces()`: belongsTo previous entry
- `replacements()`: hasMany next entries
- `totalDebits()`, `totalCredits()`, `isBalanced()`: sum lines, validate entry
- `LogsActivity`: logs all create/update/void actions

---

## 3. Database Schema

### journal_entries
- `replaces_entry_id`: nullable FK to journal_entries.id
- `voided_at`: nullable timestamp
- All other transaction FKs: expense_id, sales_id, customer_payment_id

### journal_entry_lines
- Standard double-entry: account_id, debit, credit, description

---

## 4. Routes & Endpoints

| Name                      | Path                              | Purpose                        | Middleware                |
|-------------------------- |-----------------------------------|------------------------------- |--------------------------|
| journal-entries.index     | /journal-entries                  | List all journal entries       | auth, role:admin,accountant |
| journal-entries.create    | /journal-entries/create           | Create manual journal entry    | auth, role:admin,accountant |
| journal-entries.show      | /journal-entries/{id}             | View journal entry details     | auth, role:admin,accountant |
| general-ledger            | /general-ledger                   | View full general ledger       | auth, role:admin,accountant |
| account-statement         | /account-statement/{id}           | View statement for account     | auth, role:admin,accountant |
| expenses.index            | /expenses                         | List expenses                  | auth, role:admin,accountant |
| sales.index               | /sales                            | List sales                     | auth, role:admin,accountant |
| vendor-invoices.index     | /vendor-invoices                  | List vendor invoices           | auth, role:admin,accountant |
| customers.index           | /customers                        | List customers                 | auth, role:admin,accountant |

---

## 5. Formulas & Validation

- **Journal Entry**: `|SUM(debits) - SUM(credits)| < 0.01` (must balance)
- **General Ledger**: For each account: `balance = SUM(debits) - SUM(credits)` (assets/expenses), or `balance = SUM(credits) - SUM(debits)` (liabilities/equity/income)
- **Account Statement**: Running balance per transaction
- **Base Currency**: All lines use `amount_base` for cross-ledger consistency
	- Posting policy: source transactions convert to base, entries post in base; report conversions happen at presentation.

---

## 6. UI Components & Features

- **Show View**: Badges for replaces, replaced-by, voided-at, links to previous/next entries; `$replacement` passed via component `with()` (no inline PHP).
- **Index View**: Defaults to `status=posted`; compact “Imbalance” badge if `!isBalanced()`; eager loads related source objects; date, type, status, search filters.
- **Audit Panel**: (Optional) Created/updated/voided timestamps and user
- **Sidebar Navigation**: Journal entries, ledgers, statements

### Index Behavior & Filters
- Query: `JournalEntry::with(['lines.account','creator','expense','income','sale','customerPayment'])->whereBetween('date', [$start,$end])`.
- Filters: `type` (all, expense, income, payment, adjustment, opening, closing), `status` (draft, posted, void), `search` (reference/description), `date` range.
- Default: `status=posted`, current month range.

---

## 7. Audit Trail Logic

- **LogsActivity**: On every create/update/void/delete, logs user, IP, URL, user agent, before/after changes, hash chain
- **Immutable Posting**: No edits to posted entries; only void-and-replace
- **Chain Navigation**: UI links for full replacement/void history

### Posting Lifecycle
1) Draft (optional for manual entries) → Validate balance.
2) Post → Locks content; stores `posted_at`; available in GL/Reports.
3) Edit a posted entry → Create replacement entry (new record), optionally reverse original; link via `replaces_entry_id`; mark original void or keep as replaced chain.

### Voiding & Replacement
- `void()` marks entry as `void` and sets `voided_at`.
- Replacement flows reference the prior entry via `replaces_entry_id`; UI shows both “Replaces” and “Replaced by” links.

---

## 8. Example Data Flows

- **Create Expense**: Auto-posts journal entry in base currency
- **Edit Expense**: Old entry voided, new entry created, linked via `replaces_entry_id`
- **General Ledger**: Aggregates all posted entries, shows running balances
- **Account Statement**: Shows all lines for selected account, with running balance
- **Audit Log**: Every create/update/void tracked with before/after, user, IP, hash

---

## 9. Test Cases

- **Create Transaction**: Expense/Sale/Payment posts a journal entry in base currency
- **Edit Transaction**: Old entry is voided, new entry created with `replaces_entry_id`
- **UI Badges**: Show correct “Replaces”, “Replaced by”, and “Voided at” info
- **Index Defaults**: Defaults to posted; filters behave correctly across types/status/date.
- **Audit Log**: All actions appear in `activity_logs` with correct before/after and hash
- **Edge Cases**: Currency/account changes, multiple edits, chain navigation

---

## 10. Troubleshooting

- Error: `Undefined variable $entry` on show page
	- Cause: Inline PHP computed `$replacement` before Volt bound `$entry` in some render paths.
	- Fix: Compute and pass `$replacement` in component `with()` and remove inline PHP.
- Cache issues after updates
	- Clear caches: `php artisan view:clear`, `php artisan cache:clear`, `php artisan route:clear`, restart server.
- Volt command not found
	- Some versions don’t include `livewire:volt:cache`. Use standard Laravel clears.

---

## 11. Extensibility & Compliance

- Add more transaction types with same pattern
- RESTful API endpoints for mobile/external systems
- Meets forensic and compliance requirements
- Prevents silent changes to financial records
- Tamper detection via hash chain

Recommended enhancements
- Period locks (prevent posting/voiding into closed periods)
- Approval workflow (prepare → approve → post) with roles
- Enum validation and DB constraints for `type`/`status`
- Replace-on-edit flow with automatic reversing entries
- Closing and opening entry helpers/wizards
- Comprehensive tests for lifecycle and filters

---

## 12. Migration & Setup

- Run all migrations
- Confirm all relationships and UI features
- Test all endpoints and audit logs

---

This spec covers every file, method, route, formula, and logic added or changed for the new journal entry and ledger system. For PDF export, use:

```powershell
pandoc docs/journal-entry-system-full-spec.md -o docs/journal-entry-system-full-spec.pdf
```
