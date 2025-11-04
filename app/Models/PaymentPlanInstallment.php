<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentPlanInstallment extends Model
{
    protected $fillable = [
        'payment_plan_id',
        'installment_number',
        'due_date',
        'amount',
        'paid_amount',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'installment_number' => 'integer',
    ];

    public function paymentPlan(): BelongsTo
    {
        return $this->belongsTo(PaymentPlan::class);
    }

    public function getRemainingAmountAttribute(): float
    {
        return $this->amount - $this->paid_amount;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== 'paid' 
            && $this->due_date->isPast() 
            && $this->remaining_amount > 0;
    }

    public function recordPayment(float $amount): void
    {
        $this->paid_amount += $amount;
        
        if ($this->paid_amount >= $this->amount) {
            $this->status = 'paid';
            $this->paid_amount = $this->amount; // Cap at installment amount
        } else {
            $this->status = 'partial';
        }
        
        $this->save();
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'paid')
            ->where('due_date', '<', now());
    }
}
