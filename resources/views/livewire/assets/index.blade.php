<?php

use function Livewire\Volt\{state, computed};
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Program;
use Illuminate\Support\Facades\DB;

state([
    'search' => '',
    'category' => 'all',
    'program' => 'all',
    'status' => 'all',
]);

$assets = computed(function () {
    return Asset::with(['category', 'program', 'assignedToStaff'])
        ->when($this->search, function ($query) {
            $query->where(function ($q) {
                $q->where('asset_tag', 'like', '%' . $this->search . '%')
                  ->orWhere('name', 'like', '%' . $this->search . '%')
                  ->orWhere('serial_number', 'like', '%' . $this->search . '%')
                  ->orWhere('brand', 'like', '%' . $this->search . '%');
            });
        })
        ->when($this->category !== 'all', fn($query) => $query->where('asset_category_id', $this->category))
        ->when($this->program !== 'all', fn($query) => $query->where('program_id', $this->program))
        ->when($this->status !== 'all', fn($query) => $query->where('status', $this->status))
        ->latest()
        ->paginate(15);
});

$stats = computed(function () {
    return [
        'total_assets' => Asset::count(),
        'total_value' => Asset::sum('current_book_value'),
        'active_assets' => Asset::where('status', 'active')->count(),
        'maintenance_due' => Asset::whereHas('maintenanceRecords', function($q) {
            $q->where('status', 'scheduled')->where('scheduled_date', '<', now());
        })->count(),
    ];
});

$categories = computed(function () {
    return AssetCategory::active()->orderBy('name')->get();
});

$programs = computed(function () {
    return Program::orderBy('name')->get();
});

