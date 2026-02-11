<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\LogsActivity;

class StudentPayment extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'payment_number',
        'student_id',
        'student_invoice_id',
        'payment_date',
        'amount',
        'currency',
        'exchange_rate',
        'amount_base',
        'payment_method',
        'reference_number',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'amount_base' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function ($payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = self::generatePaymentNumber();
            }
            
            // Calculate base currency amount
            if ($payment->currency && $payment->exchange_rate) {
                $payment->amount_base = $payment->amount * $payment->exchange_rate;
            }

            // Auto-allocate to invoice if provided
            if ($payment->student_invoice_id) {
                $invoice = StudentInvoice::find($payment->student_invoice_id);
                if ($invoice) {
                    $invoice->paid_amount += $payment->amount;
                    $invoice->save();
                }
            }
        });
    }

    public static function generatePaymentNumber(): string
    {
        $year = now()->year;
        $month = now()->format('m');
        $lastPayment = self::where('payment_number', 'like', "PAY-{$year}{$month}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastPayment) {
            $lastNumber = (int) substr($lastPayment->payment_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "PAY-{$year}{$month}-{$newNumber}";
    }

    // Relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(StudentInvoice::class, 'student_invoice_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    // Accessors
    public function getPaymentMethodLabelAttribute(): string
    {
        return match($this->payment_method) {
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'mobile_money' => 'Mobile Money',
            'cheque' => 'Cheque',
            'card' => 'Card',
            default => 'Unknown',
        };
    }
}
