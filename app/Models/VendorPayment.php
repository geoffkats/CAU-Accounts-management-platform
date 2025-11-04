<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\LogsActivity;

class VendorPayment extends Model
{
    use LogsActivity;

    protected $fillable = [
        'vendor_invoice_id',
        'vendor_id',
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
        'amount_base' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
    ];

    public function vendorInvoice(): BelongsTo
    {
        return $this->belongsTo(VendorInvoice::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency', 'code');
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0);
    }

    protected static function booted(): void
    {
        static::creating(function (VendorPayment $payment) {
            // Auto-calculate base currency amount
            if ($payment->currency && $payment->currency !== 'UGX') {
                $rate = ExchangeRate::getRate($payment->currency, 'UGX') ?? 1.0;
                $payment->exchange_rate = $rate;
                $payment->amount_base = $payment->amount * $rate;
            } else {
                $payment->amount_base = $payment->amount;
                $payment->exchange_rate = 1.0;
            }
        });

        static::created(function (VendorPayment $payment) {
            $payment->vendorInvoice->updatePaymentStatus();
        });

        static::deleted(function (VendorPayment $payment) {
            $payment->vendorInvoice->updatePaymentStatus();
        });
    }
}
