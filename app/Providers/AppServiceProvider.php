<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use App\Models\ActivityLog;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ensure compatibility with older MySQL versions (index key length limits)
        Schema::defaultStringLength(191);

        Event::listen(Login::class, function (Login $event) {
            ActivityLog::create([
                'user_id'    => $event->user->id ?? null,
                'action'     => 'login',
                'model_type' => 'auth',
                'model_id'   => null,
                'changes'    => null,
                'ip_address' => request()->ip() ?? null,
                'url'        => request()->fullUrl() ?? null,
                'user_agent' => request()->header('User-Agent') ?? null,
                'prev_hash'  => ActivityLog::query()->orderByDesc('id')->value('hash'),
                'hash'       => hash('sha256', json_encode(['event'=>'login','user'=>$event->user->id ?? null]) . microtime(true)),
            ]);
        });

        Event::listen(Logout::class, function (Logout $event) {
            ActivityLog::create([
                'user_id'    => $event->user->id ?? null,
                'action'     => 'logout',
                'model_type' => 'auth',
                'model_id'   => null,
                'changes'    => null,
                'ip_address' => request()->ip() ?? null,
                'url'        => request()->fullUrl() ?? null,
                'user_agent' => request()->header('User-Agent') ?? null,
                'prev_hash'  => ActivityLog::query()->orderByDesc('id')->value('hash'),
                'hash'       => hash('sha256', json_encode(['event'=>'logout','user'=>$event->user->id ?? null]) . microtime(true)),
            ]);
        });

        Event::listen(Failed::class, function (Failed $event) {
            ActivityLog::create([
                'user_id'    => null,
                'action'     => 'login_failed',
                'model_type' => 'auth',
                'model_id'   => null,
                'changes'    => ['email' => $event->credentials['email'] ?? null],
                'ip_address' => request()->ip() ?? null,
                'url'        => request()->fullUrl() ?? null,
                'user_agent' => request()->header('User-Agent') ?? null,
                'prev_hash'  => ActivityLog::query()->orderByDesc('id')->value('hash'),
                'hash'       => hash('sha256', json_encode(['event'=>'login_failed','email'=>$event->credentials['email'] ?? null]) . microtime(true)),
            ]);
        });
    }
}
