# Code Academy Uganda - Accounting Software MVP

A simple, intuitive accounting system designed specifically for Code Academy Uganda to track income, expenses, and profitability by program or service.

## 🎯 MVP Goal

Track income, expenses, and profitability by program with core accounting functions and room for expansion.

## ✨ Key Features

### 1. Core Modules

- **Programs / Projects Management** - Central entity for tracking educational programs (Code Camp, Code Club, etc.)
- **Chart of Accounts** - Complete accounting structure (Assets, Liabilities, Income, Expenses, Equity)
- **Sales / Income Tracking** - Record program-linked income with invoice management
- **Expense Management** - Track spending with receipt attachments and categorization
- **Vendors & Customers** - Maintain contact databases
- **Comprehensive Reports** - Profit & Loss by Program, Expense Breakdown, Sales Analysis
- **User Management** - Admin and Accountant roles with secure access control
- **Company Settings** - Configure company info, currency (UGX), fiscal year, and preferences

### 2. Key Reports

✅ **Profit & Loss by Program** - View income, expenses, and profit for each program
✅ **Expense Breakdown** - Analyze spending by category, program, or vendor
✅ **Sales by Program** - Track revenue performance per program
✅ **Dashboard** - Real-time overview with key metrics and trends

### 3. Export Capabilities

- CSV export for all reports
- PDF export ready (implementation pending)
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
| Local Fit | Needs customization | Native UGX and education model |
| Offline Access | Mostly cloud/paid | Offline capable with sync |
| Custom Reports | Limited templates | Fully customizable |
| Cost | Monthly subscription | One-time/internal use |

## 🔄 Future Enhancements (Post-MVP)

- ✨ Budget vs Actual comparison per program
- 🏦 Bank reconciliation
- 💰 Payroll module for facilitators
- 📦 Inventory tracking for STEM kits
- 📄 PDF receipt and invoice generator
- 📈 Advanced dashboard charts
- 🏢 Multi-site program tracking

## 🗂️ Database Schema

### Core Tables
- `users` - System users with roles
- `programs` - Educational programs/projects
- `accounts` - Chart of accounts
- `sales` - Income transactions
- `expenses` - Expense transactions
- `customers` - Customer contacts
- `vendors` - Vendor contacts
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

## ✅ MVP Success Criteria

The MVP is complete when you can:
- ✅ Record income & expenses by program
- ✅ View profit/loss by program at a glance
- ✅ Export reports (CSV)
- ✅ Track outstanding payments
- ✅ Invite users to manage records

## 📝 License

Proprietary - Code Academy Uganda

---

**Built with ❤️ for Code Academy Uganda**
# accounting-system
