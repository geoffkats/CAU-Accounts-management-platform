<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\LogsActivity;
use App\Models\CompanySetting;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;

class Sale extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'program_id',
        'customer_id',
        'account_id',
        'invoice_number',
        'sale_date',
        'amount',
        'currency',
        'amount_base',
        'exchange_rate',
        'amount_paid',
        'description',
        'status',
        'payment_method',
        'reference_number',
        'notes',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'amount' => 'decimal:2',
        'amount_base' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'amount_paid' => 'decimal:2',
    ];

    const STATUS_UNPAID = 'unpaid';
    const STATUS_PAID = 'paid';
    const STATUS_PARTIALLY_PAID = 'partially_paid';
    const STATUS_CANCELLED = 'cancelled';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_UNPAID => 'Unpaid',
            self::STATUS_PAID => 'Paid',
            self::STATUS_PARTIALLY_PAID => 'Partially Paid',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency', 'code');
    }

    public function payments()
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function updatePaymentStatus(): void
    {
        $totalPaid = $this->payments()->sum('amount');
        $this->amount_paid = $totalPaid;

        if ($totalPaid >= $this->amount) {
            $this->status = self::STATUS_PAID;
        } elseif ($totalPaid > 0) {
            $this->status = self::STATUS_PARTIALLY_PAID;
        } else {
            $this->status = self::STATUS_UNPAID;
        }

        $this->saveQuietly(); // Save without triggering events
    }

    public function getRemainingBalanceAttribute(): float
    {
        return max(0, $this->amount - $this->amount_paid);
    }

    public function getFormattedAmountAttribute(): string
    {
        $currency = Currency::where('code', $this->currency ?? 'UGX')->first();
        return $currency ? $currency->formatAmount($this->amount) : number_format($this->amount, 2);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('status', self::STATUS_UNPAID);
    }

    public function markAsPaid(): void
    {
        $this->amount_paid = $this->amount;
        $this->status = self::STATUS_PAID;
        $this->save();
    }

    protected static function booted(): void
    {
        // Auto-calculate base currency amount
        static::saving(function (self $sale) {
            if ($sale->isDirty(['amount', 'currency'])) {
                $baseCurrency = Currency::getBaseCurrency();
                if ($baseCurrency && $sale->currency && $sale->currency !== $baseCurrency->code) {
                    $rate = ExchangeRate::getRate($sale->currency, $baseCurrency->code);
                    if ($rate) {
                        $sale->exchange_rate = $rate;
                        $sale->amount_base = $sale->amount * $rate;
                    }
                } else {
                    $sale->amount_base = $sale->amount;
                    $sale->exchange_rate = 1.0;
                }
            }
        });

        $check = function (self $model) {
            $settings = CompanySetting::get();
            if ($settings->lock_before_date) {
                $date = Carbon::parse($model->sale_date);
                if ($date->lt($settings->lock_before_date)) {
                    throw ValidationException::withMessages([
                        'sale_date' => 'Transactions before ' . $settings->lock_before_date->format('Y-m-d') . ' are locked by compliance policy.',
                    ]);
                }
            }
        };

        static::creating($check);
        static::updating($check);
    }
}
