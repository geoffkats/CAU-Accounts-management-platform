<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_run_id',
        'staff_id',
        'program_id',
        'hours_worked',
        'classes_taught',
        'gross_amount',
        'paye_amount',
        'nssf_employee',
        'nssf_employer',
        'other_deductions',
        'bonuses',
        'net_amount',
        'payment_status',
        'transaction_reference',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'hours_worked' => 'decimal:2',
        'classes_taught' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'paye_amount' => 'decimal:2',
        'nssf_employee' => 'decimal:2',
        'nssf_employer' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'bonuses' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Calculate PAYE (Uganda tax rates 2025)
     */
    public static function calculatePAYE(float $grossAmount): float
    {
        $annualIncome = $grossAmount * 12;
        $tax = 0;

        if ($annualIncome <= 2820000) {
            // No tax
            $tax = 0;
        } elseif ($annualIncome <= 4020000) {
            // 10% on amount above 2,820,000
            $tax = ($annualIncome - 2820000) * 0.10;
        } elseif ($annualIncome <= 4920000) {
            // 120,000 + 20% on amount above 4,020,000
            $tax = 120000 + (($annualIncome - 4020000) * 0.20);
        } elseif ($annualIncome <= 120000000) {
            // 300,000 + 30% on amount above 4,920,000
            $tax = 300000 + (($annualIncome - 4920000) * 0.30);
        } else {
            // 34,824,000 + 40% on amount above 120,000,000
            $tax = 34824000 + (($annualIncome - 120000000) * 0.40);
        }

        return round($tax / 12, 2);
    }

    /**
     * Calculate NSSF (Uganda rates 2025)
     */
    public static function calculateNSSF(float $grossAmount): array
    {
        $maxContribution = 200000; // Maximum monthly contribution base
        $contributionBase = min($grossAmount, $maxContribution);
        
        $employeeContribution = round($contributionBase * 0.05, 2); // 5%
        $employerContribution = round($contributionBase * 0.10, 2); // 10%

        return [
            'employee' => $employeeContribution,
            'employer' => $employerContribution,
        ];
    }

    /**
     * Calculate net amount
     */
    public function calculateNet(): void
    {
        $this->net_amount = $this->gross_amount 
            - $this->paye_amount 
            - $this->nssf_employee 
            - $this->other_deductions 
            + $this->bonuses;
        $this->save();
    }

    public function markAsPaid(string $transactionRef = null): void
    {
        $this->payment_status = 'paid';
        $this->transaction_reference = $transactionRef;
        $this->paid_at = now();
        $this->save();
    }
}
