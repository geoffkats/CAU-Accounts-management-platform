# Budget System Documentation

## 📊 Overview

The Budget System provides comprehensive program budget management with automatic calculation of actuals, variances, and utilization metrics based on real transaction data.

---

## 🎯 Core Concepts

### Manual Entry (Budget Planning)
These values are entered manually when creating a budget:
- **`income_budget`** - Expected income for the period
- **`expense_budget`** - Expected expenses for the period
- **`start_date`** and **`end_date`** - Budget period dates

### Automatic Calculation (Real-time)
These values are calculated automatically from actual transactions:
- **Actual Income** - Sum of all sales in the period
- **Actual Expenses** - Sum of all expenses in the period
- **All metrics, variances, and alerts** - Computed dynamically

---

## 📐 Calculation Formulas

### 1. Actual Values (Computed from Transactions)

#### Actual Income
```
actual_income = Σ(Sales.amount_base) 
WHERE sale_date ∈ [start_date, end_date]
AND program_id = budget.program_id
```

**Example:**
```
Budget Period: Jan 1 - Mar 31
Sales in period:
  - Jan 15: UGX 5,000,000 (Student fees)
  - Feb 10: UGX 3,000,000 (Student fees)
  - Mar 20: UGX 2,000,000 (Student fees)

Result: 10,000,000 UGX
```

#### Actual Expenses
```
actual_expenses = Σ(Expenses.amount_base)
WHERE expense_date ∈ [start_date, end_date]
AND program_id = budget.program_id
```

**Example:**
```
Budget Period: Jan 1 - Mar 31
Expenses in period:
  - Jan 5: UGX 1,500,000 (Instructor salaries)
  - Feb 15: UGX 800,000 (Equipment purchase)
  - Mar 10: UGX 500,000 (Materials)

Result: 2,800,000 UGX
```

---

### 2. Variance Analysis

#### Income Variance
```
income_variance = actual_income - income_budget
```

**Interpretation:**
- **Positive (+)** = ✅ Good! Earned MORE than expected
- **Zero (0)** = ✅ Perfect! Met exactly the target
- **Negative (-)** = ⚠️ Warning! Earned LESS than expected

**Example 1 - Favorable Variance:**
```
Budgeted Income: 10,000,000 UGX
Actual Income: 12,000,000 UGX
Variance: +2,000,000 UGX (20% above target)
```

**Example 2 - Unfavorable Variance:**
```
Budgeted Income: 10,000,000 UGX
Actual Income: 8,500,000 UGX
Variance: -1,500,000 UGX (15% shortfall)
```

#### Expense Variance
```
expense_variance = actual_expenses - expense_budget
```

**Interpretation:**
- **Positive (+)** = ❌ Bad! Spent MORE than budgeted (over budget)
- **Zero (0)** = ✅ Perfect! Spent exactly as budgeted
- **Negative (-)** = ✅ Good! Spent LESS than budgeted (under budget)

**Example 1 - Unfavorable Variance:**
```
Budgeted Expenses: 8,000,000 UGX
Actual Expenses: 9,500,000 UGX
Variance: +1,500,000 UGX (18.75% over budget)
```

**Example 2 - Favorable Variance:**
```
Budgeted Expenses: 8,000,000 UGX
Actual Expenses: 7,200,000 UGX
Variance: -800,000 UGX (10% saved)
```

---

### 3. Utilization Metrics

#### Income Utilization (Achievement Rate)
```
income_utilization = (actual_income / income_budget) × 100
```

**Interpretation:**
- **>100%** = Exceeded target (excellent) 🎉
- **100%** = Met target exactly (perfect) ✅
- **90-99%** = Close to target (good) 👍
- **80-89%** = Below target (concerning) ⚠️
- **<80%** = Significant shortfall (critical) 🚨

