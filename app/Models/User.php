<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use App\Models\Concerns\LogsActivity;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is accountant
     */
    public function isAccountant(): bool
    {
        return $this->role === 'accountant';
    }

    protected static function booted(): void
    {
        static::updated(function (self $user) {
            if ($user->wasChanged('role')) {
                \App\Models\ActivityLog::create([
                    'user_id'    => auth()->id(),
                    'action'     => 'role_changed',
                    'model_type' => self::class,
                    'model_id'   => $user->id,
                    'changes'    => [
                        'before' => ['role' => $user->getOriginal('role')],
                        'after'  => ['role' => $user->role],
                    ],
                    'ip_address' => request()->ip() ?? null,
                    'url'        => request()->fullUrl() ?? null,
                    'user_agent' => request()->header('User-Agent') ?? null,
                    'prev_hash'  => \App\Models\ActivityLog::query()->orderByDesc('id')->value('hash'),
                    'hash'       => hash('sha256', json_encode(['id'=>$user->id,'role_before'=>$user->getOriginal('role'),'role_after'=>$user->role]) . microtime(true)),
                ]);
            }
        });
    }
}
