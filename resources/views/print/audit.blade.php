<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - Printable</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji"; color:#111827; }
        h1 { font-size: 20px; margin: 0 0 10px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; vertical-align: top; }
        thead { background: #7c3aed; color: #fff; }
        .muted { color: #6b7280; }
        .footer { margin-top: 10px; font-size: 11px; color:#6b7280; }
    </style>
</head>
<body>
    <h1>Audit Logs</h1>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>User</th>
                <th>Action</th>
                <th>Model</th>
                <th>Model ID</th>
                <th>Changed Fields</th>
                <th>IP</th>
                <th>URL</th>
                <th>Hash</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($logs as $log)
                <?php $changed = implode(', ', array_keys(($log->changes['after'] ?? []) ?: [])); ?>
                <tr>
                    <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $log->user->name ?? 'System' }}</td>
                    <td>{{ ucfirst($log->action) }}</td>
                    <td>{{ class_basename($log->model_type) }}</td>
                    <td>{{ $log->model_id }}</td>
                    <td>{{ $changed }}</td>
                    <td>{{ $log->ip_address }}</td>
                    <td class="muted">{{ $log->url }}</td>
                    <td class="muted">{{ $log->hash }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="footer">
        Generated at {{ now()->format('Y-m-d H:i') }} — Chain integrity: each row includes a SHA-256 hash linked to the previous entry.
    </div>
    <script>
        window.addEventListener('load', () => window.print());
    </script>
</body>
</html>