**Example:**
```
Budgeted Income: 10,000,000 UGX
Actual Income: 8,500,000 UGX
Utilization: (8,500,000 / 10,000,000) × 100 = 85%

Interpretation: Achieved 85% of income target
```

#### Expense Utilization (Spending Rate)
```
expense_utilization = (actual_expenses / expense_budget) × 100
```

**Interpretation:**
- **<70%** = Well under budget (safe) 🟢
- **70-89%** = Approaching budget (monitor) 🟡
- **90-99%** = Near budget limit (caution) 🟠
- **100%** = At budget limit (critical) 🔴
- **>100%** = Over budget (emergency) 🚨

**Example:**
```
Budgeted Expenses: 8,000,000 UGX
Actual Expenses: 7,200,000 UGX
Utilization: (7,200,000 / 8,000,000) × 100 = 90%

Interpretation: Spent 90% of budget
```

**Combined with Time Context:**
```
If 60% of budget period passed:
  90% spent vs 60% time = 🚨 RED ALERT (spending too fast)

If 95% of budget period passed:
  90% spent vs 95% time = 🟢 GREEN (on track)
```

---

### 4. Time Progress

#### Days Elapsed Percentage
```
days_elapsed_percentage = (days_from_start_to_today / total_period_days) × 100

Where:
  days_from_start_to_today = today - start_date
  total_period_days = end_date - start_date
```

**Example:**
```
Start Date: Jan 1, 2025
End Date: Mar 31, 2025 (90 days total)
Today: Feb 15, 2025 (45 days elapsed)

Calculation: (45 / 90) × 100 = 50%
Interpretation: Budget period is 50% complete
```

**Usage in Alerts:**
Compare time elapsed with expense utilization:
- 50% time passed, 50% spent = ✅ On track
- 50% time passed, 75% spent = ⚠️ Overspending
- 50% time passed, 30% spent = ℹ️ Underspending

#### Days Remaining
```
days_remaining = end_date - today (minimum 0)
```

**Example:**
```
End Date: Mar 31, 2025
Today: Feb 15, 2025
Days Remaining: 44 days
```

**Usage:**
Combined with expense_utilization to determine burn rate:
- 30 days left, 90% spent = Need to slow spending
- 5 days left, 50% spent = Can increase spending

---

## 🚨 Alert System

The alert system compares spending rate against time progress to detect budget issues early.

### Alert Logic

Budget health is determined by comparing spending rate vs time elapsed:

#### 🟢 GREEN (Healthy Budget)
```
Conditions:
  - expense_utilization < 70% AND
  - expense_utilization ≤ (days_elapsed_percentage + 10%)
```

**Example:**
```
Budget: 10,000,000 UGX
Spent: 5,000,000 UGX (50%)
Time: 50% elapsed

Check: 50% < 70% and 50% ≤ (50% + 10%)
Result: GREEN - Perfectly on track ✅
Action: Continue current spending pattern
```

#### 🟡 YELLOW (Warning - Monitor Closely)
```
Conditions:
  - expense_utilization ≥ 70% OR
  - expense_utilization > (days_elapsed_percentage + 10%) AND
  - expense_utilization < (days_elapsed_percentage + 20%)
```

**Example 1 - Over 70% Spent:**
```
Budget: 10,000,000 UGX
Spent: 7,500,000 UGX (75%)
Time: 70% elapsed

Check: 75% > 70%
Result: YELLOW - Over 70% spent ⚠️
Action: Monitor spending, prepare to slow down
```

**Example 2 - Moderate Overspend:**
```
Budget: 10,000,000 UGX
Spent: 5,500,000 UGX (55%)
Time: 40% elapsed

Check: 55% > (40% + 10%) = 55% > 50%
Result: YELLOW - Spending 15% faster than time ⚠️
Action: Review spending patterns
```

#### 🔴 RED (Critical - Immediate Action Required)
```
Conditions:
  - expense_utilization ≥ 90% OR
  - expense_utilization > (days_elapsed_percentage + 20%)
```

