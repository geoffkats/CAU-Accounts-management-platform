<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\LogsActivity;

class Scholarship extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'type',
        'amount',
        'percentage',
        'sponsor',
        'description',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'percentage' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function studentScholarships(): HasMany
    {
        return $this->hasMany(StudentScholarship::class);
    }

    // Calculate scholarship amount for a given fee amount
    public function calculateAmount(float $feeAmount): float
    {
        return match($this->type) {
            'full' => $feeAmount,
            'partial' => min($this->amount, $feeAmount),
            'percentage' => ($feeAmount * $this->percentage) / 100,
            default => 0,
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'full' => 'Full Scholarship (100%)',
            'partial' => "Partial ({$this->amount})",
            'percentage' => "Percentage ({$this->percentage}%)",
            default => 'Unknown',
        };
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            });
    }
}
