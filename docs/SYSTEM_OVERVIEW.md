# Educational Institution Accounting System - Complete Documentation

**Version:** 1.0  
**Last Updated:** November 4, 2025  
**Technology Stack:** Laravel 12.36.1, PHP 8.4.0, Livewire Volt, MySQL

---

## 📚 Table of Contents

1. [System Overview](#system-overview)
2. [Core Architecture](#core-architecture)
3. [Module Documentation](#module-documentation)
4. [Accounting Principles](#accounting-principles)
5. [Data Flow](#data-flow)
6. [User Roles & Permissions](#user-roles--permissions)
7. [Currency Management](#currency-management)
8. [Reporting System](#reporting-system)
9. [Best Practices](#best-practices)
10. [Technical Reference](#technical-reference)

---

## 📊 System Overview

### Purpose

This system is designed specifically for educational institutions (coding schools, training centers) to manage:
- **Student fee tracking** (invoices, payments, scholarships)
- **Program budgets** with real-time variance analysis
- **General accounting** (sales, expenses, assets)
- **Vendor management** (invoices, payments)
- **Staff payroll** (salary processing)
- **Multi-currency operations** with automatic conversion
- **Comprehensive reporting** for decision-making

### Key Features

✅ **Database-Driven Chart of Accounts** (not hard-coded)  
✅ **Automatic Budget vs Actual Tracking**  
✅ **Real-time Alert System** for budget overruns  
✅ **Multi-Currency Support** with exchange rates  
✅ **Separate Invoice/Payment Tracking** (proper accounting)  
✅ **Asset Management** with depreciation  
✅ **Vendor Invoice System** with payment terms  
✅ **Student Fee Management** with scholarships  
✅ **Comprehensive Audit Trail**  

---

## 🏗️ Core Architecture

### Foundation: Chart of Accounts

The Chart of Accounts is the **foundation** of the entire system. It's **NOT hard-coded** but stored in a database table for maximum flexibility.

#### Account Structure

```
accounts table:
├─ code (unique identifier, e.g., "1000", "5100")
├─ name (descriptive name, e.g., "Cash in Bank")
├─ type (asset, liability, equity, income, expense)
├─ description (optional details)
├─ parent_id (for hierarchical structure)
└─ is_active (enable/disable accounts)
```

#### Five Account Types (Standard Accounting)

1. **ASSET** 🟢
   - What the organization **owns**
   - Examples: Cash, Bank Accounts, Equipment, Buildings, Accounts Receivable
   - **Normal Balance:** Debit (increases with debits)

2. **LIABILITY** 🔴
   - What the organization **owes**
   - Examples: Loans, Accounts Payable, Accrued Expenses
   - **Normal Balance:** Credit (increases with credits)

3. **EQUITY** 🟣
   - Owner's stake in the organization
   - Examples: Capital, Retained Earnings, Grants Received
   - **Normal Balance:** Credit (increases with credits)

4. **INCOME** 🔵
   - Money **earned** by the organization
   - Examples: Student Fees, Course Sales, Grants, Donations
   - **Normal Balance:** Credit (increases with credits)

5. **EXPENSE** 🟠
   - Money **spent** by the organization
   - Examples: Salaries, Rent, Utilities, Materials, Equipment
   - **Normal Balance:** Debit (increases with debits)

#### Why Database-Driven?

✅ **Flexible** - Different institutions have different needs  
✅ **Scalable** - Add accounts as you grow  
✅ **Customizable** - Adapt to specific requirements  
✅ **Professional** - This is how QuickBooks, Xero, SAP work  
✅ **Future-proof** - No code changes needed to modify accounts  

---

## 📦 Module Documentation

### 0. Dashboard & Overview

**Location:** `/dashboard`

**Purpose:** Centralized overview of financial performance with real-time metrics.

**Key Metrics:**

#### Financial Overview
- **Total Income** - Revenue for selected period
- **Total Expenses** - Spending for selected period
- **Net Profit/Loss** - Income minus Expenses
- **Active Programs** - Currently running programs

#### Outstanding Balances
- **Unpaid Sales** - Invoices not yet paid
- **Pending Expenses** - Expenses awaiting payment

**Features:**

#### Period Filtering
- Today
- This Week
- This Month
- This Quarter
- This Year

#### Visual Analytics
- **Monthly Trend Chart** - Income vs Expenses over time
- **Program Performance** - Income/Expense breakdown by program
- **Top Customers** - Highest revenue contributors
- **Expense Categories** - Spending distribution
- **Currency Breakdown** - Multi-currency transaction summary

#### Quick Actions
- Create Sale
- Record Expense
- View Reports
- Access Budget Tracking

**Role-Based Display:**
- **Admin** - Full access to all metrics
- **Accountant** - Financial metrics and reports
- **Staff** - Limited view (assigned programs only)

**Technical Details:**
- Real-time calculations (no caching)
- Multi-currency support with base conversion
- Date range filtering
- Chart.js for visualizations

---

### 1. Chart of Accounts

**Location:** `/accounts`

**Purpose:** Central repository for all financial accounts used throughout the system.

**Features:**
- Create/Edit/Delete accounts
- Hierarchical structure (parent-child relationships)
- Search and filter by type
- Prevent deletion if account has transactions
- Visual stats by account type
- Quick links to Budget, Expenses, and Sales

**Usage:**
- All transactions (Sales, Expenses, Vendor Invoices, Student Invoices) must link to an account
- Accounts provide structure for reporting
- Enables detailed financial analysis

**Related Documentation:** See CHART_OF_ACCOUNTS.md (create if needed)

---

### 2. Budget vs Actual System

**Location:** `/budgets`

**Purpose:** Track program budgets with automatic calculation of actual performance and variance analysis.

**Key Concepts:**

#### Manual Entry (Budget Planning)
- `income_budget` - Expected income
- `expense_budget` - Expected expenses
- `start_date`, `end_date` - Budget period

#### Automatic Calculation (Real-time)
- **Actual Income** = Sum of all Sales in period for program
- **Actual Expenses** = Sum of all Expenses in period for program
- **Income Variance** = Actual Income - Budgeted Income
- **Expense Variance** = Actual Expenses - Budgeted Expenses
- **Utilization %** = (Actual / Budget) × 100

#### Alert System

**🟢 GREEN (Healthy)**
- Expense utilization < 70%
- Spending on track with time elapsed
- Action: Continue current pattern

**🟡 YELLOW (Warning)**
- Expense utilization ≥ 70% OR
- Spending 10-20% faster than time
- Action: Monitor closely, review spending

**🔴 RED (Critical)**
- Expense utilization ≥ 90% OR
- Spending >20% faster than time
- Action: Immediate review, stop non-essential spending

#### Budget Lifecycle

1. **DRAFT** - Being prepared, not tracking actuals
2. **APPROVED** - Reviewed and ready to activate
3. **ACTIVE** - Currently in effect, tracking actuals
4. **CLOSED** - Period ended, historical record

**Features:**
- Two-tab interface: Overview + Expense Breakdown
- Real-time variance calculations
- Progress bars for time/income/expenses
- Collapsible expense categories with line items
- Clickable expenses linking to filtered expense view
- Budget reallocation tracking

**Related Documentation:** See BUDGET_SYSTEM.md (already exists)

---

### 3. Sales & Income

**Location:** `/sales`

**Purpose:** Record income transactions and track customer payments.

**Architecture:**
- **Sale (Invoice)** - Records the income transaction
  - Links to Account (Chart of Accounts)
  - Links to Program (if applicable)
  - Links to Customer (if applicable)
  - Has amount, currency, exchange rate
  - Status: unpaid/partially_paid/paid

- **CustomerPayment** - Records actual payment received
  - Links to Sale
  - Payment date, amount, method
  - Reference number for tracking
  - Automatically updates Sale status

**Why Separate?**
- **Accrual Accounting** - Record income when earned, not when paid
- **Cash Flow Tracking** - Know what's owed vs what's received
- **Aging Reports** - Identify overdue payments
- **Proper Accounting** - Follows GAAP/IFRS standards

**Features:**
- Compact table view with clickable rows
- Sale detail page with payment recording
- Payment history display
- Automatic status updates
- Multi-currency support
- Links to Chart of Accounts

---

### 4. Expenses

**Location:** `/expenses`

**Purpose:** Track all organizational spending.

**Structure:**
- Links to Account (Chart of Accounts)
- Links to Program (for budget tracking)
- Category (salaries, utilities, materials, equipment, travel, welfare, etc.)
- Amount, currency, exchange rate
- Payment tracking (paid/unpaid)
- Payment method, reference, proof of payment

**Categories:**
- Salaries
- Utilities
- Materials
- Equipment
- Travel
- Professional Services
- Maintenance
- Welfare
- Other

**Features:**
- Filter by program, category, status
- Payment status tracking
- Proof of payment upload
- Budget utilization display
- Multi-currency support
- Vendor assignment

---

### 5. Vendor Management

**Location:** `/vendors`

**Purpose:** Manage supplier relationships and track purchases.

**Vendor Information:**
- Name, contact details
- Service type (supplier, service_provider, contractor, utility, other)
- Payment terms
- Tax ID, registration details
- Active/inactive status

**Features:**
- Complete vendor directory
- Service type categorization
- Contact management
- Transaction history

---

### 6. Vendor Invoices (Bills)

**Location:** `/vendor-invoices`

**Purpose:** Track bills received from vendors (Accounts Payable).

**Architecture:**
- **VendorInvoice (Bill)** - Record of what we owe
  - Auto-generated invoice number (VIN000001)
  - Links to Vendor, Program, Account
  - Payment terms (immediate, net_7, net_15, net_30, net_60, net_90)
  - Due date (auto-calculated from payment terms)
  - Amount, currency, status
  - Vendor reference number

- **VendorPayment** - Record of payment made
  - Links to VendorInvoice
  - Payment date, amount, method
  - Reference number
  - Automatically updates invoice status

**Payment Terms:**
- **Immediate** - Due on invoice date
- **Net 7/15/30/60/90** - Due 7/15/30/60/90 days after invoice date

**Features:**
- Four stat cards: Total Invoices, Paid, Owed, Overdue Count
- Overdue indicators with days past due
- Payment recording modal
- Payment history display
- Automatic status updates
- Multi-currency support

---

### 7. Student Fee Management

**Locations:** `/students`, `/invoices`, `/payments`, `/scholarships`

**Purpose:** Manage student enrollment, billing, and payments.

**Architecture:**

#### Students
- Personal information
- Program enrollment
- Guardian details
- Scholarship assignments

#### Student Invoices
- Generated from Fee Structures
- Line items (tuition, materials, etc.)
- Due dates
- Status tracking
- Payment plan support

#### Student Payments
- Payment recording
- Allocation to invoices
- Payment method tracking
- Receipt generation

#### Scholarships
- Scholarship programs
- Eligibility criteria
- Award amounts
- Student assignments

**Features:**
- Automated invoicing
- Payment plan installments
- Scholarship application
- Payment reminders
- Aging reports

---

### 8. Asset Management

**Location:** `/assets`

**Purpose:** Track organizational assets with depreciation.

**Asset Information:**
- Asset details (name, category, serial number)
- Purchase information (date, cost, vendor)
- Location and assignment
- Photo upload capability
- Depreciation tracking
- Maintenance schedule

**Categories:**
- Computers & IT Equipment
- Furniture
- Vehicles
- Buildings
- Other

**Features:**
- Asset register with photos
- Depreciation calculation
- Assignment tracking
- Maintenance scheduling
- Disposal recording

---

### 9. Staff & Payroll

**Locations:** `/staff`, `/payroll`

**Purpose:** Manage staff information and process payroll.

**Staff Management:**
- Personal information
- Job details (position, department)
- Salary information
- Bank account details
- Employment dates

**Payroll Processing:**
- Payroll run creation
- Individual payroll items
- Deductions and bonuses
- Payment status tracking
- Export capabilities

**Features:**
- Staff directory
- Payroll generation
- Payment tracking
- History viewing
- Restricted to admin users

---

### 10. Reporting System

**Location:** `/reports`

**Purpose:** Generate financial reports for decision-making.

**Available Reports:**

#### Profit & Loss Statement
- Income summary by account
- Expense summary by account
- Net profit/loss calculation
- Period comparison
- Program filtering

#### Expense Breakdown
- Expenses by category
- Visual charts
- Program comparison
- Trend analysis

#### Sales by Program
- Revenue analysis per program
- Period comparison
- Growth trends
- Performance metrics

#### Currency Conversion Report
- Multi-currency transaction summary
- Exchange rate impact
- Conversion details
- Base currency totals

**Features:**
- Date range filtering
- Program filtering
- Export capabilities
- Visual charts
- Drill-down details

---

## 💰 Accounting Principles

### Double-Entry Accounting

Every transaction affects at least two accounts:

**Example: Student pays tuition fee**
```
Debit:  Cash in Bank (Asset increases)
Credit: Tuition Income (Income increases)
```

**Example: Pay instructor salary**
```
Debit:  Salary Expense (Expense increases)
Credit: Cash in Bank (Asset decreases)
```

### Accrual vs Cash Basis

**Accrual Accounting** (Used in this system)
- Record income when **earned** (invoice created)
- Record expense when **incurred** (bill received)
- Separate tracking of invoices and payments
- Better financial picture

**Cash Accounting** (Not used)
- Record income when **received**
- Record expense when **paid**
- No separation of invoices/payments
- Simpler but less accurate

### The Accounting Equation

```
Assets = Liabilities + Equity
```

**Example:**
```
Cash (Asset): 50,000,000 UGX
Equipment (Asset): 20,000,000 UGX
Loan (Liability): 15,000,000 UGX
Capital (Equity): 55,000,000 UGX

Check: 70,000,000 = 15,000,000 + 55,000,000 ✅
```

### Financial Statements

#### 1. Balance Sheet
Shows financial position at a specific date:
- Assets (what you own)
- Liabilities (what you owe)
- Equity (owner's stake)

#### 2. Income Statement (Profit & Loss)
Shows financial performance over a period:
- Income (revenue earned)
- Expenses (costs incurred)
- Net Profit/Loss (income - expenses)

#### 3. Cash Flow Statement
Shows cash movement:
- Operating activities
- Investing activities
- Financing activities

---

## 🔄 Data Flow

### Income Recording Flow

```
1. Create Sale/Student Invoice
   ↓
2. System records income against Account
   ↓
3. Invoice status: unpaid
   ↓
4. Record Payment (CustomerPayment/StudentPayment)
   ↓
5. System updates invoice status
   ↓
6. Budget actuals automatically update
   ↓
7. Reports reflect new income
```

### Expense Recording Flow

```
1. Receive Vendor Invoice or create Expense
   ↓
2. System records expense against Account
   ↓
3. Invoice/Expense status: unpaid
   ↓
4. Record Payment (VendorPayment or update Expense)
   ↓
5. System updates status
   ↓
6. Budget actuals automatically update
   ↓
7. Reports reflect new expense
```

### Budget Calculation Flow

```
Program Budget Active
   ↓
Every page load:
   ├─ Query Sales in period → Sum = Actual Income
   ├─ Query Expenses in period → Sum = Actual Expenses
   ├─ Calculate variances
   ├─ Calculate utilization percentages
   ├─ Evaluate alert level
   └─ Display current status
```

---

## 👥 User Roles & Permissions

### Admin
**Full Access:**
- All modules
- User management
- Company settings
- Audit trail
- Staff & Payroll
- Budget reallocations
- All reports

### Accountant
**Financial Management:**
- Chart of Accounts
- Sales & Expenses
- Vendor invoices
- Student invoices
- Budget viewing
- Financial reports
- Asset management

**No Access:**
- Staff management
- Payroll
- User management
- System settings

### Staff
**Limited Access:**
- View own information
- View assigned programs
- Basic reporting
- Student information (if assigned)

**No Access:**
- Financial modules
- System settings
- Other staff information

---

## 💱 Currency Management

### Multi-Currency Support

The system supports multiple currencies with automatic conversion to base currency.

**Base Currency:** Set in Company Settings (e.g., UGX)

**Currency Table:**
- Code (USD, EUR, GBP, UGX, etc.)
- Symbol ($, €, £, UGX)
- Exchange rate to base currency
- Active/inactive status

**Exchange Rate Tracking:**
- Historical rates stored
- Date-based rate lookup
- Automatic conversion calculations

### How It Works

**Example: Record USD expense in UGX system**

```
Expense Amount: $500 USD
Exchange Rate: 1 USD = 3,700 UGX (on transaction date)
Amount Base: 500 × 3,700 = 1,850,000 UGX

Storage:
- amount: 500
- currency: USD
- exchange_rate: 3700
- amount_base: 1,850,000 (calculated)

Reports use amount_base for consistency
```

---

## 📊 Reporting System

### Report Types

#### 1. Profit & Loss (Income Statement)
**Purpose:** Show income vs expenses for a period

**Calculation:**
```
Total Income (sum of all income accounts)
- Total Expenses (sum of all expense accounts)
= Net Profit/Loss
```

**Features:**
- Date range filtering
- Program filtering
- Account-level detail
- Period comparison

#### 2. Expense Breakdown
**Purpose:** Analyze spending by category

**Features:**
- Visual pie/bar charts
- Category totals
- Program comparison
- Trend analysis
- Drill-down to transactions

#### 3. Sales by Program
**Purpose:** Revenue analysis per program

**Features:**
- Program comparison
- Period over period growth
- Student enrollment correlation
- Performance metrics

#### 4. Currency Conversion Report
**Purpose:** Multi-currency transaction analysis

**Features:**
- Transactions by currency
- Exchange rate impact
- Conversion details
- Base currency totals

### Report Best Practices

✅ Run reports regularly (weekly/monthly)  
✅ Compare periods for trends  
✅ Investigate unusual variances  
✅ Use filters to drill down  
✅ Export for external analysis  

---

## ✅ Best Practices

### Chart of Accounts

✅ **Use Clear Account Codes**
   - Consistent numbering (1000s = Assets, 5000s = Expenses)
   - Logical grouping
   - Easy to remember

✅ **Create Hierarchical Structure**
   - Main accounts with sub-accounts
   - Enables detailed and summary reporting
   - Example: "Bank Accounts" parent with "Savings", "Checking" children

✅ **Don't Over-Complicate**
   - Start simple, add as needed
   - Too many accounts = confusion
   - Balance detail with simplicity

✅ **Document Account Purposes**
   - Use description field
   - Clear guidelines for staff
   - Consistent usage

### Budget Management

✅ **Base Budgets on Data**
   - Review historical performance
   - Account for growth/changes
   - Be realistic, not aspirational

✅ **Monitor Alerts Weekly**
   - Don't ignore yellow/red alerts
   - Investigate causes promptly
   - Take corrective action early

✅ **Link All Transactions**
   - Every sale → program
   - Every expense → program
   - Enables accurate tracking

✅ **Close Budgets Promptly**
   - Don't leave old budgets active
   - Maintains clean data
   - Enables historical analysis

### Transaction Recording

✅ **Record Promptly**
   - Don't delay data entry
   - Real-time accuracy
   - Better cash flow management

✅ **Use Correct Accounts**
   - Match transaction to proper account
   - Consistent classification
   - Accurate reporting

✅ **Attach Documentation**
   - Receipts, invoices, proof of payment
   - Audit trail
   - Dispute resolution

✅ **Separate Invoices from Payments**
   - Use proper invoice/payment system
   - Don't record payment as income
   - Accrual accounting compliance

### System Maintenance

✅ **Regular Backups**
   - Daily database backups
   - Test restore procedures
   - Offsite storage

✅ **Review Audit Trail**
   - Monitor system usage
   - Identify unusual activity
   - Training opportunities

✅ **Update Exchange Rates**
   - Keep currency rates current
   - Regular review (weekly/monthly)
   - Accurate conversions

✅ **Train Users**
   - Proper system usage
   - Accounting principles
   - Best practices

---

## 🔧 Technical Reference

### Technology Stack

**Backend:**
- Laravel 12.36.1
- PHP 8.4.0
- MySQL 8.0+

**Frontend:**
- Livewire Volt (class-based)
- TailwindCSS 3.x
- Alpine.js (via Livewire)

**Key Packages:**
- Laravel Fortify (authentication)
- Livewire 3.x
- Volt (component-based UI)

### Database Structure

**Core Tables:**
- `accounts` - Chart of Accounts
- `programs` - Educational programs
- `currencies` - Multi-currency support
- `exchange_rates` - Historical rates
- `company_settings` - System configuration

**Transaction Tables:**
- `sales` - Income transactions
- `customer_payments` - Sale payments
- `expenses` - Expense transactions
- `vendor_invoices` - Bills from vendors
- `vendor_payments` - Vendor bill payments

**Student Tables:**
- `students` - Student information
- `student_invoices` - Student bills
- `student_invoice_items` - Invoice line items
- `student_payments` - Student payments
- `payment_allocations` - Payment to invoice mapping
- `scholarships` - Scholarship programs
- `student_scholarships` - Student awards

**Budget Tables:**
- `program_budgets` - Budget definitions
- `budget_reallocations` - Budget transfers

**Asset Tables:**
- `assets` - Asset register
- `asset_categories` - Asset classification
- `asset_assignments` - Asset allocations
- `asset_maintenance` - Maintenance records

**Staff Tables:**
- `staff` - Staff information
- `payroll_runs` - Payroll batches
- `payroll_items` - Individual payroll records

### Key Models

**Account.php** - Chart of Accounts
- Dynamic database model
- Hierarchical relationships
- Transaction relationships

**ProgramBudget.php** - Budget Management
- Automatic actual calculations
- Variance analysis
- Alert system logic
- See BUDGET_SYSTEM.md for details

**Sale.php** - Income Transactions
- Links to Account, Program, Customer
- Multi-currency support
- Payment status tracking

**Expense.php** - Expense Transactions
- Links to Account, Program, Vendor
- Category management
- Payment tracking

**VendorInvoice.php** - Vendor Bills
- Auto invoice numbering
- Payment terms handling
- Due date calculation
- Overdue tracking

### File Structure

```
accounting/
├── app/
│   ├── Models/          # Eloquent models
│   ├── Livewire/        # Livewire components
│   ├── Services/        # Business logic
│   └── Http/            # Controllers, Middleware
├── resources/
│   └── views/
│       └── livewire/    # Volt component views
├── database/
│   ├── migrations/      # Database schema
│   └── seeders/         # Sample data
├── routes/
│   └── web.php         # Application routes
├── docs/               # Documentation
│   ├── SYSTEM_OVERVIEW.md (this file)
│   ├── BUDGET_SYSTEM.md
│   ├── ASSET_SYSTEM.md
│   └── CURRENCY_CONVERSION_REPORT.md
└── public/            # Public assets
```

### Performance Considerations

**Database Optimization:**
- Indexes on foreign keys
- Composite indexes for common queries
- Eager loading relationships

**Caching:**
- Exchange rates cached
- Account lists cached
- Report data cached (if needed)

**Query Optimization:**
- Use `select()` to limit columns
- Paginate large result sets
- Avoid N+1 queries

---

## � Debugging Strategies

### Common Issues & Solutions

#### 1. Budget Actuals Not Updating

**Symptoms:**
- Budget shows $0 for actual income/expenses
- Utilization percentages are 0%
- Variance calculations incorrect

**Causes:**
- Transactions not linked to program
- Transactions outside budget period
- Wrong date fields being queried

**Debugging Steps:**
```sql
-- Check if sales have program_id
SELECT id, amount, sale_date, program_id 
FROM sales 
WHERE program_id IS NULL;

-- Check if expenses have program_id
SELECT id, amount, expense_date, program_id 
FROM expenses 
WHERE program_id IS NULL;

-- Verify transactions in budget period
SELECT * FROM sales 
WHERE program_id = 1 
AND sale_date BETWEEN '2025-01-01' AND '2025-12-31';
```

**Solutions:**
✅ Update transactions to include program_id  
✅ Verify budget start/end dates are correct  
✅ Check that sale_date and expense_date are within period  
✅ Ensure amount_base is calculated (for multi-currency)  

---

#### 2. Reports Showing Zero or Empty

**Symptoms:**
- All reports display $0
- "No data found" messages
- Charts are empty

**Causes:**
- Wrong date range selected
- No transactions in database
- Filter excluding all data
- Currency conversion issues

**Debugging Steps:**
```sql
-- Check total transactions
SELECT COUNT(*) as sales FROM sales;
SELECT COUNT(*) as expenses FROM expenses;

-- Check date ranges
SELECT MIN(sale_date), MAX(sale_date) FROM sales;
SELECT MIN(expense_date), MAX(expense_date) FROM expenses;

-- Check amount_base calculation
SELECT id, amount, currency, exchange_rate, amount_base 
FROM sales 
WHERE amount_base IS NULL OR amount_base = 0;
```

**Solutions:**
✅ Adjust date range to include transactions  
✅ Verify data entry is complete  
✅ Check filter settings (program, account, category)  
✅ Update exchange rates and recalculate amount_base  

---

#### 3. Currency Conversion Errors

**Symptoms:**
- Wrong amounts in reports
- Exchange rate errors
- amount_base is NULL or 0

**Causes:**
- Missing exchange rates
- Exchange rate = 0
- Currency not active

**Debugging Steps:**
```sql
-- Check exchange rates
SELECT * FROM currencies;

-- Find transactions with missing conversions
SELECT id, amount, currency, exchange_rate, amount_base 
FROM sales 
WHERE currency != 'UGX' AND (exchange_rate IS NULL OR exchange_rate = 0);

-- Check for active currencies
SELECT code, name, exchange_rate, is_active 
FROM currencies 
WHERE is_active = 1;
```

**Solutions:**
✅ Add missing exchange rates in `/settings/currencies`  
✅ Ensure exchange_rate > 0 for all currencies  
✅ Run update query to recalculate amount_base  
✅ Set up automated exchange rate updates  

---

#### 4. Can't Delete Account

**Symptoms:**
- "Cannot delete account with transactions" error
- "Cannot delete account with sub-accounts" error
- Delete button doesn't work

**Causes:**
- Account has related transactions (by design)
- Account has child accounts
- Foreign key constraints

**Debugging Steps:**
```sql
-- Check for related sales
SELECT COUNT(*) FROM sales WHERE account_id = [account_id];

-- Check for related expenses
SELECT COUNT(*) FROM expenses WHERE account_id = [account_id];

-- Check for child accounts
SELECT * FROM accounts WHERE parent_id = [account_id];
```

**Solutions:**
✅ **DON'T DELETE** accounts with transactions (audit trail)  
✅ Mark account as inactive instead: `UPDATE accounts SET is_active = 0`  
✅ Reassign child accounts to different parent first  
✅ Create new account if restructuring is needed  

---

#### 5. Payment Status Not Updating

**Symptoms:**
- Invoice still shows "unpaid" after recording payment
- Payment recorded but status unchanged
- Incorrect remaining balance

**Causes:**
- Payment not linked to invoice
- amount_paid not calculated
- Status update logic not triggered

**Debugging Steps:**
```sql
-- Check payments for invoice
SELECT * FROM customer_payments WHERE sale_id = [sale_id];
SELECT * FROM vendor_payments WHERE vendor_invoice_id = [invoice_id];
SELECT * FROM student_payments WHERE id IN (
    SELECT student_payment_id FROM payment_allocations 
    WHERE student_invoice_id = [invoice_id]
);

-- Check amount_paid vs amount
SELECT id, amount, amount_paid, status 
FROM sales WHERE id = [sale_id];

-- Verify payment sum
SELECT sale_id, SUM(amount) as total_paid 
FROM customer_payments 
WHERE sale_id = [sale_id]
GROUP BY sale_id;
```

**Solutions:**
✅ Verify payment foreign key is correct  
✅ Check model event listeners are working  
✅ Manually trigger `updatePaymentStatus()` method  
✅ Verify amount_paid calculation logic  

---

#### 6. Permissions/Access Denied

**Symptoms:**
- "403 Forbidden" errors
- Features not visible
- Can't access admin pages

**Causes:**
- Wrong user role
- Middleware blocking access
- Route not authorized

**Debugging Steps:**
```sql
-- Check user role
SELECT id, name, email, role FROM users WHERE id = [user_id];

-- Check route middleware
-- Review routes/web.php for ->middleware(['role:admin'])
```

**Solutions:**
✅ Update user role: `UPDATE users SET role = 'admin' WHERE id = [user_id]`  
✅ Check role middleware in routes  
✅ Verify auth()->user()->role in blade templates  
✅ Clear cache: `php artisan cache:clear`  

---

#### 7. Livewire Component Not Updating

**Symptoms:**
- Page doesn't refresh after action
- Changes not showing
- "Component not found" errors

**Causes:**
- Missing wire:model bindings
- JavaScript conflicts
- Cache issues

**Debugging Steps:**
```bash
# Clear Livewire cache
php artisan livewire:discover

# Clear all caches
php artisan optimize:clear

# Check browser console for errors
# Press F12 and look at Console tab
```

**Solutions:**
✅ Check wire:model bindings are correct  
✅ Verify method names match wire:click  
✅ Clear browser cache (Ctrl+Shift+R)  
✅ Check for JavaScript errors in console  
✅ Rebuild assets: `npm run build`  

---

#### 8. Slow Performance

**Symptoms:**
- Pages load slowly
- Reports take long time
- Dashboard hangs

**Causes:**
- N+1 query problems
- Missing database indexes
- Large dataset without pagination
- No caching

**Debugging Steps:**
```bash
# Enable query logging
# Add to AppServiceProvider boot():
DB::listen(function($query) {
    Log::info($query->sql, $query->bindings);
});

# Check slow query log
# Review storage/logs/laravel.log

# Analyze specific queries
EXPLAIN SELECT * FROM sales 
WHERE program_id = 1 
AND sale_date BETWEEN '2025-01-01' AND '2025-12-31';
```

**Solutions:**
✅ Add eager loading: `->with(['program', 'account'])`  
✅ Add database indexes on foreign keys  
✅ Use pagination for large lists  
✅ Cache frequently accessed data  
✅ Optimize queries with `select()` to limit columns  

---

#### 9. File Upload Failures

**Symptoms:**
- Asset photos not saving
- Proof of payment upload fails
- "File too large" errors

**Causes:**
- PHP upload limits too low
- Storage disk full
- Wrong file permissions
- Invalid file types

**Debugging Steps:**
```bash
# Check PHP limits
php -i | grep upload_max_filesize
php -i | grep post_max_size

# Check storage permissions
ls -la storage/app/public

# Check disk space
df -h
```

**Solutions:**
✅ Increase PHP limits in php.ini:
```ini
upload_max_filesize = 10M
post_max_size = 10M
```
✅ Check storage permissions: `chmod -R 775 storage`  
✅ Create symlink: `php artisan storage:link`  
✅ Verify allowed file types in validation  

---

#### 10. Database Connection Errors

**Symptoms:**
- "Connection refused" errors
- "SQLSTATE[HY000]" errors
- Can't connect to database

**Causes:**
- Wrong database credentials
- Database server not running
- Wrong host/port
- Firewall blocking

**Debugging Steps:**
```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check .env file
cat .env | grep DB_

# Test MySQL connection directly
mysql -h 127.0.0.1 -u root -p
```

**Solutions:**
✅ Verify .env database credentials  
✅ Start database server (WAMP/XAMPP)  
✅ Check DB_HOST (localhost vs 127.0.0.1)  
✅ Verify database exists: `CREATE DATABASE accounting;`  
✅ Clear config cache: `php artisan config:clear`  

---

### Debugging Tools

#### Laravel Telescope (Recommended)
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

**Access:** `http://localhost/telescope`

**Features:**
- Request monitoring
- Query analysis
- Exception tracking
- Log viewer
- Cache insights

#### Laravel Debugbar
```bash
composer require barryvdh/laravel-debugbar --dev
```

**Features:**
- Query time analysis
- Memory usage
- View rendering time
- Ajax request tracking

#### Built-in Tools

**Log Files:**
```bash
# View logs
tail -f storage/logs/laravel.log

# Clear logs
> storage/logs/laravel.log
```

**Artisan Commands:**
```bash
# Check environment
php artisan env

# List routes
php artisan route:list

# Database status
php artisan migrate:status

# Clear caches
php artisan optimize:clear
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

**SQL Query Logging:**
```php
// In AppServiceProvider boot()
if (app()->environment('local')) {
    DB::listen(function($query) {
        logger()->info(
            $query->sql,
            [
                'bindings' => $query->bindings,
                'time' => $query->time
            ]
        );
    });
}
```

---

### Best Practices for Debugging

✅ **Start with Logs**
- Check `storage/logs/laravel.log` first
- Look for error stack traces
- Note the timestamp of errors

✅ **Isolate the Problem**
- Reproduce the issue consistently
- Test in isolation (remove other variables)
- Check one thing at a time

✅ **Use dd() and dump()**
```php
// Die and dump
dd($variable);

// Dump and continue
dump($variable);

// Dump to log
logger()->info('Debug:', ['data' => $variable]);
```

✅ **Check Browser Console**
- Press F12 in browser
- Look for JavaScript errors
- Check Network tab for failed requests
- Verify Livewire requests

✅ **Database First**
- Verify data exists
- Check relationships
- Test queries directly in SQL
- Validate foreign keys

✅ **Test in Tinker**
```bash
php artisan tinker

>>> $account = Account::find(1);
>>> $account->sales()->count();
>>> Sale::whereBetween('sale_date', ['2025-01-01', '2025-12-31'])->sum('amount');
```

✅ **Document Solutions**
- Note what worked
- Update team documentation
- Share with other developers
- Add comments in code

---

## �📞 Support & Maintenance

### Getting Help

1. **Review Documentation**
   - This file (SYSTEM_OVERVIEW.md)
   - Module-specific docs (BUDGET_SYSTEM.md, etc.)
   - Inline code comments

2. **Check Audit Trail**
   - System logs
   - User activity
   - Error messages

3. **Contact Support**
   - Development team
   - System administrator
   - Accounting team lead

### Common Issues

**Issue:** Budget actuals not updating
- **Cause:** Transactions not linked to program
- **Solution:** Ensure all sales/expenses have program_id

**Issue:** Reports show zero
- **Cause:** Wrong date range or no transactions
- **Solution:** Check date filters, verify data entry

**Issue:** Currency conversion errors
- **Cause:** Missing exchange rates
- **Solution:** Update exchange rate table

**Issue:** Can't delete account
- **Cause:** Account has transactions
- **Solution:** Accounts with transactions can't be deleted (audit trail)

---

## 🚀 Future Enhancements

### Planned Features

- [ ] Automated bank reconciliation
- [ ] Bulk transaction import
- [ ] Custom report builder
- [ ] Email notifications for budget alerts
- [ ] Mobile app for expense recording
- [ ] Integration with payment gateways
- [ ] Advanced forecasting tools
- [ ] Multi-entity consolidation

### Customization Options

- Custom account structures
- Additional transaction categories
- Custom report templates
- Workflow approvals
- Additional user roles
- Industry-specific features

---

## 📝 Version History

- **v1.0** (November 2025)
  - Initial system implementation
  - Core accounting modules
  - Budget vs Actual system
  - Multi-currency support
  - Vendor invoice system
  - Student fee management
  - Asset management
  - Staff & Payroll

---

## 📄 License & Credits

**System:** Educational Institution Accounting System  
**Developer:** [Your Organization]  
**Framework:** Laravel (MIT License)  
**Documentation:** November 4, 2025

---

## 🎓 Conclusion

This accounting system provides a comprehensive, professional solution for educational institutions. It follows proper accounting principles with:

✅ **Database-driven Chart of Accounts** (flexible, not hard-coded)  
✅ **Proper separation of invoices and payments** (accrual accounting)  
✅ **Real-time budget tracking** with automatic calculations  
✅ **Multi-currency support** with conversion  
✅ **Comprehensive reporting** for decision-making  
✅ **Audit trail** for compliance  

The system is designed to grow with your institution while maintaining proper financial controls and reporting.

**Remember:** The Chart of Accounts is NOT hard-coded. It's a flexible, database-driven system that you can customize to meet your specific needs. This is the professional standard used by all major accounting software worldwide.

---

**For questions or support, refer to the specific module documentation or contact your system administrator.**
