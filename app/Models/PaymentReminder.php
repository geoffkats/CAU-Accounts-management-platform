<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReminder extends Model
{
    protected $fillable = [
        'student_invoice_id',
        'type',
        'scheduled_date',
        'sent_date',
        'status',
        'message',
    ];

    protected $casts = [
        'scheduled_date' => 'datetime',
        'sent_date' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(StudentInvoice::class, 'student_invoice_id');
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_date' => now(),
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where('scheduled_date', '<=', now());
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}
