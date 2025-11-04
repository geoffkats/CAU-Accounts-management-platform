# Accounting System Features

## System Overview
Comprehensive accounting and financial management system for educational institutions with support for multi-currency transactions, program-based budgeting, and compliance tracking.

**Tech Stack:** Laravel 12.36.1, PHP 8.4.0, Livewire Volt, Tailwind CSS, SQLite, Spatie Activity Log

---

## ✅ Implemented Features

### 1. Multi-Currency Support (HIGH PRIORITY)
**Status:** ✅ Complete  
**Implementation Date:** November 2025

#### Features
- **Multiple Currency Support**: Handle USD, UGX, EUR simultaneously
- **Automatic Exchange Rate Updates**: Integration with exchangerate-api.com
- **Base Currency System**: All transactions auto-convert to base currency for reporting
- **Currency Management UI**: 
  - Activate/deactivate currencies
  - View and update exchange rates
  - Set base currency from Company Settings
- **Transaction-Level Currency**:
  - Currency selector in Sales/Expenses forms
  - Live conversion preview when creating transactions
  - Stores original currency, amount, and exchange rate
- **Multi-Currency Reports**:
  - Dashboard shows totals in base currency
  - Currency breakdown widget (income/expenses by currency)
  - All reports use base currency for aggregation
  - Dynamic currency symbols throughout the system

#### Technical Details
- **Tables**: `currencies`, `exchange_rates`
- **Models**: `Currency`, `ExchangeRate` with conversion methods
- **Service**: `CurrencyService` with 1-hour rate caching
- **Auto-Conversion**: Sales and Expenses calculate `amount_base` on save
- **SSL Fix**: Disabled SSL verification for Windows cURL compatibility

#### Why This Matters
Supports international donors, scholarships, and foreign partnerships with accurate multi-currency accounting.

---

### 2. Budget vs Actual Tracking (PER PROGRAM)
**Status:** ✅ Complete  
**Implementation Date:** November 2025

#### Features
- **Budget Management**:
  - Create quarterly (3-month) or annual (12-month) budgets per program
  - Set income and expense budgets in any active currency
  - Budget workflow: Draft → Approved → Active → Closed
  - Search, filter, and pagination on budget dashboard
  
- **Real-Time Variance Tracking**:
  - Automatic calculation of actual vs budget
  - Income/expense utilization percentages
  - Variance analysis (over/under budget)
  - Days elapsed vs days remaining tracking

- **Traffic Light Alert System**:
  - 🔴 **RED (Critical)**: ≥90% budget used OR spending >20% ahead of schedule
  - 🟡 **YELLOW (Warning)**: ≥70% budget used OR spending >10% ahead of schedule
  - 🟢 **GREEN (On Track)**: Spending aligns with time elapsed
  
- **Budget Alerts Page**:
  - Dedicated view for budgets needing attention
  - Filter by severity (critical/warning)
  - Sort by urgency (highest overspending first)
  - Alert count summary dashboard

- **Budget Detail View**:
  - Comprehensive budget overview with charts
  - Visual progress bars (time vs spending)
  - Activate approved budgets
  - Close active budgets when period ends
  - Alert banner for budgets needing attention

#### Technical Details
- **Tables**: `program_budgets`, `budget_reallocations`
- **Models**: `ProgramBudget`, `BudgetReallocation`
- **Smart Calculations**: Auto-computed actual income/expenses, variance, utilization
- **Alert Logic**: Compares spending pace to time elapsed percentage
- **Integration**: Works with multi-currency system (converts to base currency)

#### Why This Matters
Prevents overspending, provides early warning alerts, enables proactive financial management for educational programs.

---

### 3. Core Financial Management
**Status:** ✅ Complete

#### Sales/Income Tracking
- Record sales transactions with program assignment
- Multi-currency support with live conversion
- Customer management
- Sales reports and analytics
- Monthly trend analysis

