# System Audit Report

**Auditor:** Ms. Hajara  \
**Audit Date:** 5 February 2026  \
**Scope:** Accounting System

## Summary
This report captures missing components and capabilities identified during the external audit. Items are grouped by module and list the specific features required to meet audit expectations. Each requirement below is **missing** as of the audit date and should be used as a remediation checklist.

---

## 1. Payment Voucher
Missing capabilities:
- **View:** Detailed view for each payment voucher.
- **Delete:** Ability to remove erroneous or orphaned vouchers that still appear after an expense or payment is deleted.
- **Edit:** Ability to correct voucher errors.
- **Print:** Printable voucher copy for audit records.
- **Period selector:** Monthly, quarterly, and yearly filters for voucher reports.

---

## 2. Sales Ledger
Missing capabilities:
- **View:** Detailed sale information per entry.
- **Edit:** Error correction on sales.
- **Print:** Printable sales record for audit purposes.
- **Aging analysis:** Receivables aging buckets (e.g., 0–30, 31–60, 61–90, 90+ days) to track amounts owed by customers.
- **Estimates and quotations:** Pre-transaction estimates for planning and quotations that confirm prices and terms before committing to a sale.
- **Sales order:** Sales order confirmation with quantities, prices, and delivery terms before services are provided.
- **Till sale:** Point-of-sale transactions recorded instantly.
- **Product area code:** Track sales by product category/department.
- **Period selector:** Monthly, quarterly, and yearly filters for sales reports.

---

## 3. Profit and Loss Statement
Required format:
- **Sales**
- **Cost of sales**
- **Gross profit**
- **Expenses**
- **Net profit/loss**

Missing capabilities:
- **Print:** Printable statement for audit records.
- **Period selector:** Monthly, quarterly, and yearly filters.

---

## 4. Balance Sheet
Required format:
- **Fixed assets**
- **Current assets**
- **Owners' equity**
- **Long-term liabilities**
- **Short-term liabilities**

Missing capabilities:
- **Transaction drill-down:** Detailed transactions supporting each balance.
- **Print:** Printable statement for audit records.
- **Period selector:** Monthly, quarterly, and yearly filters.

---

## 5. Chart of Accounts
Missing capabilities:
- **Code:** Unique account code for each asset, liability, or expense.
- **Category:** Long-term vs short-term grouping for assets and liabilities to support Balance Sheet reporting and liquidity ratios.
- **Opening balance:** Should be an option inside chart of accounts, not a standalone feature.
- **Delete:** Ability to delete accounts that are no longer needed.

---

## 6. Cashbook
Missing capabilities:
- **Mobile money coverage:** Reflect MTN MoMo and Airtel Money transactions by period.
- **Period selector:** Monthly, quarterly, and yearly filters.

---

## 7. Expenses
Missing capabilities:
- **Category:** Group expenses by Paid, Unpaid, and Partially paid.
- **Period selector:** Monthly, quarterly, and yearly filters.
- **Aging analysis:** Payables aging buckets (e.g., 0–30, 31–60, 61–90, 90+ days) to track amounts owed to vendors.

---

## 8. Journal Entries
Missing capabilities:
- **Period selector:** Monthly, quarterly, and yearly filters.

---

## 9. UI Theme
Missing capability:
- **Background light:** Light background option/theme.

---

## Implementation Checklist (All Items Missing)
- [ ] Payment voucher view, edit, delete, print, and period selector.
- [ ] Sales ledger view, edit, print, aging analysis, estimates/quotations, sales orders, till sales, product area codes, and period selector.
- [ ] Profit & loss format updates, print option, and period selector.
- [ ] Balance sheet format updates, transaction drill-down, print option, and period selector.
- [ ] Chart of accounts code, category, opening balance option, and delete action.
- [ ] Cashbook mobile money coverage and period selector.
- [ ] Expenses category grouping, period selector, and aging analysis.
- [ ] Journal entries period selector.
- [ ] Light background UI theme.
