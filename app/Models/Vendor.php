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
        'vendor_type',
        'service_type',
        'email',
        'phone',
        'address',
        'company',
        'tin',
        'account_number',
        'payment_method',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'mobile_money_provider',
        'mobile_money_number',
        'business_type',
        'currency',
        'ussd_provider',
        'ussd_number',
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
