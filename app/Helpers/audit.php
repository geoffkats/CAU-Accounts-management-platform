<?php

use App\Models\ActivityLog;

if (!function_exists('audit')) {
    /**
     * Log an audit trail entry
     *
     * @param string $action The action performed (created, updated, deleted, approved, etc.)
     * @param \Illuminate\Database\Eloquent\Model $model The model being audited
     * @param array|null $old Old values (for updates)
     * @param array|null $new New values (for updates/creates)
     * @param string|null $description Optional description
     * @return ActivityLog
     */
    function audit(string $action, $model, ?array $old = null, ?array $new = null, ?string $description = null): ActivityLog
    {
        $changes = null;
        if ($old !== null || $new !== null) {
            $changes = [
                'before' => $old ?? [],
                'after' => $new ?? [],
            ];
        }

        // Get previous hash for blockchain
        $prevHash = ActivityLog::query()->orderByDesc('id')->value('hash');

        $payload = [
            'user_id'     => auth()->id(),
            'action'      => $action,
            'module'      => class_basename($model),
            'model_type'  => get_class($model),
            'model_id'    => $model->getKey(),
            'changes'     => $changes,
            'description' => $description,
            'ip_address'  => request()->ip(),
            'url'         => request()->fullUrl(),
            'user_agent'  => request()->userAgent(),
            'prev_hash'   => $prevHash,
        ];

        // Compute hash
        $hashBase = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $payload['hash'] = hash('sha256', $hashBase . microtime(true));

        return ActivityLog::create($payload);
    }
}

if (!function_exists('audit_financial')) {
    /**
     * Log a financial transaction audit with extra details
     *
     * @param string $action
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $financialDetails Additional financial context
     * @return ActivityLog
     */
    function audit_financial(string $action, $model, array $financialDetails = []): ActivityLog
    {
        $description = sprintf(
            '%s: %s',
            $action,
            json_encode($financialDetails)
        );

        return audit($action, $model, null, $financialDetails, $description);
    }
}
