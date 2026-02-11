<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Staff extends Model
{
    use HasFactory;

    protected $table = 'staff';

    protected $fillable = [
        'employee_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'employment_type',
        'payment_method',
        'mobile_money_provider',
        'mobile_money_number',
        'bank_name',
        'bank_account',
        'nssf_number',
        'tin_number',
        'base_salary',
        'hourly_rate',
        'is_active',
        'hire_date',
        'termination_date',
        'notes',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'hire_date' => 'date',
        'termination_date' => 'date',
    ];

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(InstructorAssignment::class);
    }

    public function activeAssignments(): HasMany
    {
        return $this->hasMany(InstructorAssignment::class)->where('is_active', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInstructors($query)
    {
        return $query->whereIn('employment_type', ['part_time', 'contract', 'consultant']);
    }

    public function scopeFullTime($query)
    {
        return $query->where('employment_type', 'full_time');
    }
}
