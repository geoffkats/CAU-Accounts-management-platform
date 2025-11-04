<?php

use App\Models\ActivityLog;
use Livewire\Volt\Component;

new class extends Component {
    public function with(): array
    {
        $logs = ActivityLog::orderBy('id')->get(['id','hash','prev_hash','created_at','action','model_type','model_id']);
        $breaks = [];
        $lastHash = null;
        foreach ($logs as $log) {
            $expectedPrev = $lastHash;
            $isOk = $log->prev_hash === $expectedPrev;
            if (!$isOk && !is_null($expectedPrev)) {
                $breaks[] = $log->id;
            }
            $lastHash = $log->hash;
        }
        return [
            'logs' => $logs,
            'breaks' => $breaks,
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Verify Audit Chain</h1>
            <p class="text-gray-600 dark:text-gray-400">Checks continuity of the hash chain (prev_hash → hash).</p>
        </div>
        <a href="{{ route('settings.audit') }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300">Back to Audit</a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        @php $ok = count($breaks) === 0; @endphp
        <div class="flex items-center gap-3">
            <div class="w-3 h-3 rounded-full {{ $ok ? 'bg-green-500' : 'bg-red-500' }}"></div>
            <div class="text-gray-900 dark:text-white font-medium">
                Chain Status: {{ $ok ? 'INTACT' : 'BROKEN' }}
            </div>
        </div>
        @if(!$ok)
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">Breaks detected near log IDs: {{ implode(', ', $breaks) }}</p>
        @else
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">No breaks found.</p>
        @endif
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-purple-600">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Model</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Model ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Prev Hash</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Hash</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @php $lastHash = null; @endphp
                    @foreach ($logs as $log)
                        @php $ok = $log->prev_hash === $lastHash; @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 {{ $ok ? '' : 'bg-red-50/50 dark:bg-red-900/20' }}">
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $log->id }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $log->action }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ class_basename($log->model_type) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $log->model_id }}</td>
                            <td class="px-6 py-4 text-xs text-gray-600 dark:text-gray-300 break-all">{{ $log->prev_hash ?? '—' }}</td>
                            <td class="px-6 py-4 text-xs text-gray-600 dark:text-gray-300 break-all">{{ $log->hash ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $ok ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                                    {{ $ok ? 'OK' : 'BREAK' }}
                                </span>
                            </td>
                        </tr>
                        @php $lastHash = $log->hash; @endphp
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
