<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentInvoiceItem extends Model
{
    protected $fillable = [
        'student_invoice_id',
        'fee_structure_id',
        'description',
        'amount',
        'quantity',
        'total',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'quantity' => 'integer',
        'total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function ($item) {
            $item->total = $item->amount * $item->quantity;
        });

        static::updating(function ($item) {
            $item->total = $item->amount * $item->quantity;
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(StudentInvoice::class, 'student_invoice_id');
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }
}
