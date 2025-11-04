<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\LogsActivity;

class Account extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'type',
        'description',
        'parent_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    const TYPE_ASSET = 'asset';
    const TYPE_LIABILITY = 'liability';
    const TYPE_INCOME = 'income';
    const TYPE_EXPENSE = 'expense';
    const TYPE_EQUITY = 'equity';

    public static function getTypes(): array
    {
        return [
            self::TYPE_ASSET => 'Asset',
            self::TYPE_LIABILITY => 'Liability',
            self::TYPE_EQUITY => 'Equity',
            self::TYPE_INCOME => 'Income',
            self::TYPE_EXPENSE => 'Expense',
        ];
    }

    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
