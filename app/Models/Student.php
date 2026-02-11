<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\LogsActivity;

class Student extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'student_id',
        'program_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'address',
        'guardian_name',
        'guardian_phone',
        'guardian_email',
        'enrollment_date',
        'status',
        'class_level',
        'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'enrollment_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function ($student) {
            if (empty($student->student_id)) {
                $student->student_id = self::generateStudentId();
            }
        });
    }

    public static function generateStudentId(): string
    {
        $year = now()->year;
        $lastStudent = self::where('student_id', 'like', "STU-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastStudent) {
            $lastNumber = (int) substr($lastStudent->student_id, -3);
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        return "STU-{$year}-{$newNumber}";
    }

    // Relationships
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(StudentInvoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(StudentPayment::class);
    }

    public function scholarships(): HasMany
    {
        return $this->hasMany(StudentScholarship::class);
    }

    public function paymentPlans(): HasMany
    {
        return $this->hasMany(PaymentPlan::class);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getTotalOwedAttribute(): float
    {
        return $this->invoices()
            ->whereIn('status', ['sent', 'partially_paid', 'overdue'])
            ->sum('balance');
    }

    public function getTotalPaidAttribute(): float
    {
        return $this->payments()->sum('amount_base');
    }

    public function getAccountStatusAttribute(): string
    {
        $owed = $this->total_owed;
        
        if ($owed == 0) return 'clear';
        if ($owed > 0) {
            $overdueInvoices = $this->invoices()
                ->where('status', 'overdue')
                ->count();
            
            return $overdueInvoices > 0 ? 'overdue' : 'outstanding';
        }
        
        return 'credit';
    }

    public function getActiveScholarshipAttribute()
    {
        return $this->scholarships()
            ->where('status', 'active')
            ->with('scholarship')
            ->first();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByProgram($query, $programId)
    {
        return $query->where('program_id', $programId);
    }

    public function scopeWithOutstandingBalance($query)
    {
        return $query->whereHas('invoices', function ($q) {
            $q->whereIn('status', ['sent', 'partially_paid', 'overdue'])
              ->where('balance', '>', 0);
        });
    }
}
