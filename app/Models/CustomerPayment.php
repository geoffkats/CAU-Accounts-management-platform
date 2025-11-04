<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\LogsActivity;

class CustomerPayment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'sale_id',
        'customer_id',
        'payment_date',
        'amount',
        'currency',
        'exchange_rate',
        'amount_base',
        'payment_method',
        'reference_number',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'amount_base' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function ($payment) {
            // Auto-calculate base currency amount
            if ($payment->currency && $payment->exchange_rate) {
                $payment->amount_base = $payment->amount * $payment->exchange_rate;
            } else {
                $payment->amount_base = $payment->amount;
                $payment->exchange_rate = 1.0;
            }
        });

        static::created(function ($payment) {
            // Update sale paid amount and status
            $payment->sale->updatePaymentStatus();
        });

        static::deleted(function ($payment) {
            // Update sale paid amount and status
            $payment->sale->updatePaymentStatus();
        });
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency', 'code');
    }

    public function getFormattedAmountAttribute(): string
    {
        $currency = Currency::where('code', $this->currency ?? 'UGX')->first();
        return $currency ? $currency->formatAmount($this->amount) : number_format($this->amount, 2);
    }
}