#### Expense Management
- Track expenses by program and category
- Vendor management
- Receipt/proof attachment support
- Expense breakdown reports
- Category-wise analysis

#### Program Management
- Create and manage programs
- Program-specific financial tracking
- Budget assignment per program
- Active budget monitoring

---

### 4. Dashboard & Analytics
**Status:** ✅ Complete

#### Key Metrics
- Total revenue (base currency)
- Total expenses (base currency)
- Net profit/loss calculation
- Active programs count
- Alert notifications

#### Visualizations
- Monthly income/expense trends (chart)
- Expense breakdown by category (chart)
- Currency breakdown widget
- Budget alert indicators

#### Multi-Currency Overview
- Income/expenses by currency
- Net amounts per currency
- Base currency conversions

---

### 5. Reporting System
**Status:** ✅ Complete

#### Available Reports
- **Profit & Loss Statement**: Income vs expenses with net calculation
- **Expense Breakdown**: By category with percentages
- **Sales by Program**: Program-wise revenue analysis

#### Report Features
- Date range filtering
- Multi-currency support (base currency aggregation)
- Export-ready formatting
- Visual charts and tables

---

### 6. Company Settings
**Status:** ✅ Complete

#### Settings Management
- Company profile (name, address, email, phone, registration)
- Multi-currency configuration
- Base currency selection with visual cards
- "Set as Base" quick action buttons
- Link to advanced currency management

---

### 7. User Management & Authentication
**Status:** ✅ Complete (via Laravel Fortify)

#### Features
- User registration and login
- Two-factor authentication support
- Role-based access control (admin, accountant, audit)
- Password reset functionality

---

### 8. Activity Logging & Compliance
**Status:** ✅ Complete (via Spatie Activity Log)

#### Features
- Automatic logging of all financial transactions
- Tracks creates, updates, deletes
- User attribution for all actions
- Audit trail for compliance
- "LogsActivity" trait on all models

---

## 🚧 Partially Implemented Features

### 1. Budget Reallocation Workflow
**Priority:** High  
**Status:** 🟡 Database Ready, UI Pending

#### What's Done
- `budget_reallocations` table created
- `BudgetReallocation` model with relationships
- Status workflow: pending → approved → rejected
- Approval tracking (requested_by, reviewed_by)

#### What's Pending
- Reallocation request form UI
- Approval interface for reviewers
- Reallocation history view
- Impact preview before submitting

#### Why This Matters
Allows flexible budget adjustments between programs when priorities change or opportunities arise.

---

### 2. Asset Management
**Priority:** High  
**Status:** 🟡 Database Ready, UI Pending

#### What's Done
- Database tables created: `asset_categories`, `assets`, `asset_maintenance`, `asset_assignments`
- Models exist: `AssetCategory`, `Asset`, `AssetMaintenance`, `AssetAssignment`
- Asset status workflow: available → in_use → under_maintenance → disposed
- Depreciation fields ready: purchase_value, salvage_value, useful_life, depreciation_method
- Maintenance tracking structure in place
- Asset assignment to staff/programs ready

#### What's Pending
- Asset registration form UI
- Asset listing and management dashboard
- Depreciation calculator and reports
- Maintenance scheduling interface
- Asset transfer/assignment workflow UI
- Barcode/QR code generation for tracking
- Asset audit reports

#### Technical Details
- **Depreciation Methods**: Straight-line, Declining balance
- **Categories**: Buildings, Furniture, Equipment, Vehicles, Electronics, Other
- **Maintenance Types**: Routine, Repair, Upgrade, Inspection

#### Why This Matters
Track school property (buildings, equipment, vehicles), calculate depreciation for accurate financial statements, maintain audit compliance.

---

## 📋 High Priority Features (Not Yet Implemented)

### 1. Currency Conversion Reports
**Priority:** High  
**Status:** 🔴 Not Started

