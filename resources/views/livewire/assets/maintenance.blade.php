<?php

use function Livewire\Volt\{state, mount, computed};
use App\Models\Asset;
use App\Models\AssetMaintenance;

state([
    'assetId' => null,
    'asset_id' => '',
    'type' => 'preventive',
    'scheduled_date' => '',
    'description' => '',
    'search' => '',
    'typeFilter' => '',
    'statusFilter' => '',
]);

mount(function ($id = null) {
    $this->assetId = $id;
    if ($id) {
        $this->asset_id = $id;
    }
    $this->scheduled_date = now()->addWeeks(2)->format('Y-m-d');
});

$assets = computed(function () {
    return Asset::active()
        ->with('category')
        ->orderBy('name')
        ->get();
});

$maintenanceRecords = computed(function () {
    $query = AssetMaintenance::with(['asset.category'])
        ->when($this->assetId, fn($q) => $q->where('asset_id', $this->assetId));
    
    if ($this->search) {
        $query->whereHas('asset', function ($q) {
            $q->where('asset_tag', 'like', '%' . $this->search . '%')
              ->orWhere('name', 'like', '%' . $this->search . '%');
        });
    }
    
    if ($this->typeFilter) {
        $query->where('type', $this->typeFilter);
    }
    
    if ($this->statusFilter) {
        $query->where('status', $this->statusFilter);
    }
    
    return $query->orderBy('scheduled_date', 'desc')->get();
});

$upcomingMaintenance = computed(function () {
    return AssetMaintenance::with(['asset.category'])
        ->upcoming()
        ->orderBy('scheduled_date', 'asc')
        ->get();
});

$overdueMaintenance = computed(function () {
    return AssetMaintenance::with(['asset.category'])
        ->overdue()
        ->orderBy('scheduled_date', 'asc')
        ->get();
});

$stats = computed(function () {
    return [
        'total_scheduled' => AssetMaintenance::scheduled()->count(),
        'overdue' => AssetMaintenance::overdue()->count(),
        'completed_this_month' => AssetMaintenance::completed()
            ->whereMonth('completed_date', now()->month)
            ->count(),
        'total_cost_this_month' => AssetMaintenance::completed()
            ->whereMonth('completed_date', now()->month)
            ->sum('cost'),
    ];
});

$schedule = function () {
    $validated = $this->validate([
        'asset_id' => 'required|exists:assets,id',
        'type' => 'required|in:preventive,corrective,inspection,upgrade',
        'scheduled_date' => 'required|date',
        'description' => 'required|string',
    ]);

    $validated['status'] = 'scheduled';
    
    AssetMaintenance::create($validated);
    
    session()->flash('success', 'Maintenance scheduled successfully.');
    
    $this->reset(['asset_id', 'type', 'description']);
    $this->scheduled_date = now()->addWeeks(2)->format('Y-m-d');
};

$markCompleted = function ($id) {
    $maintenance = AssetMaintenance::findOrFail($id);
    $maintenance->complete('Maintenance completed', null, null, null);
    
    session()->flash('success', 'Maintenance marked as completed.');
    $this->dispatch('$refresh');
};

$delete = function ($id) {
    $maintenance = AssetMaintenance::findOrFail($id);
    $maintenance->delete();
    
    session()->flash('success', 'Maintenance record deleted.');
    $this->dispatch('$refresh');
};

