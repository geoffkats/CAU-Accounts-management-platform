<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'changes',
        'ip_address',
        'url',
        'user_agent',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException('Activity logs are immutable and cannot be updated.');
        });
        static::deleting(function () {
            throw new \RuntimeException('Activity logs are immutable and cannot be deleted.');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