#### Planned Features
- Historical exchange rate tracking
- Multi-currency transaction reports with original currency and converted amounts
- Exchange rate gain/loss analysis
- Currency-wise profit & loss statements
- Transaction drill-down by currency
- Exchange rate trend charts
- Export to Excel with multiple currency columns
- Configurable date ranges for rate comparisons

#### Technical Approach
- Leverage existing `exchange_rates` table with `effective_date`
- Query transactions with their original currency and rate
- Calculate realized vs unrealized gains/losses
- Generate comparative reports showing amounts in both original and base currency

#### Why This Matters
Provides transparency for international donors, tracks currency exposure, identifies exchange rate impacts on profitability.

---

### 2. Payroll Management
**Priority:** High  
**Status:** 🔴 Not Started

#### Planned Features
- **Staff Salary Management**:
  - Employee master records with salary details
  - Salary scales and grades
  - Allowances and benefits tracking
  - Deduction types (taxes, loans, insurance)
  
- **Payroll Processing**:
  - Automated monthly payroll runs
  - Overtime and bonus calculations
  - Net pay computation
  - Bank transfer file generation
  - Payslip generation (PDF)

- **Tax & Compliance**:
  - PAYE (Pay As You Earn) calculations
  - NSSF (National Social Security Fund) contributions
  - Statutory deductions
  - Tax return reports
  
- **Reports**:
  - Payroll summary by program
  - Employee payment history
  - Tax liability reports
  - Cost center allocation
  - Year-to-date earnings

#### Technical Approach
- Create tables: `employees`, `salary_components`, `payroll_runs`, `payroll_details`, `deductions`
- Multi-currency support: Salaries in USD/UGX
- Integration with expense tracking for program budgets
- Automated payroll processing via scheduled tasks

#### Why This Matters
Largest expense for educational institutions, ensures compliance with labor laws, enables accurate program cost allocation.

---

### 3. Accounts Payable Management
**Priority:** High  
**Status:** 🔴 Not Started

#### Planned Features
- **Invoice Management**:
  - Vendor invoice registration
  - Invoice approval workflow (multi-level)
  - Payment scheduling and reminders
  - Partial payment tracking
  - Invoice matching (PO → Receipt → Invoice)
  
- **Payment Processing**:
  - Payment batch creation
  - Payment method tracking (bank transfer, cash, check)
  - Payment approval workflow
  - Bank reconciliation integration
  - Payment confirmation and receipts

- **Vendor Management**:
  - Vendor master data with payment terms
  - Credit limit tracking
  - Vendor performance ratings
  - Preferred vendor lists
  - Vendor statements

- **Reports**:
  - Accounts payable aging (30/60/90 days)
  - Cash flow forecasting
  - Vendor payment history
  - Outstanding liabilities by program
  - Payment due alerts

#### Technical Approach
- Create tables: `invoices`, `invoice_items`, `payments`, `payment_approvals`
- Workflow states: draft → submitted → approved → scheduled → paid
- Multi-currency invoice support
- Automated email notifications for approvals and due dates

#### Why This Matters
Manage vendor relationships, optimize cash flow, prevent late payments, track liabilities accurately.

---

### 4. Accounts Receivable Management
**Priority:** High  
**Status:** ✅ Complete (95%)  
**Implementation Date:** January 2025

#### Implemented Features
- **Student Fee Management**:
  - ✅ Student master data (registration with guardian info)
  - ✅ Fee structure by program/term/academic year
  - ✅ Mandatory and optional fee configuration
  - ✅ Scholarship application (full/partial/percentage types)
  - ✅ Student enrollment status tracking (active/graduated/suspended/withdrawn)

- **Invoice Generation**:
  - ✅ Individual invoice creation with dynamic line items
  - ✅ Fee structure dropdown (auto-fills description and amount)
  - ✅ Bulk invoice generation for entire programs/classes
  - ✅ Invoice preview before generation
  - ✅ Invoice status workflow (draft → sent → partially_paid → paid → overdue → cancelled)
  - ✅ Invoice numbering system (INV-202501-0001)

