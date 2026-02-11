# Journal Entries Index: Behavior & Filters

## Overview
Displays journal entries with filters and summaries. Defaults to posted entries to align with proper accounting views.

## Query & Eager Loads
- Base query: `JournalEntry::with(['lines.account','creator','expense','income','sale','customerPayment'])`
- Date filter: `whereBetween('date', [$startDate, $endDate])`
- Ordering: `latest('date')->latest('id')`

## Filters
- `type`: all | expense | income | payment | adjustment | opening | closing
- `status`: draft | posted | void (default: posted)
- `search`: reference or description (LIKE)
- `date range`: startDate → endDate (defaults: current month)

## Columns
- Date, Reference, Type, Description, Debits, Credits, Status, Actions
- Imbalance badge: Shown if `!$entry->isBalanced()` (should be rare; surfaced to catch anomalies)

## Totals (per row)
- `totalDebits()` and `totalCredits()` derived from entry lines
- Entries must be balanced by creation/posting guards; badge flags legacy/manual issues

## Actions
- View Details → `journal-entries.show`
- (Optional future) Reverse / Replace on the show view

## Files
- Component: `resources/views/livewire/journal-entries/index.blade.php`
- Model: `app/Models/JournalEntry.php`

## Notes
- Eager loading avoids N+1 for lines/accounts and source transactions.
- Defaulting to posted improves trust in listing; drafts/voids are filterable when needed.
