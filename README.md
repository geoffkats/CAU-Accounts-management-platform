# Code Academy Uganda - Complete Accounting System

A comprehensive, full-featured accounting system designed specifically for Code Academy Uganda to manage all financial operations including income tracking, expense management, assets, budgeting, payroll, and detailed financial reporting.

## 🎯 System Overview

Complete accounting solution with audit-ready reporting for educational institutions, including program-based financial tracking, multi-currency support, budgeting, accounts receivable, and print-ready financial statements.

## ✨ Key Features

### 1. Core Modules

- **Programs / Projects Management** - Central entity for tracking educational programs (Code Camp, Code Club, etc.)
- **Chart of Accounts** - Complete accounting structure (Assets, Liabilities, Income, Expenses, Equity)
- **Sales / Income Tracking** - Record program-linked income with invoice management
- **Sales Document Types** - Invoice, Estimate, Quotation, Sales Order, and Till Sale (POS) workflows
- **Sales Ledger** - Detailed sales history with aging analysis and period filters
- **Expense Management** - Track spending with receipt attachments and categorization
- **Payment Vouchers** - Create, edit, delete, view, and print vouchers with period filters
- **Vendors & Customers** - Maintain contact databases with payment history
- **Asset Management** - Track fixed assets, depreciation, maintenance, and assignments
- **Budget Management** - Create program budgets, track actual vs budget, and manage reallocations
- **Multi-Currency Support** - Handle transactions in multiple currencies with automatic conversion
- **Payroll System** - Process staff payroll with automatic journal entries
- **Accounts Receivable** - Student invoicing, payment plans, installments, and payment tracking
- **Fee Structures & Scholarships** - Manage program fees and student scholarships
- **Activity Logging** - Comprehensive audit trail for all system activities
- **Journal Entries** - Manual and system entries with period filters
- **User Management** - Admin and Accountant roles with secure access control
- **Company Settings** - Configure company info, currency (UGX), fiscal year, and preferences

### 2. Key Reports

✅ **Profit & Loss by Program** - View income, expenses, and profit for each program
✅ **Balance Sheet** - Assets, liabilities, and equity in audit-ready format
✅ **Trial Balance** - Period-based trial balance with drill-down
✅ **Cashbook** - Cash, MTN Momo, and Airtel Money summaries by period
✅ **Budget vs Actual** - Compare actual spending against budgeted amounts
✅ **Expense Breakdown** - Analyze spending by category, program, or vendor
✅ **Sales Ledger** - Detailed sales entries with aging analysis
✅ **Sales by Program** - Track revenue performance per program
✅ **Asset Reports** - Track asset values, depreciation, and assignments
✅ **Accounts Receivable Aging** - Monitor outstanding student payments
✅ **Currency Conversion Reports** - Track multi-currency transactions
✅ **Payroll Reports** - View payroll summaries and history
✅ **Activity Logs** - Comprehensive audit trail reports
✅ **Audit Report** - Consolidated audit-ready summaries
✅ **Dashboard** - Real-time overview with key metrics and trends

### 3. Export Capabilities

- CSV export for all reports
- Print/PDF output for key statements and vouchers (A4 and 80mm receipt formats)
- Data backup and export functionality

## 🚀 Technology Stack

- **Framework**: Laravel 12
- **Frontend**: Livewire Volt + Flux UI Components
- **Database**: SQLite (easily switchable to MySQL)
- **Authentication**: Laravel Fortify with 2FA support
- **Currency**: Uganda Shillings (UGX)

## 📋 Installation

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js & NPM
- Database (SQLite included, MySQL optional)

### Setup Instructions

1. **Clone the repository**
```bash
cd c:\wamp64\www\accounting\accounting
```

2. **Install PHP dependencies**
```bash
composer install
```

3. **Install NPM dependencies**
```bash
npm install
```

4. **Environment Configuration**
```bash
cp .env.example .env
php artisan key:generate
```

5. **Configure Database** (edit .env file)
```env
DB_CONNECTION=sqlite
DB_DATABASE=C:\wamp64\www\accounting\accounting\database\database.sqlite
```

6. **Run Migrations and Seed Data**
```bash
php artisan migrate:fresh --seed
```

This will create:
- Admin user: `admin@codeacademy.ug` / `password`
- Accountant user: `accountant@codeacademy.ug` / `password`
- Sample programs, accounts, customers, and vendors

7. **Build Assets**
```bash
npm run build
```

8. **Start Development Server**
```bash
php artisan serve
```

Visit: `http://localhost:8000`

## 👥 User Roles

### Admin
- Full access to all modules
- Can create/edit/delete all records
- Manage users and settings
- View all reports

### Accountant
- Access to accounting modules
- Can create/edit transactions
- View reports
- Limited settings access

## 📊 Using the System

### 1. Programs Management
Navigate to **Programs** to:
- Create new educational programs
- Assign program managers
- Set budgets and timelines
- Track program status (Planned, Active, Completed)