- **Payment Tracking**:
  - ✅ Payment recording with multiple methods (cash, bank_transfer, mobile_money, cheque, card)
  - ✅ Professional receipt generation with print functionality
  - ✅ Automatic payment allocation to invoices
  - ✅ Payment numbering system (PAY-202501-0001)
  - ✅ Payment history tracking per student
  - ⏳ Overpayment and credit management (future)
  - ⏳ Refund processing (future)

- **Collections Management**:
  - ✅ Outstanding balance tracking per student (dashboard stats)
  - ✅ Student detail page with invoice/payment history
  - ✅ Balance calculations (total invoiced, paid, outstanding, overdue)
  - ⏳ Automated payment reminder system (email/SMS) (future)
  - ⏳ Collection follow-up workflow (future)
  - ⏳ Bad debt provisioning (future)

- **Scholarship Management**:
  - ✅ Three scholarship types: full (100%), partial (fixed amount), percentage-based
  - ✅ Sponsor tracking
  - ✅ Active/inactive status control
  - ✅ Date range configuration (start/end dates)
  - ✅ Scholarship listing with search and filtering

- **Reports** (5% Pending):
  - ⏳ Accounts receivable aging report (30/60/90 days)
  - ⏳ Collection efficiency metrics
  - ⏳ Revenue recognition by program
  - ⏳ Payment trend analysis
  - ⏳ Scholarship impact reports

#### Pages Implemented (15 Total)
1. **Students**: index, create, edit, show (with tabs for overview/invoices/payments/contact)
2. **Fee Structures**: index, create, edit
3. **Invoices**: index, create, bulk-generate, show, edit
4. **Payments**: index, create, show
5. **Scholarships**: index, create, edit

#### Technical Implementation
- **Database Tables** (11): `students`, `fee_structures`, `fees`, `invoices`, `invoice_items`, `payments`, `payment_allocations`, `payment_plans`, `installments`, `scholarships`, `student_scholarships`
- **Models** (11): All with relationships, validation, and business logic
- **Auto-Numbering**: Student IDs (STU-2025-001), Invoice numbers (INV-202501-0001), Payment numbers (PAY-202501-0001)
- **Multi-Currency**: Full integration with exchange rate tracking
- **Activity Logging**: All AR operations tracked via Spatie
- **Status Workflows**: Automated status transitions based on balances
- **Navigation**: 5 menu items in "Student Fees" section

#### Why This Matters
Primary revenue source for schools, ensures fee collection, improves cash flow, supports scholarship management. System is production-ready for immediate use.

---

### 5. Bank Reconciliation
**Priority:** High  
**Status:** 🔴 Not Started

#### Planned Features
- **Bank Account Management**:
  - Multiple bank account support
  - Account details and balances
  - Bank statement upload (CSV/Excel)
  - Opening and closing balance tracking

- **Reconciliation Workflow**:
  - Automatic transaction matching (date + amount)
  - Manual transaction matching interface
  - Unmatched transaction identification
  - Bank charges and interest recording
  - Reconciliation approval process

- **Discrepancy Management**:
  - Outstanding checks tracking
  - Deposits in transit
  - Bank errors vs book errors
  - Adjustment entries
  - Variance analysis

- **Reports**:
  - Bank reconciliation statement
  - Unreconciled items report
  - Bank balance vs book balance
  - Cash position by account
  - Reconciliation history

#### Technical Approach
- Create tables: `bank_accounts`, `bank_statements`, `bank_transactions`, `reconciliations`, `reconciliation_items`
- Import parsers for common bank statement formats
- Fuzzy matching algorithm for transaction pairs
- Integration with sales/expense transactions

#### Why This Matters
Detects fraud and errors, ensures accurate cash balances, required for audits, improves financial control.

---

### 6. Advanced Budget Features
**Priority:** High  
**Status:** 🔴 Not Started

