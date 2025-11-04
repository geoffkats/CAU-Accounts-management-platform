<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'account_id',
        'expense_date',
        'amount',
        'currency',
        'amount_base',
        'exchange_rate',
        'status',
        'payment_status',
        'payment_date',
        'payment_reference',
        'description',
        'category',
        'payment_method',
        'reference_number',
        'receipt_path',
        'notes',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'amount_base' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
    ];

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

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency', 'code');
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
     * Mark expense as paid
     */
    public function markAsPaid(string $paymentReference = null): void
    {
        $this->payment_status = 'paid';
        $this->payment_date = now();
        if ($paymentReference) {
            $this->payment_reference = $paymentReference;
        }
        $this->save();
    }

    /**
     * Check if expense is paid
     */
    public function isPaid(): bool
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

    protected static function booted(): void
    {
        // Auto-calculate base currency amount
        static::saving(function (self $expense) {
            if ($expense->isDirty(['amount', 'currency'])) {
                $baseCurrency = Currency::getBaseCurrency();
                if ($baseCurrency && $expense->currency && $expense->currency !== $baseCurrency->code) {
                    $rate = ExchangeRate::getRate($expense->currency, $baseCurrency->code);
                    if ($rate) {
                        $expense->exchange_rate = $rate;
                        $expense->amount_base = $expense->amount * $rate;
                    }
                } else {
                    $expense->amount_base = $expense->amount;
                    $expense->exchange_rate = 1.0;
                }
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
