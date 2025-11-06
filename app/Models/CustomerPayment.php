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
            
            // Create journal entry
            try {
                $payment->createJournalEntry();
            } catch (\Exception $e) {
                \Log::error('Failed to create journal entry for customer payment: ' . $e->getMessage());
            }
        });

        static::deleted(function ($payment) {
            // Update sale paid amount and status
            $payment->sale->updatePaymentStatus();
        });

        // Keep payment journal entry in sync when payment is updated
        static::updated(function (self $payment) {
            try {
                $entry = JournalEntry::where('customer_payment_id', $payment->id)->latest('id')->first();
                $oldId = $entry?->id;
                if ($entry) {
                    $entry->void();
                }
                $newEntry = $payment->createJournalEntry();
                if ($oldId) {
                    $newEntry->update(['replaces_entry_id' => $oldId]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to recreate customer payment journal entry on update: ' . $e->getMessage());
            }
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

    public function journalEntry()
    {
        return $this->hasOne(JournalEntry::class, 'customer_payment_id');
    }

    /**
     * Create journal entry for customer payment
     * 
     * Double-entry logic:
     * Dr. Bank Account (1100) - Cash received
     * Cr. Accounts Receivable (1200) - Customer owes less
     */
    public function createJournalEntry(): JournalEntry
    {
        // Debit: Bank Account (cash comes in)
        $debitAccount = Account::where('code', '1100')->first();
        if (!$debitAccount) {
            $debitAccount = Account::where('code', '1000')->first(); // Cash fallback
        }

        // Credit: Accounts Receivable (customer owes less)
        $creditAccount = Account::where('code', '1200')->first();

        if (!$debitAccount || !$creditAccount) {
            throw new \Exception('Bank or Accounts Receivable account not found.');
        }

        $customerName = $this->customer?->name ?? 'Unknown Customer';
        $invoiceRef = $this->sale?->invoice_number ?? 'N/A';
        $description = "Payment received from {$customerName} for Invoice {$invoiceRef}";

        $journalEntry = JournalEntry::createEntry(
            [
                'date' => $this->payment_date,
                'type' => 'payment',
                'description' => $description,
                'customer_payment_id' => $this->id, // Link to payment
                'created_by' => auth()->id(),
                'status' => 'posted',
                'posted_at' => now(),
            ],
            [
                // Line 1: Debit Bank (cash received)
                [
                    'account_id' => $debitAccount->id,
                    'debit' => $this->amount_base ?? $this->amount,
                    'credit' => 0,
                    'description' => "Cash received - {$this->payment_method}",
                ],
                // Line 2: Credit Accounts Receivable (reduce customer debt)
                [
                    'account_id' => $creditAccount->id,
                    'debit' => 0,
                    'credit' => $this->amount_base ?? $this->amount,
                    'description' => "Payment from {$customerName}",
                ],
            ]
        );

        return $journalEntry;
    }

    public function getFormattedAmountAttribute(): string
    {
        $currency = Currency::where('code', $this->currency ?? 'UGX')->first();
        return $currency ? $currency->formatAmount($this->amount) : number_format($this->amount, 2);
    }
}
