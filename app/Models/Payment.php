<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Concerns\LogsActivity;

class Payment extends Model
{
    use LogsActivity;

    protected $fillable = [
        'voucher_number',
        'expense_id',
        'payment_date',
        'payment_account_id',
        'amount',
        'payment_method',
        'payment_reference',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    public function journalEntry(): HasOne
    {
        return $this->hasOne(JournalEntry::class, 'payment_id');
    }

    /**
     * Create journal entry for this payment voucher
     * 
     * DOUBLE-ENTRY BOOKKEEPING FOR PAYMENT:
     * Dr. Accounts Payable (2000)    XXX   (reduce liability)
     *     Cr. Cash/Bank (1xxx)       XXX   (reduce asset - money out)
     */
    public function createJournalEntry(): JournalEntry
    {
        $expense = $this->expense;
        $paymentAccount = $this->paymentAccount;

        // Get Accounts Payable account
        $accountsPayable = Account::where('code', '2000')->first();
        if (!$accountsPayable) {
            throw new \Exception('Accounts Payable account (2000) not found. Please create it first.');
        }

        // Build description
        $payeeName = $expense->vendor?->name ?? $expense->staff?->first_name . ' ' . $expense->staff?->last_name ?? 'Unknown';
        $description = "Payment Voucher #{$this->id} - Payment to {$payeeName} for {$expense->description}";

        // Create journal entry with two lines
        $journalEntry = JournalEntry::createEntry(
            [
                'date' => $this->payment_date,
                'type' => 'payment',
                'description' => $description,
                'payment_id' => $this->id,
                'expense_id' => $this->expense_id,
                'created_by' => auth()->id(),
                'status' => 'posted',
                'posted_at' => now(),
            ],
            [
                // Line 1: Debit Accounts Payable (reduce liability)
                [
                    'account_id' => $accountsPayable->id,
                    'debit' => $this->amount,
                    'credit' => 0,
                    'description' => "Payment to {$payeeName}",
                ],
                // Line 2: Credit Cash/Bank (money out)
                [
                    'account_id' => $this->payment_account_id,
                    'debit' => 0,
                    'credit' => $this->amount,
                    'description' => "Payment from {$paymentAccount->name}",
                ],
            ]
        );

        return $journalEntry;
    }

    /**
     * Generate next voucher number
     */
    public static function generateVoucherNumber(): string
    {
        $lastPayment = self::orderBy('id', 'desc')->first();
        
        if (!$lastPayment || !$lastPayment->voucher_number) {
            return 'PV-0001';
        }

        // Extract number from last voucher (e.g., PV-0001 -> 1)
        $lastNumber = (int) substr($lastPayment->voucher_number, 3);
        $nextNumber = $lastNumber + 1;

        return 'PV-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    protected static function booted(): void
    {
        // Auto-generate voucher number before creating
        static::creating(function (self $payment) {
            if (!$payment->voucher_number) {
                $payment->voucher_number = self::generateVoucherNumber();
            }
        });

        // Auto-create journal entry when payment is created
        static::created(function (self $payment) {
            try {
                $payment->createJournalEntry();
            } catch (\Exception $e) {
                \Log::error('Failed to create journal entry for payment: ' . $e->getMessage());
            }
        });

        // Keep payment journal entry in sync when updated
        static::updated(function (self $payment) {
            try {
                $entry = JournalEntry::where('payment_id', $payment->id)->latest('id')->first();
                $oldId = $entry?->id;
                if ($entry) {
                    $entry->void();
                }
                $newEntry = $payment->createJournalEntry();
                if ($oldId) {
                    $newEntry->update(['replaces_entry_id' => $oldId]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to recreate payment journal entry on update: ' . $e->getMessage());
            }
        });

        // Void the linked journal entry when payment is deleted
        static::deleted(function (self $payment) {
            try {
                $entry = JournalEntry::where('payment_id', $payment->id)->latest('id')->first();
                if ($entry) {
                    $entry->void();
                }
            } catch (\Exception $e) {
                \Log::error('Failed to void payment journal entry on delete: ' . $e->getMessage());
            }
        });
    }
}
