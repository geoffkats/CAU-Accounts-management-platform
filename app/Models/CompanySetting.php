<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\LogsActivity;

class CompanySetting extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'company_name',
        'company_email',
        'company_phone',
        'company_address',
        'tax_id',
        'logo_path',
        'currency',
        'currency_symbol',
        'fiscal_year_start',
        'fiscal_year_end',
        'lock_before_date',
        'date_format',
        'timezone',
    ];

    protected $casts = [
        'fiscal_year_start' => 'date',
        'fiscal_year_end' => 'date',
        'lock_before_date' => 'date',
    ];

    public static function get()
    {
        return self::first() ?? new self([
            'currency' => 'UGX',
            'currency_symbol' => 'UGX',
            'date_format' => 'd/m/Y',
            'timezone' => 'Africa/Kampala',
        ]);
    }
}