**Example 1 - Near Budget Limit:**
```
Budget: 10,000,000 UGX
Spent: 9,200,000 UGX (92%)
Time: 50% elapsed

Check: 92% ≥ 90%
Result: RED - Over 90% spent 🚨
Action: Stop non-essential spending immediately
```

**Example 2 - Critical Overspending:**
```
Budget: 10,000,000 UGX
Spent: 7,000,000 UGX (70%)
Time: 40% elapsed

Check: 70% > (40% + 20%) = 70% > 60%
Result: RED - Spending 30% faster than time 🚨
Action: Review and cut expenses urgently
```

### Alert Threshold Table

| Time Elapsed | Safe Spending (Green) | Warning (Yellow) | Critical (Red) |
|--------------|----------------------|------------------|----------------|
| 20% | ≤30% | 31-40% | >40% |
| 40% | ≤50% | 51-60% | >60% |
| 60% | ≤70% | 71-80% | >80% |
| 80% | ≤90% | 91-100% | >100% |
| 100% | ≤100% | - | >100% |

---

## 🔄 Budget Lifecycle

### 1. DRAFT
- Budget created but not yet approved
- Can be edited freely
- No tracking of actuals yet

### 2. APPROVED
- Budget reviewed and approved by authorized user
- Ready to be activated
- Still no tracking of actuals

### 3. ACTIVE
- Budget is currently in effect
- Automatically tracks actual income and expenses
- Real-time variance and utilization calculations
- Alert system monitoring spending patterns

### 4. CLOSED
- Budget period has ended
- Final variance recorded for historical analysis
- Cannot be edited (maintains audit trail)
- Used for year-over-year comparisons

---

## 📊 Data Flow

### Budget Creation
```
User Input:
  ├─ Sets income_budget (expected income)
  ├─ Sets expense_budget (expected expenses)
  └─ Sets period dates (start_date, end_date)
```

### Transaction Recording
```
Sales Entry:
  └─ Automatically adds to actual_income
      (when sale_date is within budget period)

Expense Entry:
  └─ Automatically adds to actual_expenses
      (when expense_date is within budget period)
```

### Real-time Updates
```
Every Page Load:
  ├─ Queries all sales for the period
  ├─ Queries all expenses for the period
  ├─ Calculates actual_income
  ├─ Calculates actual_expenses
  ├─ Computes variances
  ├─ Computes utilization percentages
  ├─ Evaluates alert level
  └─ Displays current status
```

---

## 💡 Best Practices

### ✅ DO:

1. **Set Realistic Budget Amounts**
   - Base budgets on historical data
   - Account for seasonal variations
   - Include buffer for unexpected costs

2. **Link All Transactions to Programs**
   - Every sale must have a program_id
   - Every expense must have a program_id
   - Ensures accurate actual calculations

3. **Monitor Alerts Regularly**
   - Check budget status weekly
   - Investigate yellow alerts promptly
   - Take immediate action on red alerts

4. **Close Budgets When Period Ends**
   - Preserves historical accuracy
   - Enables year-over-year comparisons
   - Maintains clean audit trail

5. **Review Variances**
   - Analyze why variances occurred
   - Adjust future budgets accordingly
   - Learn from overspending patterns

### ❌ DON'T:

1. **Never Manually Set Actual Values**
   - actual_income and actual_expenses are computed
   - Not stored in database
   - Calculated from real transactions only

2. **Avoid Overlapping Budget Periods**
   - Don't create two active budgets for same program
   - Creates confusion in reporting
   - Can't determine which budget to track against

3. **Don't Ignore Alerts**
   - Yellow/red alerts indicate real financial issues
   - Ignoring them leads to budget overruns
   - Address problems early

4. **Don't Edit Closed Budgets**
   - Creates audit trail problems
   - Invalidates historical comparisons
   - Use new budget with adjustments instead