?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
                Asset Register
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Track and manage organizational assets</p>
        </div>
        <a href="{{ route('assets.create') }}" 
           class="px-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl hover:shadow-xl transition-all duration-200 font-semibold">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add Asset
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Total Assets Card -->
        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-200">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <div class="text-right">
                    <div class="text-sm font-medium text-indigo-100 uppercase tracking-wider">Total Assets</div>
                    <div class="text-4xl font-bold mt-1">{{ number_format($this->stats['total_assets']) }}</div>
                </div>
            </div>
            <div class="pt-4 border-t border-white/20">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-indigo-100">Inventory Count</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Value Card -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-200">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="text-right">
                    <div class="text-sm font-medium text-green-100 uppercase tracking-wider">Total Value</div>
                    <div class="text-3xl font-bold mt-1">{{ number_format($this->stats['total_value'] / 1000000, 1) }}M</div>
                    <div class="text-xs text-green-100 mt-1">UGX {{ number_format($this->stats['total_value']) }}</div>
                </div>
            </div>
            <div class="pt-4 border-t border-white/20">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-green-100">Current Book Value</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active Assets Card -->
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-200">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="text-right">
                    <div class="text-sm font-medium text-emerald-100 uppercase tracking-wider">Active Assets</div>
                    <div class="text-4xl font-bold mt-1">{{ number_format($this->stats['active_assets']) }}</div>
                </div>
            </div>
            <div class="pt-4 border-t border-white/20">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-emerald-100">In Operation</span>
                    <span class="font-semibold">{{ $this->stats['total_assets'] > 0 ? round(($this->stats['active_assets'] / $this->stats['total_assets']) * 100) : 0 }}%</span>
                </div>
            </div>
        </div>

        <!-- Maintenance Due Card -->
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all duration-200">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="text-right">
                    <div class="text-sm font-medium text-orange-100 uppercase tracking-wider">Maintenance Due</div>
                    <div class="text-4xl font-bold mt-1">{{ number_format($this->stats['maintenance_due']) }}</div>
                </div>
            </div>
            <div class="pt-4 border-t border-white/20">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-orange-100">Requires Attention</span>
                    @if($this->stats['maintenance_due'] > 0)
                        <a href="{{ route('maintenance.index') }}" 
                           wire:navigate
                           class="flex items-center gap-1 font-semibold hover:underline">
                            View
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    @else
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Main Card -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-6">
            <h2 class="text-xl font-bold text-white">All Assets</h2>
        </div>

        <div class="p-6 space-y-6">
            <!-- Filters -->
            <div class="grid gap-4 md:grid-cols-4">
                <div>
                    <input type="text"
                           wire:model.live.debounce.300ms="search" 
                           placeholder="Search assets..."
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                </div>
                
                <div>
                    <select wire:model.live="category"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        <option value="all">All Categories</option>
                        @foreach($this->categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <select wire:model.live="program"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        <option value="all">All Programs</option>
                        @foreach($this->programs as $prog)
                            <option value="{{ $prog->id }}">{{ $prog->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <select wire:model.live="status"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        <option value="all">All Status</option>
                        <option value="active">Active</option>
                        <option value="in_maintenance">In Maintenance</option>
                        <option value="retired">Retired</option>
                        <option value="disposed">Disposed</option>
                    </select>
                </div>
            </div>

            <!-- Assets Table -->
            <div class="overflow-x-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @forelse($this->assets as $asset)
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-2xl transition-shadow duration-300">
                            <!-- Asset Photo -->
                            <div class="h-48 bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/30 dark:to-purple-900/30 relative overflow-hidden">
                                @if($asset->photo_path)
                                    <img src="{{ $asset->photo_url }}" alt="{{ $asset->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-20 h-20 text-indigo-300 dark:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                @endif
                                <!-- Asset Tag Badge -->
                                <div class="absolute top-3 left-3">
                                    <span class="px-3 py-1 bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm rounded-full text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400 shadow-lg">
                                        {{ $asset->asset_tag }}
                                    </span>
                                </div>
                                <!-- Status Badge -->
                                <div class="absolute top-3 right-3">
                                    @php
                                        $statusColors = [
                                            'active' => 'bg-green-500 text-white',
                                            'in_maintenance' => 'bg-yellow-500 text-white',
                                            'retired' => 'bg-gray-500 text-white',
                                            'disposed' => 'bg-red-500 text-white',
                                        ];
                                    @endphp
                                    <span class="px-3 py-1 {{ $statusColors[$asset->status] ?? 'bg-gray-500 text-white' }} rounded-full text-xs font-semibold shadow-lg backdrop-blur-sm">
                                        {{ ucfirst(str_replace('_', ' ', $asset->status)) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Asset Info -->
                            <div class="p-5">
                                <a href="{{ route('assets.show', $asset->id) }}" wire:navigate>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                        {{ $asset->name }}
                                    </h3>
                                </a>
                                @if($asset->brand || $asset->model)
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $asset->brand }} {{ $asset->model }}
                                    </p>
                                @endif
                                
                                <div class="mt-4 space-y-2">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Category:</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $asset->category->name }}</span>
                                    </div>
                                    @if($asset->program)
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">Program:</span>
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $asset->program->name }}</span>
                                        </div>
                                    @endif
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Purchase Price:</span>
                                        <span class="font-bold text-indigo-600 dark:text-indigo-400">UGX {{ number_format($asset->purchase_price) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Book Value:</span>
                                        <span class="font-bold text-green-600 dark:text-green-400">UGX {{ number_format($asset->current_book_value) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">Assigned To:</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ Str::limit($asset->assigned_to_name, 20) }}</span>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-2 mt-5 pt-5 border-t border-gray-200 dark:border-gray-700">
                                    <a href="{{ route('assets.show', $asset->id) }}"
                                       wire:navigate
                                       class="flex-1 px-4 py-2 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/20 dark:hover:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 rounded-lg transition-colors text-center text-sm font-semibold">
                                        View Details
                                    </a>
                                    <a href="{{ route('assets.edit', $asset->id) }}"
                                       wire:navigate
                                       class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                       title="Edit">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center py-12">
                            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400 font-medium">No assets found. Add your first asset to get started.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $this->assets->links() }}
            </div>
        </div>
    </div>
</div>