?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ $assetId ? route('assets.show', $assetId) : route('assets.index') }}" 
           class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
                Asset Maintenance
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Schedule and track asset maintenance activities</p>
        </div>
    </div>

    <!-- Stats -->
    @if(!$assetId)
        <div class="grid gap-6 md:grid-cols-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">Scheduled</p>
                        <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-2">
                            {{ $this->stats['total_scheduled'] }}
                        </p>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-xl">
                        <svg class="w-8 h-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">Overdue</p>
                        <p class="text-3xl font-bold text-red-600 dark:text-red-400 mt-2">
                            {{ $this->stats['overdue'] }}
                        </p>
                    </div>
                    <div class="p-3 bg-red-100 dark:bg-red-900 rounded-xl">
                        <svg class="w-8 h-8 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">Completed This Month</p>
                        <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">
                            {{ $this->stats['completed_this_month'] }}
                        </p>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-xl">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">Cost This Month</p>
                        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-2">
                            UGX {{ number_format($this->stats['total_cost_this_month'], 0) }}
                        </p>
                    </div>
                    <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-xl">
                        <svg class="w-8 h-8 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Schedule New Maintenance -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-6">
            <h2 class="text-xl font-bold text-white">Schedule Maintenance</h2>
        </div>
        
        <form wire:submit="schedule" class="p-6">
            <div class="grid gap-4 md:grid-cols-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Asset <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="asset_id"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Select Asset</option>
                        @foreach($this->assets as $asset)
                            <option value="{{ $asset->id }}">{{ $asset->asset_tag }} - {{ $asset->name }}</option>
                        @endforeach
                    </select>
                    @error('asset_id')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Type <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="type"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        <option value="preventive">Preventive</option>
                        <option value="corrective">Corrective</option>
                        <option value="inspection">Inspection</option>
                        <option value="upgrade">Upgrade</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Scheduled Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" 
                           wire:model="scheduled_date"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    @error('scheduled_date')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Description <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           wire:model="description"
                           placeholder="What needs to be done"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    @error('description')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <button type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl hover:shadow-xl transition-all duration-200 font-semibold">
                    Schedule Maintenance
                </button>
            </div>
        </form>
    </div>

    <!-- Overdue Maintenance Alert -->
    @if($this->overdueMaintenance->count() > 0 && !$assetId)
        <div class="bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-800 rounded-2xl p-6">
            <div class="flex items-start gap-4">
                <div class="p-2 bg-red-100 dark:bg-red-900 rounded-lg">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-red-900 dark:text-red-300">Overdue Maintenance</h3>
                    <p class="text-red-700 dark:text-red-400 mt-1">{{ $this->overdueMaintenance->count() }} maintenance task(s) are overdue</p>
                    <div class="mt-4 space-y-2">
                        @foreach($this->overdueMaintenance as $maintenance)
                            <div class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-lg p-3">
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $maintenance->asset->asset_tag }} - {{ $maintenance->asset->name }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $maintenance->description }}</p>
                                    <p class="text-xs text-red-600 dark:text-red-400 mt-1">Due: {{ $maintenance->scheduled_date->format('M d, Y') }}</p>
                                </div>
                                <button wire:click="markCompleted({{ $maintenance->id }})"
                                        class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors duration-200 text-sm font-semibold">
                                    Mark Complete
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Upcoming Maintenance -->
    @if($this->upcomingMaintenance->count() > 0 && !$assetId)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-6">
                <h2 class="text-xl font-bold text-white">Upcoming Maintenance (Next 30 Days)</h2>
            </div>
            
            <div class="p-6 space-y-3">
                @foreach($this->upcomingMaintenance as $maintenance)
                    <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $maintenance->asset->asset_tag }} - {{ $maintenance->asset->name }}</p>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 capitalize">
                                    {{ str_replace('_', ' ', $maintenance->type) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $maintenance->description }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Scheduled: {{ $maintenance->scheduled_date->format('M d, Y') }}</p>
                        </div>
                        <button wire:click="markCompleted({{ $maintenance->id }})"
                                class="ml-4 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors duration-200 text-sm font-semibold">
                            Mark Complete
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- All Maintenance Records -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-6">
            <h2 class="text-xl font-bold text-white">{{ $assetId ? 'Asset' : 'All' }} Maintenance Records</h2>
        </div>
        
        <!-- Filters -->
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <input type="text" 
                           wire:model.live.debounce.300ms="search"
                           placeholder="Search by asset tag or name..."
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <select wire:model.live="typeFilter"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        <option value="">All Types</option>
                        <option value="preventive">Preventive</option>
                        <option value="corrective">Corrective</option>
                        <option value="inspection">Inspection</option>
                        <option value="upgrade">Upgrade</option>
                    </select>
                </div>

                <div>
                    <select wire:model.live="statusFilter"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        <option value="">All Status</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
        </div>

        @if($this->maintenanceRecords->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Photo</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Asset</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Scheduled</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Cost</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($this->maintenanceRecords as $maintenance)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($maintenance->asset->photo_url)
                                        <img src="{{ $maintenance->asset->photo_url }}" 
                                             alt="{{ $maintenance->asset->name }}"
                                             class="w-12 h-12 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600">
                                    @else
                                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                            </svg>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <a href="{{ route('assets.show', $maintenance->asset->id) }}" 
                                           wire:navigate
                                           class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 hover:underline">
                                            {{ $maintenance->asset->asset_tag }}
                                        </a>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">{{ $maintenance->asset->name }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400 capitalize">
                                    {{ str_replace('_', ' ', $maintenance->type) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ Str::limit($maintenance->description, 40) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    {{ $maintenance->scheduled_date->format('M d, Y') }}
                                    @if($maintenance->is_overdue)
                                        <span class="ml-2 px-2 py-1 text-xs bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 rounded-full">Overdue</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @php
                                        $statusColors = [
                                            'scheduled' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                            'in_progress' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                            'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                        ];
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$maintenance->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($maintenance->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white font-semibold">
                                    {{ $maintenance->cost ? 'UGX ' . number_format($maintenance->cost, 0) : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($maintenance->status === 'scheduled')
                                            <button wire:click="markCompleted({{ $maintenance->id }})"
                                                    class="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900 rounded-lg transition-colors"
                                                    title="Mark Complete">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </button>
                                        @endif
                                        <button wire:click="delete({{ $maintenance->id }})"
                                                wire:confirm="Are you sure you want to delete this maintenance record?"
                                                class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900 rounded-lg transition-colors"
                                                title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <p class="mt-4 text-gray-600 dark:text-gray-400">No maintenance records found</p>
            </div>
        @endif
    </div>
</div>