#### Planned Features
- **Automated Workflows**:
  - Cron job for budget status transitions (approved → active on start date)
  - Automatic closure at end date
  - Email alerts for budget warnings (70%/90% thresholds)
  - Daily digest for budget managers
  - Escalation alerts for critical overspending

- **Budget Templates**:
  - Create templates from historical budgets
  - Template library for recurring periods
  - Copy last year's budget with adjustments
  - Template sharing between programs

- **Multi-Year Planning**:
  - 3-5 year budget projections
  - Year-over-year comparisons
  - Rolling forecasts
  - Scenario planning (best/worst/expected)

- **Enhanced Analytics**:
  - Budget vs actual comparison charts (line/bar)
  - Variance trend analysis
  - Spending velocity tracking
  - Forecast to completion
  - Program performance benchmarking

- **Reallocation Workflow UI**:
  - Request form with justification
  - Impact preview (donor budgets)
  - Approval chain (manager → finance → director)
  - Reallocation history and audit trail
  - Batch reallocation support

#### Technical Approach
- Laravel scheduled tasks for automation
- Mail notifications (queued jobs)
- Chart.js for advanced visualizations
- Budget template versioning system
- Multi-step approval workflow

#### Why This Matters
Proactive budget management, reduces manual monitoring, enables data-driven decisions, improves forecast accuracy.

---

## 📋 Medium Priority Features (Not Yet Implemented)

### 7. Advanced Reporting
**Priority:** Medium  
**Status:** 🔴 Not Started

#### Planned Features
- Cash flow statements
- Balance sheet generation
- Trial balance reports
- General ledger reports
- Custom report builder
- Scheduled report emails
- PDF/Excel export for all reports

---

### 8. Donor Management
**Priority:** Medium  
**Status:** 🔴 Not Started

#### Planned Features
- Donor database with contact information
- Donation tracking by donor and program
- Pledge management
- Donor reports and thank-you letters
- Multi-year donor analytics
- Grant management integration

---

### 9. Grant Management
**Priority:** Medium  
**Status:** 🔴 Not Started

#### Planned Features
- Grant application tracking
- Grant-specific budgets
- Milestone and deliverable tracking
- Compliance reporting per grant
- Multi-grant program support
- Grant expense allocation

---

### 10. Inventory Management
**Priority:** Low  
**Status:** 🔴 Not Started

#### Planned Features
- Item catalog with categories
- Stock tracking (in/out/current)
- Reorder level alerts
- Inventory valuation (FIFO, LIFO, Average)
- Physical inventory reconciliation
- Inventory reports by location/program

---

### 11. Document Management
**Priority:** Low  
**Status:** 🔴 Not Started

#### Planned Features
- Centralized document repository
- File upload and categorization
- Document linking to transactions
- Version control
- Search and filter functionality
- Access control per document type

---

### 12. Mobile App/Responsive Design
**Priority:** Low  
**Status:** 🔴 Not Started

#### Planned Features
- Fully responsive web interface
- Mobile-optimized forms
- Touch-friendly navigation
- Offline capability for data entry
- Mobile receipt capture (photo upload)

---

### 13. API Integration
**Priority:** Low  
**Status:** 🔴 Not Started

#### Planned Features
- RESTful API for external systems
- Mobile app API endpoints
- Third-party accounting software integration
- Payment gateway integration (Stripe, PayPal)
- SMS notification service

---

### 14. Advanced User Permissions
**Priority:** Low  
**Status:** 🔴 Not Started

#### Planned Features
- Granular permission system (create, read, update, delete per module)
- Custom role creation
- Permission templates
- Program-specific access restrictions
- Time-based access controls

---

### 15. Audit & Compliance Features
**Priority:** Low  
**Status:** 🔴 Not Started

#### Planned Features
- Audit trail reports with filters
- Change history for all records
- Compliance checklist dashboard
- Regulatory report templates
- Data retention policies
- Backup and restore functionality

