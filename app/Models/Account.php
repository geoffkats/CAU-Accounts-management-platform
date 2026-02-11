<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\LogsActivity;

class Account extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'type',
        'category',
        'description',
        'parent_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    const TYPE_ASSET = 'asset';
    const TYPE_LIABILITY = 'liability';
    const TYPE_INCOME = 'income';
    const TYPE_EXPENSE = 'expense';
    const TYPE_EQUITY = 'equity';

    const CATEGORY_SHORT_TERM = 'short_term';
    const CATEGORY_LONG_TERM = 'long_term';

    public static function getTypes(): array
    {
        return [
            self::TYPE_ASSET => 'Asset',
            self::TYPE_LIABILITY => 'Liability',
            self::TYPE_EQUITY => 'Equity',
            self::TYPE_INCOME => 'Income',
            self::TYPE_EXPENSE => 'Expense',
        ];
    }

    public static function getCategories(): array
    {
        return [
            self::CATEGORY_SHORT_TERM => 'Short-term',
            self::CATEGORY_LONG_TERM => 'Long-term',
        ];
    }

    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function journalEntryLines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function vendorInvoices()
    {
        return $this->hasMany(VendorInvoice::class);
    }

    /**
     * ACCOUNTING BALANCE CALCULATIONS
     * 
     * Formula for account balance depends on account type:
     * - Assets: Debit - Credit (increases with debits)
     * - Expenses: Debit - Credit (increases with debits)
     * - Liabilities: Credit - Debit (increases with credits)
     * - Equity: Credit - Debit (increases with credits)
     * - Income: Credit - Debit (increases with credits)
     */

    /**
     * Calculate account balance using proper accounting rules
     * 
     * @param string|null $startDate Optional start date for period balance
     * @param string|null $endDate Optional end date for period balance
     * @return float The account balance
     */
    public function calculateBalance($startDate = null, $endDate = null): float
    {
        $query = $this->journalEntryLines()
            ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'posted');
                if ($startDate && $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate]);
                }
            });

        $totalDebits = (float) $query->sum('debit');
        $totalCredits = (float) $query->sum('credit');

        // Apply proper accounting equation based on account type
        return match($this->type) {
            'asset', 'expense' => $totalDebits - $totalCredits,  // Normal debit balance
            'liability', 'equity', 'income' => $totalCredits - $totalDebits,  // Normal credit balance
            default => $totalDebits - $totalCredits
        };
    }

    /**
     * Get all transactions for this account
     */
    public function getTransactions($startDate = null, $endDate = null)
    {
        return $this->journalEntryLines()
            ->with(['journalEntry', 'account'])
            ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'posted');
                if ($startDate && $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate]);
                }
            })
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->orderBy('journal_entries.date')
            ->orderBy('journal_entries.id')
            ->select('journal_entry_lines.*')
            ->get();
    }

    /**
     * Get account balance with running balance for each transaction
     */
    public function getTransactionsWithRunningBalance($startDate = null, $endDate = null)
    {
        $transactions = $this->getTransactions($startDate, $endDate);
        $runningBalance = 0;

        return $transactions->map(function ($transaction) use (&$runningBalance) {
            // Calculate the effect of this transaction on balance
            $effect = match($this->type) {
                'asset', 'expense' => $transaction->debit - $transaction->credit,
                'liability', 'equity', 'income' => $transaction->credit - $transaction->debit,
                default => $transaction->debit - $transaction->credit
            };

            $runningBalance += $effect;
            $transaction->running_balance = $runningBalance;
            
            return $transaction;
        });
    }

    /**
     * Check if account has normal debit balance
     */
    public function hasNormalDebitBalance(): bool
    {
        return in_array($this->type, ['asset', 'expense']);
    }

    /**
     * Check if account has normal credit balance
     */
    public function hasNormalCreditBalance(): bool
    {
        return in_array($this->type, ['liability', 'equity', 'income']);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
