<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\LogsActivity;

class ExchangeRate extends Model
{
    use LogsActivity;

    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'effective_date',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:6',
            'effective_date' => 'date',
        ];
    }

    public function fromCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'from_currency', 'code');
    }

    public function toCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'to_currency', 'code');
    }

    public static function getRate(string $from, string $to, ?\Carbon\Carbon $date = null): ?float
    {
        if ($from === $to) {
            return 1.0;
        }

        $date = $date ?? now();

        $rate = static::where('from_currency', $from)
            ->where('to_currency', $to)
            ->where('effective_date', '<=', $date)
            ->latest('effective_date')
            ->first();

        return $rate?->rate;
    }

    public static function convert(float $amount, string $from, string $to, ?\Carbon\Carbon $date = null): ?float
    {
        $rate = static::getRate($from, $to, $date);
        
        if ($rate === null) {
            return null;
        }

        return $amount * $rate;
    }

    public static function upsertRate(string $from, string $to, float $rate, string $date, string $source = 'api'): void
    {
        static::updateOrCreate(
            [
                'from_currency' => $from,
                'to_currency' => $to,
                'effective_date' => $date,
            ],
            [
                'rate' => $rate,
                'source' => $source,
            ]
        );
    }
}