---

## 🎯 Recommended Implementation Priority

### Phase 1: Core Financial Operations (Q1 2026)
1. ✅ Multi-Currency Support (DONE)
2. ✅ Budget vs Actual Tracking (DONE)
3. � Budget Reallocation Workflow UI (Partially Complete - Database Ready)
4. 🟡 Asset Management UI (Partially Complete - Database Ready)
5. 🔴 Currency Conversion Reports (High Priority)

### Phase 2: Operational Efficiency (Q2 2026)
6. 🔴 Payroll Management (High Priority)
7. 🔴 Accounts Payable Management (High Priority)
8. ✅ Accounts Receivable Management (COMPLETE - High Priority)
9. 🔴 Bank Reconciliation (High Priority)
10. 🔴 Advanced Budget Features (High Priority)

### Phase 3: Enhanced Reporting (Q3 2026)
11. 🔴 Advanced Reporting (Cash Flow, Balance Sheet, Trial Balance)
12. 🔴 Donor Management
13. 🔴 Grant Management

### Phase 4: Optimization & Expansion (Q4 2026)
14. 🔴 Inventory Management
15. 🔴 Document Management
16. 🔴 Advanced User Permissions
17. 🔴 API Integration
18. 🔴 Mobile App/Responsive Design
19. 🔴 Audit & Compliance Features

---

## 📊 Feature Completion Status

**Total Features:** 17 major feature sets  
**Completed:** 9 (53%)  
**Partially Complete:** 2 (12%)  
**High Priority Pending:** 5 (29%)  
**Medium/Low Priority Pending:** 9 (53%)

### By Priority
- **High Priority**: 9/14 complete or in progress (64%)
  - ✅ 9 Complete
  - 🟡 2 Partially Complete (Database Ready)
  - 🔴 3 Not Started
- **Medium Priority**: 0/7 complete (0%)
- **Low Priority**: 0/5 complete (0%)

### Quick Status Legend
- ✅ **Complete**: Fully implemented and tested
- 🟡 **Partially Complete**: Database schema ready, UI pending
- 🔴 **Not Started**: Planning phase only

---

## 📋 Detailed Implementation Checklist

### Partially Complete Features (Immediate Action)

#### Budget Reallocation Workflow
- [x] Database migration created
- [x] BudgetReallocation model with relationships
- [x] Status workflow defined
- [ ] Create reallocation request form (from_budget → to_budget)
- [ ] Build approval interface for finance managers
- [ ] Add reallocation history view
- [ ] Implement impact preview (shows budget availability)
- [ ] Add email notifications for requests/approvals
- [ ] Add to sidebar navigation

#### Asset Management
- [x] Database migrations (4 tables)
- [x] AssetCategory, Asset, AssetMaintenance, AssetAssignment models
- [x] Status workflow defined
- [ ] Create asset category management UI
- [ ] Build asset registration form (with depreciation settings)
- [ ] Create asset listing/dashboard with search/filters
- [ ] Implement depreciation calculator (straight-line, declining balance)
- [ ] Build maintenance scheduling interface
- [ ] Create asset assignment workflow
- [ ] Generate barcode/QR codes for tracking
- [ ] Build depreciation schedule reports
- [ ] Create asset audit reports
- [ ] Add to sidebar navigation

### High Priority Features (Roadmap)

#### Currency Conversion Reports
- [ ] Design report layouts (multi-currency transaction list)
- [ ] Query builder for historical exchange rates
- [ ] Calculate realized vs unrealized gains/losses
- [ ] Build currency-wise P&L statement
- [ ] Add exchange rate trend charts
- [ ] Implement Excel export with dual currency columns
- [ ] Add date range filters for rate comparisons

#### Payroll Management
- [ ] Design database schema (employees, payroll_runs, deductions)
- [ ] Create employee master management UI
- [ ] Build salary scales and allowances configuration
- [ ] Implement payroll run automation (monthly)
- [ ] Calculate PAYE, NSSF, and statutory deductions
- [ ] Generate payslips (PDF)
- [ ] Create bank transfer file generation
- [ ] Build payroll reports by program
- [ ] Add tax liability reports