### 2. Recording Income
Navigate to **Sales** to:
- Create invoices for program fees
- Link sales to specific programs
- Track payment status (Paid/Unpaid)
- Manage customer information

### 3. Recording Expenses
Navigate to **Expenses** to:
- Record program-related expenses
- Upload receipt attachments
- Categorize spending
- Link expenses to programs

### 4. Viewing Reports
Navigate to **Reports** to access:
- **Profit & Loss**: See income vs expenses by program
- **Expense Breakdown**: Analyze spending patterns
- **Dashboard**: Overview of key metrics

### 5. Company Settings
Navigate to **Settings > Company** to:
- Update company information
- Upload company logo
- Set fiscal year dates
- Configure currency and formats

## 💡 Competitive Advantages vs QuickBooks

| Feature | QuickBooks | This System |
|---------|-----------|-------------|
| Program Tracking | Uses "Classes" (cumbersome) | Programs are core entities |
| Education Focus | Generic business tool | Built for educational institutions |
| Student Management | Not available | Full student invoicing & payment plans |
| Asset Management | Basic tracking | Complete with depreciation & maintenance |
| Budget Management | Limited | Program-based budgets with reallocations |
| Multi-Currency | Paid tier only | Built-in with automatic conversion |
| Local Fit | Needs customization | Native UGX and education model |
| Offline Access | Mostly cloud/paid | Offline capable with sync |
| Custom Reports | Limited templates | Fully customizable |
| Cost | Monthly subscription | One-time/internal use |

## 🔄 Recent Enhancements

- ✅ Budget vs Actual comparison per program
- ✅ Asset management with depreciation tracking
- ✅ Multi-currency support with exchange rates
- ✅ Payroll processing system
- ✅ Student invoicing and payment plans
- ✅ Budget reallocation workflows
- ✅ Activity logging and audit trails
- ✅ Scholarship management
- ✅ Payment reminders and notifications
- ✅ Audit fixes across all modules (period filters, print views, aging analysis)
- ✅ Sales document type workflows and print templates
- ✅ Default light UI theme for consistent audit visibility
- ✅ Chart of accounts delete with reassignment workflow

## 🚧 Future Enhancements

- 🏦 Bank reconciliation
- 📄 PDF receipt and invoice generator
- 📈 Advanced dashboard charts with drill-downs
- 🏢 Multi-site program tracking
- 📱 Mobile app for expense capture
- 🔔 Advanced notification system
- 📊 Custom report builder

## 🗂️ Database Schema

### Core Tables
- `users` - System users with roles
- `programs` - Educational programs/projects
- `program_budgets` - Program budget planning and tracking
- `budget_reallocations` - Budget reallocation requests and approvals
- `accounts` - Chart of accounts
- `sales` - Income transactions
- `expenses` - Expense transactions
- `customers` - Customer contacts
- `vendors` - Vendor contacts
- `vendor_invoices` - Vendor invoice tracking
- `vendor_payments` - Vendor payment records
- `students` - Student information
- `student_invoices` - Student billing
- `student_invoice_items` - Invoice line items
- `student_payments` - Student payment records
- `payment_plans` - Student payment plan configurations
- `payment_plan_installments` - Installment schedules
- `payment_allocations` - Payment distribution across invoices
- `payment_reminders` - Automated payment notifications
- `fee_structures` - Program fee configurations
- `scholarships` - Scholarship definitions
- `student_scholarships` - Student scholarship assignments
- `assets` - Fixed asset tracking
- `asset_categories` - Asset classification
- `asset_assignments` - Asset assignment to staff/programs
- `asset_maintenance` - Maintenance records
- `currencies` - Currency definitions
- `exchange_rates` - Historical exchange rates
- `staff` - Staff member records
- `payroll_items` - Payroll configuration items
- `payroll_runs` - Payroll processing records
- `activity_logs` - System audit trail
- `company_settings` - System configuration

## 🔒 Security

- Role-based access control
- Password hashing with bcrypt
- CSRF protection
- SQL injection prevention
- XSS protection
- Optional 2FA authentication

## 📞 Support

For issues or questions:
- Email: admin@codeacademy.ug
- Check application logs: `storage/logs/laravel.log`

## ✅ System Status

The system is **production-ready** with the following capabilities:
- ✅ Complete financial transaction recording and tracking
- ✅ Program-based accounting and profitability analysis
- ✅ Full asset lifecycle management
- ✅ Budget planning and monitoring
- ✅ Multi-currency transaction support
- ✅ Comprehensive payroll processing
- ✅ Student accounts receivable management
- ✅ Payment plans and installment tracking
- ✅ Scholarship management
- ✅ Vendor invoice and payment tracking
- ✅ Complete audit trail and activity logging
- ✅ Robust reporting suite with export capabilities
- ✅ Audit-ready print layouts and standardized paper sizes
- ✅ Role-based access control and security

## 📝 License

Proprietary - Code Academy Uganda

---

**Built with ❤️ for Code Academy Uganda**
# accounting-system
