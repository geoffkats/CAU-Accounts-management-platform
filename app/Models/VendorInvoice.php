<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\LogsActivity;

class VendorInvoice extends Model
{
    use LogsActivity;

    protected $fillable = [
        'invoice_number',
        'vendor_id',
        'program_id',
        'account_id',
        'invoice_date',
        'due_date',
        'amount',
        'currency',
        'exchange_rate',
        'amount_base',
        'amount_paid',
        'status',
        'payment_terms',
        'category',
        'description',
        'vendor_reference',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'amount_base' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency', 'code');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(VendorPayment::class);
    }

    public function getRemainingBalanceAttribute(): float
    {
        return max(0, $this->amount - $this->amount_paid);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && 
               $this->due_date->isPast() && 
               $this->status !== 'paid' && 
               $this->status !== 'cancelled';
    }

    public function getDaysOverdueAttribute(): int
    {
        if (!$this->is_overdue) {
            return 0;
        }
        return now()->diffInDays($this->due_date);
    }

    /**
     * Update payment status based on payments
     */
    public function updatePaymentStatus(): void
    {
        $totalPaid = $this->payments()->sum('amount');
        
        $this->amount_paid = $totalPaid;
        
        if ($totalPaid >= $this->amount) {
            $this->status = 'paid';
        } elseif ($totalPaid > 0) {
            $this->status = 'partially_paid';
        } else {
            $this->status = 'unpaid';
        }
        
        $this->saveQuietly();
    }

    /**
     * Calculate due date based on payment terms
     */
    public function calculateDueDate(): void
    {
        if (!$this->invoice_date) {
            return;
        }

        $days = match($this->payment_terms) {
            'immediate' => 0,
            'net_7' => 7,
            'net_15' => 15,
            'net_30' => 30,
            'net_60' => 60,
            'net_90' => 90,
            default => 30,
        };

        $this->due_date = $this->invoice_date->copy()->addDays($days);
    }

    protected static function booted(): void
    {
        // Auto-generate invoice number
        static::creating(function (VendorInvoice $invoice) {
            if (!$invoice->invoice_number) {
                $lastInvoice = static::latest('id')->first();
                $nextNumber = $lastInvoice ? ((int) substr($lastInvoice->invoice_number, 3)) + 1 : 1;
                $invoice->invoice_number = 'VIN' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
            }

            // Calculate due date if not set
            if (!$invoice->due_date) {
                $invoice->calculateDueDate();
            }

            // Calculate base currency amount
            if ($invoice->currency && $invoice->currency !== 'UGX') {
                $rate = ExchangeRate::getRate($invoice->currency, 'UGX') ?? 1.0;
                $invoice->exchange_rate = $rate;
                $invoice->amount_base = $invoice->amount * $rate;
            } else {
                $invoice->amount_base = $invoice->amount;
                $invoice->exchange_rate = 1.0;
            }
        });
    }
}