#### Accounts Payable
- [ ] Design database schema (invoices, payments, approvals)
- [ ] Create vendor invoice registration form
- [ ] Build multi-level approval workflow
- [ ] Implement payment scheduling system
- [ ] Create payment batch processing
- [ ] Build accounts payable aging report (30/60/90 days)
- [ ] Add automated payment reminders
- [ ] Create vendor statement generation

#### Accounts Receivable
- [x] Design database schema (students, fee_structures, invoices, payments, scholarships)
- [x] Create student master management UI (index, create, edit, show with tabs)
- [x] Build fee structure configuration (index, create, edit)
- [x] Implement invoice generation (single and bulk generation)
- [x] Create payment recording interface (with invoice allocation)
- [x] Build receipt generation and printing
- [x] Create scholarship management interface (full/partial/percentage types)
- [ ] Implement payment plan/installment support (future enhancement)
- [ ] Create automated payment reminders (email/SMS) (future enhancement)
- [ ] Build accounts receivable aging report (30/60/90 days)
- [ ] Build payment collection analytics dashboard

#### Bank Reconciliation
- [ ] Design database schema (bank_accounts, statements, reconciliations)
- [ ] Create bank account management UI
- [ ] Build bank statement upload (CSV/Excel parser)
- [ ] Implement automatic transaction matching algorithm
- [ ] Create manual matching interface
- [ ] Build discrepancy identification and resolution
- [ ] Generate bank reconciliation statement
- [ ] Create unreconciled items report

#### Advanced Budget Features
- [ ] Implement Laravel scheduled tasks for status transitions
- [ ] Build email notification system for budget alerts
- [ ] Create budget template library
- [ ] Build template creation from historical budgets
- [ ] Implement multi-year budget projections (3-5 years)
- [ ] Create budget vs actual comparison charts (Chart.js)
- [ ] Build variance trend analysis dashboard
- [ ] Implement scenario planning (best/worst/expected)
- [ ] Complete reallocation approval workflow UI

---

## 🔧 Technical Architecture

### Database Schema
- **Currencies**: `currencies`, `exchange_rates`
- **Transactions**: `sales`, `expenses` (with currency fields)
- **Programs**: `programs`, `program_budgets`, `budget_reallocations`
- **Entities**: `customers`, `vendors`, `users`
- **Settings**: `company_settings`
- **Compliance**: Activity logs (via Spatie)

### Key Services
- **CurrencyService**: Exchange rate management and conversion
- **ActivityLog**: Automatic compliance tracking

### Design Patterns
- Repository pattern for data access
- Service layer for business logic
- Livewire components for reactive UI
- Computed properties for dynamic calculations

---

## 📞 Support & Maintenance

### Known Issues
- SSL certificate verification disabled for Windows (exchange rate API)
- Budget status transitions require manual activation (automation pending)

### Maintenance Tasks
- Regular database backups
- Exchange rate updates (automatic via API)
- Activity log cleanup (consider retention policy)
- Performance monitoring for large datasets

---

## 📝 Notes

### Multi-Currency Considerations
- All financial aggregations use `amount_base` for consistency
- Original transaction currency preserved for audit trail
- Exchange rates stored with `effective_date` for historical accuracy
- 1-hour cache on exchange rates to minimize API calls

### Budget Alert Thresholds
- **Critical (Red)**: Immediate action required
- **Warning (Yellow)**: Monitor closely
- **On Track (Green)**: No action needed

### Security
- Laravel Fortify for authentication
- Role-based access control (admin, accountant, audit)
- Activity logging for all financial transactions
- Two-factor authentication available

---

**Last Updated:** November 3, 2025  
**Version:** 1.0  
**Maintained By:** Development Team
