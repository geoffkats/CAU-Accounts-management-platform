<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use App\Models\Concerns\LogsActivity;
use App\Models\CompanySetting;
use Illuminate\Validation\ValidationException;

class JournalEntry extends Model
{
    use LogsActivity;
    protected $fillable = [
        'date',
        'reference',
        'type',
        'description',
        'expense_id',
        'payment_id',
        'income_id',
        'sales_id',
        'customer_payment_id',
        'replaces_entry_id',
        'created_by',
        'status',
        'posted_at',
        'voided_at',
    ];

    protected $casts = [
        'date' => 'date',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sales_id');
    }

    public function customerPayment(): BelongsTo
    {
        return $this->belongsTo(CustomerPayment::class);
    }

    public function income(): BelongsTo
    {
        return $this->belongsTo(Income::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function replaces(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_entry_id');
    }

    public function replacements(): HasMany
    {
        return $this->hasMany(self::class, 'replaces_entry_id');
    }

    /**
     * Accounting Methods
     */
    
    /**
     * Calculate total debits for this entry
     * Formula: SUM(debit column)
     */
    public function totalDebits(): float
    {
        return (float) $this->lines()->sum('debit');
    }

    /**
     * Calculate total credits for this entry
     * Formula: SUM(credit column)
     */
    public function totalCredits(): float
    {
        return (float) $this->lines()->sum('credit');
    }

    /**
     * Check if entry is balanced (debits = credits)
     * Formula: SUM(debits) - SUM(credits) = 0
     */
    public function isBalanced(): bool
    {
        $difference = abs($this->totalDebits() - $this->totalCredits());
        return $difference < 0.01; // Allow for rounding errors (less than 1 cent)
    }

    /**
     * Get the imbalance amount (should be 0 for valid entries)
     * Formula: |SUM(debits) - SUM(credits)|
     */
    public function getImbalance(): float
    {
        return abs($this->totalDebits() - $this->totalCredits());
    }

    /**
     * Generate next reference number
     */
    public static function generateReference(string $type = 'JE'): string
    {
        $prefix = match($type) {
            'expense' => 'EXP',
            'income' => 'INC',
            'transfer' => 'TRF',
            'adjustment' => 'ADJ',
            'opening_balance' => 'OB',
            default => 'JE',
        };

        $latest = static::where('reference', 'like', "{$prefix}-%")
            ->orderBy('reference', 'desc')
            ->first();

        if (!$latest) {
            return "{$prefix}-0001";
        }

        $number = (int) substr($latest->reference, strlen($prefix) + 1);
        return sprintf("%s-%04d", $prefix, $number + 1);
    }

    /**
     * Create a journal entry with automatic validation
     */
    public static function createEntry(array $data, array $lines): self
    {
        return DB::transaction(function () use ($data, $lines) {
            // Enforce period lock: no journal dated before lock_before_date
            $settings = CompanySetting::get();
            if (!empty($data['date']) && $settings->lock_before_date) {
                $entryDate = \Carbon\Carbon::parse($data['date']);
                if ($entryDate->lt($settings->lock_before_date)) {
                    throw ValidationException::withMessages([
                        'date' => 'Posting is locked before ' . $settings->lock_before_date->format('Y-m-d') . '.',
                    ]);
                }
            }
            // Validate that debits = credits before creating
            $totalDebits = collect($lines)->sum('debit');
            $totalCredits = collect($lines)->sum('credit');
            
            if (abs($totalDebits - $totalCredits) > 0.01) {
                throw new \Exception(
                    "Journal entry is not balanced. Debits: {$totalDebits}, Credits: {$totalCredits}"
                );
            }

            // Generate reference if not provided
            if (empty($data['reference'])) {
                $data['reference'] = static::generateReference($data['type'] ?? 'JE');
            }

            // Create the journal entry
            $entry = static::create($data);

            // Create the lines
            foreach ($lines as $line) {
                $entry->lines()->create($line);
            }

            return $entry->fresh('lines');
        });
    }

    /**
     * Post a draft entry
     */
    public function post(): bool
    {
        if ($this->status !== 'draft') {
            throw new \Exception('Only draft entries can be posted');
        }

        // Enforce period lock before posting
        $settings = CompanySetting::get();
        if ($this->date && $settings->lock_before_date) {
            if ($this->date->lt($settings->lock_before_date)) {
                throw ValidationException::withMessages([
                    'date' => 'Posting is locked before ' . $settings->lock_before_date->format('Y-m-d') . '.',
                ]);
            }
        }

        if (!$this->isBalanced()) {
            throw new \Exception('Cannot post unbalanced entry');
        }

        return $this->update([
            'status' => 'posted',
            'posted_at' => now(),
        ]);
    }

    /**
     * Void an entry (reverse it)
     */
    public function void(): bool
    {
        if ($this->status === 'void') {
            throw new \Exception('Entry is already void');
        }

        return $this->update([
            'status' => 'void',
            'voided_at' => now(),
        ]);
    }

    /**
     * Scopes
     */
    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
}
