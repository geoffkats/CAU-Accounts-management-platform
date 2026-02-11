<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\LogsActivity;
use App\Models\CompanySetting;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;

class Sale extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'program_id',
        'customer_id',
        'account_id',
        'document_type',
        'product_area_code',
        'invoice_number',
        'sale_date',
        'due_date',
        'validity_date',
        'delivery_date',
        'order_status',
        'amount',
        'currency',
        'amount_base',
        'exchange_rate',
        'amount_paid',
        'discount_amount',
        'tax_amount',
        'description',
        'terms_conditions',
        'receipt_number',
        'status',
        'payment_method',
        'reference_number',
        'notes',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'due_date' => 'date',
        'validity_date' => 'date',
        'delivery_date' => 'date',
        'amount' => 'decimal:2',
        'amount_base' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'amount_paid' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
    ];

    const STATUS_UNPAID = 'unpaid';
    const STATUS_PAID = 'paid';
    const STATUS_PARTIALLY_PAID = 'partially_paid';
    const STATUS_CANCELLED = 'cancelled';

    const DOC_INVOICE = 'invoice';
    const DOC_ESTIMATE = 'estimate';
    const DOC_QUOTATION = 'quotation';
    const DOC_SALES_ORDER = 'sales_order';
    const DOC_TILL_SALE = 'till_sale';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_UNPAID => 'Unpaid',
            self::STATUS_PAID => 'Paid',
            self::STATUS_PARTIALLY_PAID => 'Partially Paid',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public static function getDocumentTypes(): array
    {
        return [
            self::DOC_INVOICE => 'Invoice',
            self::DOC_ESTIMATE => 'Estimate',
            self::DOC_QUOTATION => 'Quotation',
            self::DOC_SALES_ORDER => 'Sales Order',
            self::DOC_TILL_SALE => 'Till Sale',
        ];
    }

    public function postsToLedger(): bool
    {
        $type = $this->document_type ?: self::DOC_INVOICE;
        return in_array($type, [self::DOC_INVOICE, self::DOC_TILL_SALE], true);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency', 'code');
    }

    public function journalEntry()
    {
        return $this->hasOne(JournalEntry::class, 'sales_id');
    }

    public function payments()
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function updatePaymentStatus(): void
    {
        // Refresh the model to get latest data
        $this->refresh();
        
        // Sum payments in the sale's currency (not base currency)
        // This ensures amount_paid matches the sale's amount currency
        $totalPaid = $this->payments()
            ->where('currency', $this->currency)
            ->sum('amount');
        
        // If there are payments in different currencies, convert them
        $otherCurrencyPayments = $this->payments()
            ->where('currency', '!=', $this->currency)
            ->get();
        
        foreach ($otherCurrencyPayments as $payment) {
            // Convert payment to sale currency
            if ($payment->exchange_rate && $this->exchange_rate) {
                // Convert: payment amount -> base currency -> sale currency
                $inBaseCurrency = $payment->amount * $payment->exchange_rate;
                $inSaleCurrency = $inBaseCurrency / $this->exchange_rate;
                $totalPaid += $inSaleCurrency;
            } else {
                // If no exchange rates, just add the amount (fallback)
                $totalPaid += $payment->amount;
            }
        }
        
        // Determine status
        if ($totalPaid >= $this->amount) {
            $status = self::STATUS_PAID;
        } elseif ($totalPaid > 0) {
            $status = self::STATUS_PARTIALLY_PAID;
        } else {
            $status = self::STATUS_UNPAID;
        }
        
        // Update using DB query to bypass model events and ensure save
        \DB::table('sales')
            ->where('id', $this->id)
            ->update([
                'amount_paid' => $totalPaid,
                'status' => $status,
                'updated_at' => now(),
            ]);
        
        // Refresh model to reflect changes
        $this->refresh();
    }

    public function getRemainingBalanceAttribute(): float
    {
        return max(0, $this->amount - $this->amount_paid);
    }

    public function getFormattedAmountAttribute(): string
    {
        $currency = Currency::where('code', $this->currency ?? 'UGX')->first();
        return $currency ? $currency->formatAmount($this->amount) : number_format($this->amount, 2);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopePosting($query)
    {
        return $query->whereIn('document_type', [self::DOC_INVOICE, self::DOC_TILL_SALE]);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('status', self::STATUS_UNPAID);
    }

    public function markAsPaid(): void
    {
        $this->amount_paid = $this->amount;
        $this->status = self::STATUS_PAID;
        $this->save();
    }

    /**
     * Create journal entry for this sale/income
     * 
     * Double-entry logic:
     * - If paid: Dr. Bank (1100), Cr. Income Account (4xxx)
     * - If unpaid: Dr. Accounts Receivable (1200), Cr. Income Account (4xxx)
     */
    public function createJournalEntry(): JournalEntry
    {
        if (!$this->postsToLedger()) {
            throw new \Exception('Non-posting sales documents do not create journal entries.');
        }

        // Determine debit account (where money goes)
        if ($this->status === self::STATUS_PAID || $this->status === self::STATUS_PARTIALLY_PAID) {
            // Paid: Debit Bank Account
            $debitAccount = Account::where('code', '1100')->first(); // Bank Account
            if (!$debitAccount) {
                $debitAccount = Account::where('code', '1000')->first(); // Cash fallback
            }
        } else {
            // Unpaid: Debit Accounts Receivable
            $debitAccount = Account::where('code', '1200')->first(); // Accounts Receivable
        }

        if (!$debitAccount) {
            throw new \Exception('Debit account not found. Please ensure Bank or Accounts Receivable accounts exist.');
        }

        // Credit account is the income account
        $creditAccount = $this->account;
        if (!$creditAccount) {
            throw new \Exception('Income account not found. Please assign an income account to this sale.');
        }

        // Build description
        $customerName = $this->customer?->name ?? 'Unknown Customer';
        $description = "Income: {$this->description} - From: {$customerName}";

        // Create journal entry with two lines (Dr. Bank/AR, Cr. Income)
        $journalEntry = JournalEntry::createEntry(
            [
                'date' => $this->sale_date,
                'type' => 'income',
                'description' => $description,
                'sales_id' => $this->id,
                'created_by' => auth()->id(),
                'status' => 'posted',
                'posted_at' => now(),
            ],
            [
                // Line 1: Debit Bank or Accounts Receivable
                [
                    'account_id' => $debitAccount->id,
                    'debit' => $this->amount_base ?? $this->amount,
                    'credit' => 0,
                    'description' => $this->status === self::STATUS_PAID || $this->status === self::STATUS_PARTIALLY_PAID
                        ? "Payment received from {$customerName}"
                        : "Accounts Receivable - {$customerName}",
                ],
                // Line 2: Credit Income Account
                [
                    'account_id' => $creditAccount->id,
                    'debit' => 0,
                    'credit' => $this->amount_base ?? $this->amount,
                    'description' => $this->description,
                ],
            ]
        );

        return $journalEntry;
    }

    protected static function booted(): void
    {
        // Auto-calculate base currency amount
        static::saving(function (self $sale) {
            if ($sale->isDirty(['amount', 'currency'])) {
                $baseCurrency = Currency::getBaseCurrency();
                if ($baseCurrency && $sale->currency && $sale->currency !== $baseCurrency->code) {
                    $rate = ExchangeRate::getRate($sale->currency, $baseCurrency->code);
                    if ($rate) {
                        $sale->exchange_rate = $rate;
                        $sale->amount_base = $sale->amount * $rate;
                    }
                } else {
                    $sale->amount_base = $sale->amount;
                    $sale->exchange_rate = 1.0;
                }
            }
        });

        // Auto-create journal entry when sale is created (posting documents only)
        static::created(function (self $sale) {
            if (!$sale->postsToLedger()) {
                return;
            }
            try {
                $sale->createJournalEntry();
            } catch (\Exception $e) {
                // Log error but don't fail sale creation
                \Log::error('Failed to create journal entry for sale: ' . $e->getMessage());
            }
        });

        $check = function (self $model) {
            $settings = CompanySetting::get();
            if ($settings->lock_before_date) {
                $date = Carbon::parse($model->sale_date);
                if ($date->lt($settings->lock_before_date)) {
                    throw ValidationException::withMessages([
                        'sale_date' => 'Transactions before ' . $settings->lock_before_date->format('Y-m-d') . ' are locked by compliance policy.',
                    ]);
                }
            }
        };

        static::creating($check);
        static::updating($check);

        // Keep sale journal entry in sync when sale is updated
        static::updated(function (self $sale) {
            try {
                $entry = JournalEntry::where('sales_id', $sale->id)->latest('id')->first();
                $oldId = $entry?->id;
                if ($entry) {
                    $entry->void();
                }

                if ($sale->postsToLedger()) {
                    $newEntry = $sale->createJournalEntry();
                    if ($oldId) {
                        $newEntry->update(['replaces_entry_id' => $oldId]);
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Failed to recreate sale journal entry on update: ' . $e->getMessage());
            }
        });
    }
}
