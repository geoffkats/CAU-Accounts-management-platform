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

    public function journalEntry()
    {
        return $this->hasOne(JournalEntry::class, 'expense_id');
    }

    /**
     * Create journal entry for vendor payment
     * 
     * Double-entry logic:
     * Dr. Accounts Payable (2000) - We owe less to vendor
     * Cr. Bank Account (1100) - Cash goes out
     */
    public function createJournalEntry(): JournalEntry
    {
        // Debit: Accounts Payable (reduce what we owe)
        $debitAccount = Account::where('code', '2000')->first();

        // Credit: Bank Account (cash goes out)
        $creditAccount = Account::where('code', '1100')->first();
        if (!$creditAccount) {
            $creditAccount = Account::where('code', '1000')->first(); // Cash fallback
        }

        if (!$debitAccount || !$creditAccount) {
            throw new \Exception('Accounts Payable or Bank account not found.');
        }

        $vendorName = $this->vendor?->name ?? 'Unknown Vendor';
        $invoiceRef = $this->vendorInvoice?->invoice_number ?? 'N/A';
        $description = "Payment to {$vendorName} for Invoice {$invoiceRef}";

        $journalEntry = JournalEntry::createEntry(
            [
                'date' => $this->payment_date,
                'type' => 'payment',
                'description' => $description,
                'expense_id' => $this->id, // Link to payment
                'created_by' => auth()->id(),
                'status' => 'posted',
                'posted_at' => now(),
            ],
            [
                // Line 1: Debit Accounts Payable (reduce liability)
                [
                    'account_id' => $debitAccount->id,
                    'debit' => $this->amount_base ?? $this->amount,
                    'credit' => 0,
                    'description' => "Payment to {$vendorName}",
                ],
                // Line 2: Credit Bank (cash paid out)
                [
                    'account_id' => $creditAccount->id,
                    'debit' => 0,
                    'credit' => $this->amount_base ?? $this->amount,
                    'description' => "Payment via {$this->payment_method}",
                ],
            ]
        );

        return $journalEntry;
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
            
            // Create journal entry
            try {
                $payment->createJournalEntry();
            } catch (\Exception $e) {
                \Log::error('Failed to create journal entry for vendor payment: ' . $e->getMessage());
            }
        });

        static::deleted(function (VendorPayment $payment) {
            $payment->vendorInvoice->updatePaymentStatus();
        });

        // Immutable sync on update: void and recreate journal entry
        static::updated(function (VendorPayment $payment) {
            try {
                $entry = JournalEntry::where('expense_id', $payment->id)->latest('id')->first();
                $oldId = $entry?->id;
                if ($entry) {
                    $entry->void();
                }
                $newEntry = $payment->createJournalEntry();
                if ($oldId) {
                    $newEntry->update(['replaces_entry_id' => $oldId]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to recreate journal entry for vendor payment: ' . $e->getMessage());
            }
        });
    }
}
