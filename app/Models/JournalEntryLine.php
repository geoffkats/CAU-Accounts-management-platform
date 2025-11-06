<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\LogsActivity;

class JournalEntryLine extends Model
{
    use LogsActivity;
    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'debit',
        'credit',
        'description',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the net effect (debit - credit)
     * Positive = Debit balance
     * Negative = Credit balance
     * Formula: debit - credit
     */
    public function getNetAmount(): float
    {
        return (float) ($this->debit - $this->credit);
    }

    /**
     * Check if this is a debit entry
     */
    public function isDebit(): bool
    {
        return $this->debit > 0;
    }

    /**
     * Check if this is a credit entry
     */
    public function isCredit(): bool
    {
        return $this->credit > 0;
    }

    /**
     * Get the absolute amount (whichever is non-zero)
     */
    public function getAmount(): float
    {
        return (float) max($this->debit, $this->credit);
    }

    /**
     * Validation: Ensure only one of debit or credit is non-zero
     */
    protected static function booted()
    {
        static::saving(function ($line) {
            // Ensure both debit and credit are not both > 0
            if ($line->debit > 0 && $line->credit > 0) {
                throw new \Exception('A line cannot have both debit and credit amounts');
            }

            // Ensure at least one is > 0
            if ($line->debit == 0 && $line->credit == 0) {
                throw new \Exception('A line must have either a debit or credit amount');
            }
        });
    }
}
