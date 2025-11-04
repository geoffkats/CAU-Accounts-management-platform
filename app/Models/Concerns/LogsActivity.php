<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        $events = ['created', 'updated', 'deleted'];
        
        // Only register 'restored' event if model uses SoftDeletes
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(static::class))) {
            $events[] = 'restored';
        }
        
        foreach ($events as $event) {
            static::$event(function ($model) use ($event) {
                // Prevent recursion and unnecessary logs
                if ($model instanceof ActivityLog) {
                    return;
                }

                // Skip if only timestamps changed on update
                $changes = null;
                if ($event === 'updated') {
                    $dirty = $model->getDirty();
                    unset($dirty['updated_at']);
                    if (empty($dirty)) {
                        return; // nothing meaningful changed
                    }
                    $before = [];
                    $after = [];
                    $hidden = array_flip(array_merge(
                        method_exists($model, 'getHidden') ? $model->getHidden() : [],
                        ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes']
                    ));

                    foreach ($dirty as $key => $value) {
                        if (isset($hidden[$key])) {
                            continue;
                        }
                        $before[$key] = $model->getOriginal($key);
                        $after[$key] = $value;
                    }
                    $changes = ['before' => $before, 'after' => $after];
                }

                // Request context (guarded for CLI/queue)
                $user = Auth::user();
                $ip = null; $url = null; $agent = null;
                try {
                    if (function_exists('request')) {
                        $ip = request()->ip();
                        $url = request()->fullUrl();
                        $agent = request()->header('User-Agent');
                    }
                } catch (\Throwable $e) {
                    // ignore missing request
                }

                // Build hash chain
                $prevHash = ActivityLog::query()->orderByDesc('id')->value('hash');
                $payload = [
                    'user_id'    => $user?->id,
                    'action'     => $event,
                    'model_type' => get_class($model),
                    'model_id'   => $model->getKey(),
                    'changes'    => $changes,
                    'ip_address' => $ip,
                    'url'        => $url,
                    'user_agent' => $agent,
                    'prev_hash'  => $prevHash,
                ];

                // Compute hash over payload + timestamp approximation
                $hashBase = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $payload['hash'] = hash('sha256', $hashBase . microtime(true));

                ActivityLog::create($payload);
            });
        }
    }
}
