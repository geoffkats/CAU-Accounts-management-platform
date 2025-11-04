<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\LogsActivity;

class PaymentPlan extends Model
{
    use LogsActivity;

    protected $fillable = [
        'student_id',
        'student_invoice_id',
        'total_amount',
        'number_of_installments',
        'frequency',
        'start_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'number_of_installments' => 'integer',
        'start_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::created(function ($plan) {
            $plan->generateInstallments();
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(StudentInvoice::class, 'student_invoice_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(PaymentPlanInstallment::class);
    }

    public function generateInstallments(): void
    {
        $installmentAmount = $this->total_amount / $this->number_of_installments;
        $currentDate = $this->start_date;

        for ($i = 1; $i <= $this->number_of_installments; $i++) {
            PaymentPlanInstallment::create([
                'payment_plan_id' => $this->id,
                'installment_number' => $i,
                'due_date' => $currentDate,
                'amount' => $installmentAmount,
                'paid_amount' => 0,
                'status' => 'pending',
            ]);

            // Calculate next due date based on frequency
            $currentDate = match($this->frequency) {
                'weekly' => $currentDate->addWeek(),
                'biweekly' => $currentDate->addWeeks(2),
                'monthly' => $currentDate->addMonth(),
                default => $currentDate->addMonth(),
            };
        }
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->installments()->sum('paid_amount');
    }

    public function getTotalRemainingAttribute(): float
    {
        return $this->total_amount - $this->total_paid;
    }

    public function getNextDueInstallmentAttribute()
    {
        return $this->installments()
            ->where('status', 'pending')
            ->where('due_date', '>=', now())
            ->orderBy('due_date')
            ->first();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
