<?php

use App\Models\ActivityLog;
use App\Models\User;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Carbon;

new class extends Component {
    use WithPagination;

    public string $action = 'all';
    public string $model = 'all';
    public ?int $userId = null;
    public string $startDate = '';
    public string $endDate = '';

    public function updated($name): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = ActivityLog::with('user')
            ->when($this->action !== 'all', fn($q) => $q->where('action', $this->action))
            ->when($this->model !== 'all', fn($q) => $q->where('model_type', $this->model))
            ->when($this->userId, fn($q) => $q->where('user_id', $this->userId))
            ->when($this->startDate, fn($q) => $q->whereDate('created_at', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('created_at', '<=', $this->endDate))
            ->latest();

        $models = ActivityLog::query()->select('model_type')->distinct()->pluck('model_type')->filter()->values();
        $actions = ['created','updated','deleted','restored'];

        return [
            'logs' => $query->paginate(15),
            'users' => User::orderBy('name')->get(['id','name']),
            'models' => $models,
            'actions' => $actions,
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Audit Trail</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">All changes across programs, sales, expenses, accounts, and settings</p>
        </div>
        <a href="{{ route('settings.audit.verify') }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition-colors">Verify Chain</a>
        <div class="flex items-center gap-3">
            <a 
                href="{{ route('settings.audit.export.csv', ['action' => $action, 'model' => $model, 'userId' => $userId, 'startDate' => $startDate, 'endDate' => $endDate]) }}"
                class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition-colors">
                Export CSV
            </a>
            <a 
                href="{{ route('settings.audit.print', ['action' => $action, 'model' => $model, 'userId' => $userId, 'startDate' => $startDate, 'endDate' => $endDate]) }}"
                target="_blank"
                class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors">
                Export PDF (Print)
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Action</label>
                <select wire:model.live="action" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="all">All</option>
                    <option value="created">Created</option>
                    <option value="updated">Updated</option>
                    <option value="deleted">Deleted</option>
                    <option value="restored">Restored</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Model</label>
                <select wire:model.live="model" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="all">All</option>
                    @foreach ($models as $m)
                        <option value="{{ $m }}">{{ class_basename($m) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">User</label>
                <select wire:model.live="userId" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">All</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                <input type="date" wire:model.live="startDate" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                <input type="date" wire:model.live="endDate" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
            </div>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-purple-600">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Model</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Model ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Changed Fields</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">IP</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">URL</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($logs as $log)
                        <?php $changed = array_keys(($log->changes['after'] ?? []) ?: []); ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $log->user->name ?? 'System' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $log->action === 'created' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                    {{ $log->action === 'updated' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                    {{ $log->action === 'deleted' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}
                                    {{ $log->action === 'restored' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}">
                                    {{ ucfirst($log->action) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ class_basename($log->model_type) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $log->model_id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                @if (!empty($changed) && count($changed) > 0)
                                    {{ implode(', ', array_slice($changed, 0, 3)) }}@if(count($changed) > 3), +{{ count($changed) - 3 }} more @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $log->ip_address ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 max-w-xs truncate" title="{{ $log->url }}">{{ $log->url }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">No audit logs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30">
            {{ $logs->links() }}
        </div>
    </div>
</div>
