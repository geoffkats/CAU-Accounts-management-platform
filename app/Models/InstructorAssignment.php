<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'program_id',
        'start_date',
        'end_date',
        'rate_per_hour',
        'rate_per_class',
        'fixed_amount',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'rate_per_hour' => 'decimal:2',
        'rate_per_class' => 'decimal:2',
        'fixed_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function calculatePayment(float $hoursOrClasses): float
    {
        if ($this->fixed_amount) {
            return (float) $this->fixed_amount;
        }

        if ($this->rate_per_hour) {
            return (float) $this->rate_per_hour * $hoursOrClasses;
        }

        if ($this->rate_per_class) {
            return (float) $this->rate_per_class * $hoursOrClasses;
        }

        return 0;
    }
}
