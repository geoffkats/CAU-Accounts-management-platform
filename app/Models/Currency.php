<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\LogsActivity;

class Currency extends Model
{
    use LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'is_base',
        'is_active',
        'decimal_places',
    ];

    protected function casts(): array
    {
        return [
            'is_base' => 'boolean',
            'is_active' => 'boolean',
            'decimal_places' => 'integer',
        ];
    }

    public function exchangeRatesFrom(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'from_currency', 'code');
    }

    public function exchangeRatesTo(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'to_currency', 'code');
    }

    public function formatAmount(float $amount): string
    {
        return $this->symbol . ' ' . number_format($amount, $this->decimal_places);
    }

    public static function getBaseCurrency(): ?self
    {
        return static::where('is_base', true)->first();
    }

    public static function getActive(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)->orderBy('code')->get();
    }

    public function getLatestRateTo(string $toCurrency): ?float
    {
        $rate = ExchangeRate::where('from_currency', $this->code)
            ->where('to_currency', $toCurrency)
            ->latest('effective_date')
            ->first();

        return $rate?->rate;
    }
}
