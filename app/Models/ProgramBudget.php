<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\LogsActivity;

/**
 * Program Budget Model
 * 
 * This model manages program budgets with automatic calculation of actuals, variances,
 * and utilization metrics based on real transaction data.
 * 
 * ===================================================================================
 * BUDGET SYSTEM OVERVIEW
 * ===================================================================================
 * 
 * 1. MANUAL ENTRY (Budget Planning):
 *    - income_budget: Expected income for the period
 *    - expense_budget: Expected expenses for the period
 *    - period dates (start_date, end_date)
 * 
 * 2. AUTOMATIC CALCULATION (Real-time):
 *    - Actual income: Sum of program sales in period
 *    - Actual expenses: Sum of program expenses in period
 *    - All metrics, variances, and alerts
 * 
 * ===================================================================================
 * CALCULATION FORMULAS
 * ===================================================================================
 * 
 * ACTUAL VALUES (Computed from Transactions):
 * ──────────────────────────────────────────
 * actual_income = Σ(Sales.amount_base) WHERE sale_date ∈ [start_date, end_date]
 * actual_expenses = Σ(Expenses.amount_base) WHERE expense_date ∈ [start_date, end_date]
 * 
 * VARIANCE ANALYSIS:
 * ──────────────────────────────────────────
 * income_variance = actual_income - income_budget
 *   • Positive = Earned more than expected ✓
 *   • Negative = Earned less than expected ✗
 * 
 * expense_variance = actual_expenses - expense_budget
 *   • Positive = Spent more than budgeted ✗ (Over budget)
 *   • Negative = Spent less than budgeted ✓ (Under budget)
 * 
 * UTILIZATION METRICS:
 * ──────────────────────────────────────────
 * income_utilization = (actual_income / income_budget) × 100
 *   • 100% = Target achieved
 *   • >100% = Exceeded target
 *   • <100% = Below target
 * 
 * expense_utilization = (actual_expenses / expense_budget) × 100
 *   • <100% = Under budget (good)
 *   • 100% = At budget limit
 *   • >100% = Over budget (bad)
 * 
 * TIME PROGRESS:
 * ──────────────────────────────────────────
 * days_elapsed_percentage = (days_from_start_to_today / total_period_days) × 100
 * days_remaining = end_date - today
 * 
 * ALERT LOGIC:
 * ──────────────────────────────────────────
 * Budget health is determined by comparing spending rate vs time elapsed:
 * 
 * • GREEN (Healthy):
 *   - expense_utilization < 70% AND
 *   - expense_utilization ≤ (days_elapsed_percentage + 10%)
 * 
 * • YELLOW (Warning):
 *   - expense_utilization ≥ 70% OR
 *   - expense_utilization > (days_elapsed_percentage + 10%) AND
 *   - expense_utilization < (days_elapsed_percentage + 20%)
 * 
 * • RED (Critical):
 *   - expense_utilization ≥ 90% OR
 *   - expense_utilization > (days_elapsed_percentage + 20%)
 * 
 * Example Alert Scenarios:
 * ────────────────────────
 * Scenario 1: 30% of time passed, 50% spent = YELLOW (spending too fast)
 * Scenario 2: 50% of time passed, 45% spent = GREEN (on track)
 * Scenario 3: 40% of time passed, 70% spent = RED (critical overspending)
 * Scenario 4: 95% of time passed, 92% spent = RED (near budget limit)
 * 
 * ===================================================================================
 * BUDGET LIFECYCLE
 * ===================================================================================
 * 
 * 1. DRAFT → Budget created, not yet approved
 * 2. APPROVED → Reviewed and approved by authorized user
 * 3. ACTIVE → Currently in effect, tracking actuals
 * 4. CLOSED → Budget period ended, final variance recorded
 * 
 * ===================================================================================
 * DATA FLOW
 * ===================================================================================
 * 
 * Budget Creation:
 *   User → Sets income_budget, expense_budget, dates
 * 
 * Transaction Recording:
 *   Sales Entry → Automatically adds to actual_income
 *   Expense Entry → Automatically adds to actual_expenses
 * 
 * Real-time Updates:
 *   Every page load → Recalculates all metrics
 *   Alert System → Evaluates spending vs time automatically
 * 
 * ===================================================================================
 * BEST PRACTICES
 * ===================================================================================
 * 
 * ✓ DO:
 *   - Set realistic budget amounts based on historical data
 *   - Link all sales/expenses to the correct program
 *   - Monitor alerts regularly for early warning signs
 *   - Close budgets when period ends for historical accuracy
 * 
 * ✗ DON'T:
 *   - Manually set actual_income or actual_expenses (not stored in DB)
 *   - Create overlapping budget periods for same program
 *   - Ignore yellow/red alerts - they indicate financial issues
 *   - Edit closed budgets - creates audit trail problems
 * 
 * ===================================================================================
 */
