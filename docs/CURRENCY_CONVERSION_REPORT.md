# Currency Conversion Report (Unrealized Gain/Loss)

This document explains how the Currency Conversion report computes per-transaction and total unrealized foreign-exchange (FX) gains/losses, why totals may appear as 0 in some cases, and how to diagnose anomalies.

## Overview
- View: `resources/views/livewire/reports/currency-conversion.blade.php`
- Sources: Sales (income) and Expenses within a selected date range and optional filters (currency, program)
- Base currency: `Currency::getBaseCurrency()` (e.g., UGX)
- Historical booking: Each transaction stores:
  - `amount` (original currency)
  - `currency` (e.g., USD, EUR)
  - `exchange_rate` (rate used at booking)
  - `amount_base` (amount converted to base at booking time)

## Formulas
- Current rate lookup (latest effective rate at now):
  - `currentRate = ExchangeRate::getRate(transaction.currency, baseCurrency.code)`
- Current base value:
  - $current\_base\_value = amount \times currentRate$
- Unrealized gain/loss per transaction:
  - $gain\_loss = current\_base\_value - amount\_base$
- Grand Total Gain/Loss (displayed at the top):
  - $\sum\limits_{tx} (amount_{tx} \times currentRate_{tx} - amount\_base_{tx})$

These are computed dynamically in the component's `with()` method by iterating all filtered transactions, not hard-coded.

## What can make Total Gain/Loss show 0?
One or more of the following is typically the reason:

1) All transactions are already in the base currency
- If `transaction.currency == base`, then `currentRate = 1` and `amount_base == amount`, so `gain_loss = 0`.

2) Current rate equals the historical booking rate
- If `currentRate == transaction.exchange_rate`, then $amount \times currentRate = amount\_base$ and `gain_loss = 0`.
- This happens when there has been no exchange-rate movement since booking, or the latest effective rate is the same as at booking time.

3) Missing or stale exchange rates
- If no rate exists for `(from = transaction.currency, to = baseCurrency.code)` effective on or before "now", your `ExchangeRate::getRate` helper may return a fallback (often `1` or the same stored rate), producing `0` gains/losses.
- Ensure `exchange_rates` has coverage for all active currencies with `effective_date <= now()`.

4) Filters exclude foreign-currency transactions
- If your date range or filters (currency/program) select only base-currency items or an empty set, the reported gain/loss can be 0.

5) Offsetting movements
- Gains on some transactions can offset losses on others, yielding a net total near 0.

6) Rounding/scale effects
- Small differences can be rounded away at 2 decimals, visually appearing as 0.

## Diagnostics checklist
Use this quick list to verify why you're seeing 0:

- Verify you have non-base transactions in the current filter:
  - Count transactions where `currency != base` within date range.
- Verify `amount_base` is populated at booking time:
  - Sales/Expenses models compute `amount_base` in `saving()` using the historical rate.
- Verify exchange-rate coverage:
  - `exchange_rates` contains entries for all used currencies → base, with `effective_date` before or on today.
- Compare current vs booked rate:
  - For a sample transaction, `currentRate = ExchangeRate::getRate(tx.currency, base)` vs `tx.exchange_rate`. If equal, gain/loss = 0.
- Check for offsets:
  - Sum gains and losses by currency to see if they cancel out.

## Performance notes
- The report currently queries `ExchangeRate::getRate` per transaction (simple and correct). If performance becomes an issue with large datasets, consider caching `getRate` results per currency per render, or prefetching the most recent rates once per currency.

## Edge cases & safeguards
- If base and original currencies are the same, gains/losses are always 0 (by definition).
- If `expense` amounts are converted the same way as `sales`, the sign convention remains consistent: a positive number means the base value increased relative to booking; negative means it decreased.
- Ensure consistent precision for rates (e.g., 4–6 decimals) to minimize rounding artifacts.

## Implementation references
- View logic: `resources/views/livewire/reports/currency-conversion.blade.php`
  - `with()` builds totals and the grand total
  - Per-row table also recomputes current-base value and gain/loss for display
- Models: `Sale`, `Expense`, `ExchangeRate`, `Currency`
  - `amount_base` is computed in model hooks at save time
  - `ExchangeRate::getRate(from, to)` returns the most recent effective rate

## Suggested improvements (optional)
- Cache current rates per currency during a single render to avoid repeated lookups.
- Show a small badge when current rate equals booked rate (predicting 0 gain/loss).
- Add a banner when no exchange rates are found for a selected currency in the period.
- Include a toggle to see gains and losses separately and by currency/program.
