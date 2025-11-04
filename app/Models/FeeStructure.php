<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\LogsActivity;

class FeeStructure extends Model
{
    use LogsActivity;

    protected $fillable = [
        'program_id',
        'name',
        'term',
        'academic_year',
        'amount',
        'currency',
        'is_mandatory',
        'description',
        'due_date',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
        'due_date' => 'date',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByTerm($query, $term, $year)
    {
        return $query->where('term', $term)->where('academic_year', $year);
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }
}
