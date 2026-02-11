<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Concerns\LogsActivity;
use App\Models\CompanySetting;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class Expense extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'program_id',
        'vendor_id',
        'staff_id',
        'account_id',
        'expense_date',
        'amount',
        'charges',
        'currency',
        'amount_base',
        'exchange_rate',
        'status',
        'description',
        'category',
        'receipt_path',
        'notes',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
        'charges' => 'decimal:2',
        'amount_base' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
    ];

    protected $appends = ['payment_status'];

    const CATEGORY_SALARIES = 'salaries';
    const CATEGORY_SUPPLIES = 'supplies';
    const CATEGORY_UTILITIES = 'utilities';
    const CATEGORY_RENT = 'rent';
    const CATEGORY_MARKETING = 'marketing';
    const CATEGORY_TRAVEL = 'travel';
    const CATEGORY_WELFARE = 'welfare';
    const CATEGORY_OTHER = 'other';

    public static function getCategories(): array
    {
        return [
            self::CATEGORY_SALARIES => 'Salaries & Wages',
            self::CATEGORY_SUPPLIES => 'Supplies',
            self::CATEGORY_UTILITIES => 'Utilities',
            self::CATEGORY_RENT => 'Rent',
            self::CATEGORY_MARKETING => 'Marketing',
            self::CATEGORY_TRAVEL => 'Travel',
            self::CATEGORY_WELFARE => 'Welfare',
            self::CATEGORY_OTHER => 'Other',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency', 'code');
    }

    public function journalEntry(): HasOne
    {
        return $this->hasOne(JournalEntry::class, 'expense_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get total amount paid via payment vouchers
     */
    public function getTotalPaidAttribute(): float
    {
        return $this->payments()->sum('amount');
    }

    /**
     * Get outstanding balance (including charges)
     */
    public function getOutstandingBalanceAttribute(): float
    {
        $amount = is_numeric($this->amount) ? (float) $this->amount : 0;
        $charges = is_numeric($this->charges) ? (float) $this->charges : 0;
        return max(($amount + $charges) - $this->total_paid, 0);
    }

    /**
     * Computed payment status based on payments (including charges)
     */
    public function getPaymentStatusAttribute(): string
    {
        $totalPaid = $this->total_paid;
        $amount = is_numeric($this->amount) ? (float) $this->amount : 0;
        $charges = is_numeric($this->charges) ? (float) $this->charges : 0;
        $totalAmount = $amount + $charges;

        if ($totalPaid >= $totalAmount) {
            return 'paid';
        } elseif ($totalPaid > 0) {
            return 'partial';
        }
        
        return 'unpaid';
    }

    /**
     * Check if expense is fully paid
     */
    public function isFullyPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if expense is unpaid
     */
    public function isUnpaid(): bool
    {
        return $this->payment_status === 'unpaid';
    }

    /**
     * Check if expense is partially paid
     */
    public function isPartiallyPaid(): bool
    {
        return $this->payment_status === 'partial';
    }

    /**
     * Accessor: Public URL for stored receipt (on public disk by default)
     */
    public function getReceiptUrlAttribute(): ?string
    {
        if (!$this->receipt_path) return null;

        // Prefer public disk where we store uploads
        if (Storage::disk('public')->exists($this->receipt_path)) {
            return Storage::disk('public')->url($this->receipt_path);
        }

        // Fallback: default disk
        if (Storage::exists($this->receipt_path)) {
            return Storage::url($this->receipt_path);
        }

        return null;
    }

    public function getFormattedAmountAttribute(): string
    {
        $currency = Currency::where('code', $this->currency ?? 'UGX')->first();
        return $currency ? $currency->formatAmount($this->amount) : number_format($this->amount, 2);
    }



    /**
     * Create journal entry for this expense
     * 
     * DOUBLE-ENTRY BOOKKEEPING:
     * Dr. Expense Account (5xxx)         XXX
     *     Cr. Accounts Payable (2000)    XXX
     * 
     * Note: Payment is handled separately via Payment Vouchers
     */
    public function createJournalEntry(): JournalEntry
    {
        // Always credit Accounts Payable (payment handled separately)
        $accountsPayable = Account::where('code', '2000')->first();
        
        if (!$accountsPayable) {
            throw new \Exception('Accounts Payable account (2000) not found. Please create it first.');
        }

        // Build description
        $payeeName = $this->vendor?->name ?? $this->staff?->first_name . ' ' . $this->staff?->last_name ?? 'Unknown';
        $description = "Expense: {$this->description} - Payable to: {$payeeName}";

        // Create journal entry with two lines
        $journalEntry = JournalEntry::createEntry(
            [
                'date' => $this->expense_date,
                'type' => 'expense',
                'description' => $description,
                'expense_id' => $this->id,
                'created_by' => auth()->id(),
                'status' => 'posted',
                'posted_at' => now(),
            ],
            [
                // Line 1: Debit Expense Account
                [
                    'account_id' => $this->account_id,
                    'debit' => (float) ($this->amount_base ?? $this->amount) + (is_numeric($this->charges) ? (float) $this->charges : 0),
                    'credit' => 0,
                    'description' => $this->description,
                ],
                // Line 2: Credit Accounts Payable
                [
                    'account_id' => $accountsPayable->id,
                    'debit' => 0,
                    'credit' => (float) ($this->amount_base ?? $this->amount) + (is_numeric($this->charges) ? (float) $this->charges : 0),
                    'description' => "Accounts Payable - {$payeeName}",
                ],
            ]
        );

        return $journalEntry;
    }



    protected static function booted(): void
    {
        // Auto-calculate base currency amount (including charges)
        static::saving(function (self $expense) {
            if ($expense->isDirty(['amount', 'charges', 'currency'])) {
                $baseCurrency = Currency::getBaseCurrency();
                // Ensure numeric values - handle empty strings and null
                $amount = is_numeric($expense->amount) ? (float) $expense->amount : 0;
                $charges = is_numeric($expense->charges) ? (float) $expense->charges : 0;
                $totalAmount = $amount + $charges;
                
                if ($baseCurrency && $expense->currency && $expense->currency !== $baseCurrency->code) {
                    $rate = ExchangeRate::getRate($expense->currency, $baseCurrency->code);
                    if ($rate) {
                        $expense->exchange_rate = $rate;
                        $expense->amount_base = $totalAmount * $rate;
                    }
                } else {
                    $expense->amount_base = $totalAmount;
                    $expense->exchange_rate = 1.0;
                }
            }
        });

        // Auto-create journal entry when expense is created
        static::created(function (self $expense) {
            try {
                $expense->createJournalEntry();
            } catch (\Exception $e) {
                // Log error but don't fail expense creation
                \Log::error('Failed to create journal entry for expense: ' . $e->getMessage());
            }
        });

        // Keep expense journal entry in sync when expense is updated
        static::updated(function (self $expense) {
            try {
                $entry = JournalEntry::where('expense_id', $expense->id)->latest('id')->first();
                $oldId = $entry?->id;
                if ($entry) {
                    $entry->void();
                }
                $newEntry = $expense->createJournalEntry();
                if ($oldId) {
                    $newEntry->update(['replaces_entry_id' => $oldId]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to recreate expense journal entry on update: ' . $e->getMessage());
            }
        });

        $check = function (self $model) {
            $settings = CompanySetting::get();
            if ($settings->lock_before_date) {
                $date = Carbon::parse($model->expense_date);
                if ($date->lt($settings->lock_before_date)) {
                    throw ValidationException::withMessages([
                        'expense_date' => 'Transactions before ' . $settings->lock_before_date->format('Y-m-d') . ' are locked by compliance policy.',
                    ]);
                }
            }
        };

        static::creating($check);
        static::updating($check);
    }
}
