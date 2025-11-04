<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\LogsActivity;

class StudentInvoice extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'invoice_number',
        'student_id',
        'program_id',
        'term',
        'academic_year',
        'invoice_date',
        'due_date',
        'total_amount',
        'discount_amount',
        'paid_amount',
        'balance',
        'currency',
        'exchange_rate',
        'amount_base',
        'status',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'amount_base' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = self::generateInvoiceNumber();
            }
            
            // Calculate balance
            $invoice->balance = $invoice->total_amount - $invoice->discount_amount - $invoice->paid_amount;
            
            // Calculate base currency amount
            if ($invoice->currency && $invoice->exchange_rate) {
                $invoice->amount_base = $invoice->balance * $invoice->exchange_rate;
            }
        });

        static::updating(function ($invoice) {
            // Recalculate balance
            $invoice->balance = $invoice->total_amount - $invoice->discount_amount - $invoice->paid_amount;
            
            // Update status based on balance
            if ($invoice->balance <= 0) {
                $invoice->status = 'paid';
            } elseif ($invoice->paid_amount > 0) {
                $invoice->status = 'partially_paid';
            } elseif ($invoice->due_date && $invoice->due_date->isPast() && $invoice->balance > 0) {
                $invoice->status = 'overdue';
            }

            // Update base currency amount
            if ($invoice->currency && $invoice->exchange_rate) {
                $invoice->amount_base = $invoice->balance * $invoice->exchange_rate;
            }
        });
    }

    public static function generateInvoiceNumber(): string
    {
        $year = now()->year;
        $month = now()->format('m');
        $lastInvoice = self::where('invoice_number', 'like', "INV-{$year}{$month}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "INV-{$year}{$month}-{$newNumber}";
    }

    // Relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StudentInvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(StudentPayment::class);
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function paymentPlan(): HasMany
    {
        return $this->hasMany(PaymentPlan::class);
    }

    // Accessors
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->balance > 0;
    }

    public function getDaysOverdueAttribute(): int
    {
        if (!$this->is_overdue) return 0;
        return now()->diffInDays($this->due_date);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'paid' => 'green',
            'partially_paid' => 'blue',
            'sent' => 'yellow',
            'overdue' => 'red',
            'cancelled' => 'gray',
            default => 'zinc',
        };
    }

    // Scopes
    public function scopeOutstanding($query)
    {
        return $query->whereIn('status', ['sent', 'partially_paid', 'overdue'])
            ->where('balance', '>', 0);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function scopeByTerm($query, $term, $year)
    {
        return $query->where('term', $term)->where('academic_year', $year);
    }
}
