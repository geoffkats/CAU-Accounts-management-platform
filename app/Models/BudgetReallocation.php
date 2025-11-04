<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\LogsActivity;

class BudgetReallocation extends Model
{
    use LogsActivity;

    protected $fillable = [
        'from_budget_id',
        'to_budget_id',
        'amount',
        'category',
        'reason',
        'status',
        'requested_by',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    public function fromBudget(): BelongsTo
    {
        return $this->belongsTo(ProgramBudget::class, 'from_budget_id');
    }

    public function toBudget(): BelongsTo
    {
        return $this->belongsTo(ProgramBudget::class, 'to_budget_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }
}
