<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'run_number',
        'period_start',
        'period_end',
        'payment_date',
        'status',
        'total_gross',
        'total_paye',
        'total_nssf',
        'total_net',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'payment_date' => 'date',
        'total_gross' => 'decimal:2',
        'total_paye' => 'decimal:2',
        'total_nssf' => 'decimal:2',
        'total_net' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function recalculateTotals(): void
    {
        $this->total_gross = $this->items()->sum('gross_amount');
        $this->total_paye = $this->items()->sum('paye_amount');
        $this->total_nssf = $this->items()->sum('nssf_employee') + $this->items()->sum('nssf_employer');
        $this->total_net = $this->items()->sum('net_amount');
        $this->save();
    }

    public function approve(User $user): void
    {
        $this->status = 'approved';
        $this->approved_by = $user->id;
        $this->approved_at = now();
        $this->save();
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
