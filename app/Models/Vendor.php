<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\LogsActivity;

class Vendor extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'service_type',
        'email',
        'phone',
        'address',
        'company',
        'tax_id',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(VendorInvoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(VendorPayment::class);
    }

    public function getTotalExpensesAttribute(): float
    {
        return $this->expenses()->sum('amount');
    }

    public function getTotalInvoicesAttribute(): float
    {
        return $this->invoices()->sum('amount');
    }

    public function getTotalOwedAttribute(): float
    {
        return $this->invoices()->whereIn('status', ['unpaid', 'partially_paid'])->sum('amount') - 
               $this->invoices()->whereIn('status', ['unpaid', 'partially_paid'])->sum('amount_paid');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