class ProgramBudget extends Model
{
    use LogsActivity;
    
    protected $fillable = [
        'program_id',
        'period_type',
        'start_date',
        'end_date',
        'income_budget',
        'expense_budget',
        'currency',
        'notes',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'income_budget' => 'decimal:2',
        'expense_budget' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reallocationsFrom(): HasMany
    {
        return $this->hasMany(BudgetReallocation::class, 'from_budget_id');
    }

    public function reallocationsTo(): HasMany
    {
        return $this->hasMany(BudgetReallocation::class, 'to_budget_id');
    }

    /**
     * Get actual income for this budget period
     * 
     * CALCULATION:
     * ────────────
     * Automatically sums all sales transactions linked to this program
     * that fall within the budget period.
     * 
     * Formula: actual_income = Σ(Sales.amount_base) 
     *          WHERE sale_date ∈ [start_date, end_date]
     *          AND program_id = this.program_id
     * 
     * @return float Total actual income in base currency
     * 
     * Example:
     * ────────
     * Budget Period: Jan 1 - Mar 31
     * Sales in period:
     *   - Jan 15: UGX 5,000,000 (Student fees)
     *   - Feb 10: UGX 3,000,000 (Student fees)
     *   - Mar 20: UGX 2,000,000 (Student fees)
     * Result: 10,000,000 UGX
     */
    public function getActualIncomeAttribute(): float
    {
        return $this->program->sales()
            ->whereBetween('sale_date', [$this->start_date, $this->end_date])
            ->sum('amount_base') ?: $this->program->sales()
            ->whereBetween('sale_date', [$this->start_date, $this->end_date])
            ->sum('amount');
    }

    /**
     * Get actual expenses for this budget period
     * 
     * CALCULATION:
     * ────────────
     * Automatically sums all expense transactions linked to this program
     * that fall within the budget period.
     * 
     * Formula: actual_expenses = Σ(Expenses.amount_base)
     *          WHERE expense_date ∈ [start_date, end_date]
     *          AND program_id = this.program_id
     * 
     * @return float Total actual expenses in base currency
     * 
     * Example:
     * ────────
     * Budget Period: Jan 1 - Mar 31
     * Expenses in period:
     *   - Jan 5: UGX 1,500,000 (Instructor salaries)
     *   - Feb 15: UGX 800,000 (Equipment purchase)
     *   - Mar 10: UGX 500,000 (Materials)
     * Result: 2,800,000 UGX
     */
    public function getActualExpensesAttribute(): float
    {
        return $this->program->expenses()
            ->whereBetween('expense_date', [$this->start_date, $this->end_date])
            ->sum('amount_base') ?: $this->program->expenses()
            ->whereBetween('expense_date', [$this->start_date, $this->end_date])
            ->sum('amount');
    }

    /**
     * Get income variance (difference between actual and budgeted income)
     * 
     * CALCULATION:
     * ────────────
     * income_variance = actual_income - income_budget
     * 
     * INTERPRETATION:
     * ────────────────
     * • Positive (+) = Good! Earned MORE than expected
     * • Zero (0) = Perfect! Met exactly the target
     * • Negative (-) = Warning! Earned LESS than expected
     * 
     * @return float Variance amount (positive = favorable, negative = unfavorable)
     * 
     * Example:
     * ────────
     * Budgeted Income: 10,000,000 UGX
     * Actual Income: 12,000,000 UGX
     * Variance: +2,000,000 UGX (Favorable - exceeded target by 20%)
     * 
     * Example 2:
     * ──────────
     * Budgeted Income: 10,000,000 UGX
     * Actual Income: 8,500,000 UGX
     * Variance: -1,500,000 UGX (Unfavorable - shortfall of 15%)
     */
    public function getIncomeVarianceAttribute(): float
    {
        return $this->actual_income - $this->income_budget;
    }

    /**
     * Get expense variance (difference between actual and budgeted expenses)
     * 
     * CALCULATION:
     * ────────────
     * expense_variance = actual_expenses - expense_budget
     * 
     * INTERPRETATION:
     * ────────────────
     * • Positive (+) = Bad! Spent MORE than budgeted (over budget)
     * • Zero (0) = Perfect! Spent exactly as budgeted
     * • Negative (-) = Good! Spent LESS than budgeted (under budget)
     * 
     * @return float Variance amount (negative = favorable, positive = unfavorable)
     * 
     * Example:
     * ────────
     * Budgeted Expenses: 8,000,000 UGX
     * Actual Expenses: 9,500,000 UGX
     * Variance: +1,500,000 UGX (Unfavorable - overspent by 18.75%)
     * 
     * Example 2:
     * ──────────
     * Budgeted Expenses: 8,000,000 UGX
     * Actual Expenses: 7,200,000 UGX
     * Variance: -800,000 UGX (Favorable - saved 10%)
     */
    public function getExpenseVarianceAttribute(): float
    {
        return $this->actual_expenses - $this->expense_budget;
    }

    /**
     * Get income utilization percentage (achievement rate)
     * 
     * CALCULATION:
     * ────────────
     * income_utilization = (actual_income / income_budget) × 100
     * 
     * INTERPRETATION:
     * ────────────────
     * • >100% = Exceeded target (excellent)
     * • 100% = Met target exactly (perfect)
     * • 90-99% = Close to target (good)
     * • 80-89% = Below target (concerning)
     * • <80% = Significant shortfall (critical)
     * 
     * @return float Percentage of budgeted income achieved
     * 
     * Example:
     * ────────
     * Budgeted Income: 10,000,000 UGX
     * Actual Income: 8,500,000 UGX
     * Utilization: (8,500,000 / 10,000,000) × 100 = 85%
     * Interpretation: Achieved 85% of income target
     */
    public function getIncomeUtilizationAttribute(): float
    {
        if ($this->income_budget == 0) return 0;
        return ($this->actual_income / $this->income_budget) * 100;
    }

    /**
     * Get expense utilization percentage (spending rate)
     * 
     * CALCULATION:
     * ────────────
     * expense_utilization = (actual_expenses / expense_budget) × 100
     * 
     * INTERPRETATION:
     * ────────────────
     * • <70% = Well under budget (safe)
     * • 70-89% = Approaching budget (monitor)
     * • 90-99% = Near budget limit (caution)
     * • 100% = At budget limit (critical)
     * • >100% = Over budget (emergency)
     * 
     * COMBINED WITH TIME:
     * ───────────────────
     * If 50% of time passed but 80% spent = Spending too fast!
     * If 80% of time passed but 50% spent = Under-utilizing budget
     * 
     * @return float Percentage of budget spent
     * 
     * Example:
     * ────────
     * Budgeted Expenses: 8,000,000 UGX
     * Actual Expenses: 7,200,000 UGX
     * Utilization: (7,200,000 / 8,000,000) × 100 = 90%
     * Interpretation: Spent 90% of budget
     * 
     * With Time Context:
     * ──────────────────
     * If 60% of budget period passed:
     *   90% spent vs 60% time = RED ALERT (spending too fast)
     * If 95% of budget period passed:
     *   90% spent vs 95% time = GREEN (on track)
     */
    public function getExpenseUtilizationAttribute(): float
    {
        if ($this->expense_budget == 0) return 0;
        return ($this->actual_expenses / $this->expense_budget) * 100;
    }

    /**
     * Get days elapsed percentage (time progress)
     * 
     * CALCULATION:
     * ────────────
     * days_elapsed_percentage = (days_from_start_to_today / total_period_days) × 100
     * 
     * Where:
     *   days_from_start_to_today = today - start_date
     *   total_period_days = end_date - start_date
     * 
     * @return float Percentage of budget period that has elapsed
     * 
     * Example:
     * ────────
     * Start Date: Jan 1, 2025
     * End Date: Mar 31, 2025 (90 days)
     * Today: Feb 15, 2025 (45 days elapsed)
     * 
     * Calculation: (45 / 90) × 100 = 50%
     * Interpretation: Budget period is 50% complete
     * 
     * Usage in Alerts:
     * ────────────────
     * Compare this with expense_utilization:
     *   - If 50% time passed, 50% spent = On track
     *   - If 50% time passed, 75% spent = Overspending
     *   - If 50% time passed, 30% spent = Underspending
     */
    public function getDaysElapsedPercentageAttribute(): float
    {
        $total = $this->start_date->diffInDays($this->end_date);
        $elapsed = $this->start_date->diffInDays(now());
        
        if ($total == 0) return 100;
        return min(($elapsed / $total) * 100, 100);
    }

    /**
     * Get days remaining in budget period
     * 
     * CALCULATION:
     * ────────────
     * days_remaining = end_date - today
     * 
     * Note: Returns 0 if budget period has ended
     * 
     * @return int Number of days remaining
     * 
     * Example:
     * ────────
     * End Date: Mar 31, 2025
     * Today: Feb 15, 2025
     * Days Remaining: 44 days
     * 
     * Usage:
     * ──────
     * Combined with expense_utilization helps determine burn rate:
     *   - 30 days left, 90% spent = Need to slow spending
     *   - 5 days left, 50% spent = Can increase spending
     */
    public function getDaysRemainingAttribute(): int
    {
        return max(now()->diffInDays($this->end_date, false), 0);
    }

    /**
     * Check if budget needs alert (spending rate exceeds time rate)
     * 
     * LOGIC:
     * ──────
     * Budget needs alert when spending significantly faster than time passing
     * 
     * Formula: expense_utilization > (days_elapsed_percentage + 10%)
     * 
     * Threshold: 10% buffer allows for normal spending variations
     * 
     * @return bool True if alert needed, false otherwise
     * 
     * Example Scenarios:
     * ──────────────────
     * 
     * Scenario 1 - ALERT TRIGGERED:
     *   Time Elapsed: 40%
     *   Expenses: 65%
     *   Check: 65% > (40% + 10%) = 65% > 50% = TRUE
     *   Result: ⚠️ Alert - spending 25% faster than time
     * 
     * Scenario 2 - NO ALERT:
     *   Time Elapsed: 60%
     *   Expenses: 68%
     *   Check: 68% > (60% + 10%) = 68% > 70% = FALSE
     *   Result: ✓ On track - within acceptable range
     * 
     * Scenario 3 - ALERT TRIGGERED:
     *   Time Elapsed: 30%
     *   Expenses: 55%
     *   Check: 55% > (30% + 10%) = 55% > 40% = TRUE
     *   Result: ⚠️ Alert - spending 25% faster than time
     */
    public function needsAlert(): bool
    {
        return $this->expense_utilization > ($this->days_elapsed_percentage + 10);
    }

    /**
     * Get alert level: green, yellow, red
     * 
     * ALGORITHM:
     * ──────────
     * Determines budget health based on spending patterns
     * 
     * RED (Critical) - Immediate action required:
     *   • expense_utilization ≥ 90% OR
     *   • expense_utilization > (days_elapsed_percentage + 20%)
     * 
     * YELLOW (Warning) - Monitor closely:
     *   • expense_utilization ≥ 70% OR
     *   • expense_utilization > (days_elapsed_percentage + 10%)
     * 
     * GREEN (Healthy) - On track:
     *   • All other cases
     * 
     * @return string 'red', 'yellow', or 'green'
     * 
     * DETAILED EXAMPLES:
     * ──────────────────
     * 
     * Example 1 - RED (Near Budget Limit):
     *   Budget: 10,000,000 UGX
     *   Spent: 9,200,000 UGX (92%)
     *   Time: 50% elapsed
     *   Result: RED - Over 90% spent
     *   Action: Stop non-essential spending immediately
     * 
     * Example 2 - RED (Overspending):
     *   Budget: 10,000,000 UGX
     *   Spent: 7,000,000 UGX (70%)
     *   Time: 40% elapsed
     *   Check: 70% > (40% + 20%) = 70% > 60% = TRUE
     *   Result: RED - Spending 30% faster than time
     *   Action: Review and cut expenses urgently
     * 
     * Example 3 - YELLOW (Warning):
     *   Budget: 10,000,000 UGX
     *   Spent: 7,500,000 UGX (75%)
     *   Time: 70% elapsed
     *   Check: 75% < 90% but 75% > (70% + 10%) = FALSE
     *   Result: YELLOW - Over 70% spent
     *   Action: Monitor spending, prepare to slow down
     * 
     * Example 4 - YELLOW (Moderate Overspend):
     *   Budget: 10,000,000 UGX
     *   Spent: 5,500,000 UGX (55%)
     *   Time: 40% elapsed
     *   Check: 55% > (40% + 10%) = 55% > 50% = TRUE
     *   Result: YELLOW - Spending 15% faster than time
     *   Action: Review spending patterns
     * 
     * Example 5 - GREEN (On Track):
     *   Budget: 10,000,000 UGX
     *   Spent: 5,000,000 UGX (50%)
     *   Time: 50% elapsed
     *   Check: 50% < 70% and 50% ≤ (50% + 10%)
     *   Result: GREEN - Perfectly on track
     *   Action: Continue current spending pattern
     * 
     * Example 6 - GREEN (Under Budget):
     *   Budget: 10,000,000 UGX
     *   Spent: 4,000,000 UGX (40%)
     *   Time: 60% elapsed
     *   Check: 40% < 70% and 40% < (60% + 10%)
     *   Result: GREEN - Under-utilizing budget
     *   Action: May increase spending if needed
     */
    public function getAlertLevelAttribute(): string
    {
        $utilization = $this->expense_utilization;
        $elapsed = $this->days_elapsed_percentage;

        // Red: Over 90% spent or spending faster than time by 20%+
        if ($utilization >= 90 || ($utilization > $elapsed + 20)) {
            return 'red';
        }

        // Yellow: Over 70% spent or spending faster than time by 10%+
        if ($utilization >= 70 || ($utilization > $elapsed + 10)) {
            return 'yellow';
        }

        // Green: Under control
        return 'green';
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'approved' => 'blue',
            'active' => 'green',
            'closed' => 'zinc',
            default => 'gray',
        };
    }
}