5. **Don't Mix Currencies Without Conversion**
   - All transactions should be in same currency as budget
   - Use currency conversion if needed
   - System uses amount_base for calculations

---

## 📈 Practical Examples

### Example 1: Well-Managed Budget (Green)

```
Program: Code Camp 2025 - Term 1
Period: Jan 1 - Jun 30 (180 days)
Current Date: Apr 1 (90 days elapsed = 50%)

Budgeted Income: 50,000,000 UGX
Actual Income: 45,000,000 UGX
Income Achievement: 90% ✅

Budgeted Expenses: 40,000,000 UGX
Actual Expenses: 18,000,000 UGX
Expense Utilization: 45% 🟢

Analysis:
- Time: 50% elapsed
- Spending: 45%
- Status: GREEN (spending 5% slower than time)
- Action: On track, continue as planned
```

### Example 2: Warning Situation (Yellow)

```
Program: Girls in Tech Program
Period: Jan 1 - Dec 31 (365 days)
Current Date: Apr 15 (105 days elapsed = 29%)

Budgeted Income: 30,000,000 UGX
Actual Income: 20,000,000 UGX
Income Achievement: 67% ⚠️

Budgeted Expenses: 25,000,000 UGX
Actual Expenses: 12,000,000 UGX
Expense Utilization: 48% 🟡

Analysis:
- Time: 29% elapsed
- Spending: 48%
- Status: YELLOW (spending 19% faster than time)
- Action: Review spending, identify non-essential costs
```

### Example 3: Critical Situation (Red)

```
Program: Weekend Code Club
Period: Jan 1 - Mar 31 (90 days)
Current Date: Feb 20 (50 days elapsed = 56%)

Budgeted Income: 20,000,000 UGX
Actual Income: 15,000,000 UGX
Income Achievement: 75% ⚠️

Budgeted Expenses: 18,000,000 UGX
Actual Expenses: 16,200,000 UGX
Expense Utilization: 90% 🔴

Analysis:
- Time: 56% elapsed
- Spending: 90%
- Status: RED (near budget limit)
- Actions Required:
  1. Stop all non-essential spending
  2. Review and approve only critical expenses
  3. Consider budget reallocation request
  4. Increase income generation efforts
```

---

## 🔧 Technical Implementation

### Database Structure

```sql
program_budgets table:
  - id (primary key)
  - program_id (foreign key to programs)
  - period_type (quarterly, annual, etc.)
  - start_date
  - end_date
  - income_budget (manual entry)
  - expense_budget (manual entry)
  - currency
  - notes
  - status (draft, approved, active, closed)
  - approved_by (foreign key to users)
  - approved_at
  - created_at
  - updated_at
```

### Model Accessors

All calculations are implemented as Eloquent accessors:
- `actual_income` - Dynamic calculation from sales
- `actual_expenses` - Dynamic calculation from expenses
- `income_variance` - Computed from actual vs budget
- `expense_variance` - Computed from actual vs budget
- `income_utilization` - Percentage calculation
- `expense_utilization` - Percentage calculation
- `days_elapsed_percentage` - Time progress
- `days_remaining` - Days until end
- `alert_level` - Green/Yellow/Red determination

### Performance Considerations

- Calculations happen on every access (not cached)
- For high-traffic systems, consider caching
- Indexes on `program_id`, `start_date`, `end_date`
- Use eager loading for program relationships

---

## 📞 Support & Questions

For questions about the budget system:
1. Review this documentation first
2. Check the inline code documentation in `ProgramBudget.php`
3. Contact the development team
4. Submit issues via your project management system

---

## 📝 Version History

- **v1.0** (Nov 2025) - Initial implementation with automatic calculations
- **v1.1** (Nov 2025) - Added comprehensive documentation

---

**Last Updated:** November 4, 2025  
**Model Location:** `app/Models/ProgramBudget.php`  
**Related Views:** `resources/views/livewire/budgets/`
