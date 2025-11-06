<?php

use function Livewire\Volt\{state, mount, computed};
use App\Models\Asset;
use App\Models\AssetMaintenance;

state(['assetId']);

mount(function ($id) {
    $this->assetId = $id;
});

$asset = computed(function () {
    return Asset::with(['assignedToStaff', 'maintenanceRecords', 'assignments'])
        ->findOrFail($this->assetId);
});

$maintenanceRecords = computed(function () {
    return $this->asset->maintenanceRecords()
        ->orderBy('scheduled_date', 'desc')
        ->get();
});

$assignmentHistory = computed(function () {
    return $this->asset->assignments()
        ->orderBy('assigned_date', 'desc')
        ->get();
});

$depreciationSchedule = computed(function () {
    $asset = $this->asset;
    $schedule = [];
    $yearsToCalculate = min($asset->useful_life_years, 10);
    
    for ($year = 0; $year <= $yearsToCalculate; $year++) {
        if ($asset->depreciation_method === 'straight_line') {
            $yearDepreciation = Asset::calculateStraightLineDepreciation(
                $asset->purchase_price,
                $asset->salvage_value,
                $asset->useful_life_years,
                $year
            );
        } else {
            $yearDepreciation = Asset::calculateDecliningBalanceDepreciation(
                $asset->purchase_price,
                $asset->salvage_value,
                $asset->depreciation_rate,
                $year
            );
        }
        
        $bookValue = $asset->purchase_price - $yearDepreciation;
        
        $schedule[] = [
            'year' => $year,
            'date' => $asset->purchase_date->copy()->addYears($year)->format('Y'),
            'accumulated_depreciation' => $yearDepreciation,
            'book_value' => max($bookValue, $asset->salvage_value),
        ];
    }
    
    return $schedule;
});

$updateDepreciation = function () {
    $asset = Asset::findOrFail($this->assetId);
    $asset->updateDepreciation();
    
    session()->flash('success', 'Depreciation updated successfully.');
    $this->dispatch('$refresh');
};

$delete = function () {
    $asset = Asset::findOrFail($this->assetId);
    $asset->delete();
    
    session()->flash('success', 'Asset deleted successfully.');
    $this->redirect(route('assets.index'), navigate: true);
};

?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-start justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('assets.index') }}" 
               class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
                        {{ $this->asset->asset_tag }}
                    </h1>
                    @php
                        $statusColors = [
                            'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                            'in_maintenance' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                            'retired' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                            'disposed' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                            'lost' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
                            'stolen' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                        ];
                    @endphp
                    <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $statusColors[$this->asset->status] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ ucfirst(str_replace('_', ' ', $this->asset->status)) }}
                    </span>
                </div>
                <p class="text-xl text-gray-600 dark:text-gray-400 mt-1">{{ $this->asset->name }}</p>
                @if($this->asset->brand || $this->asset->model)
                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                        {{ $this->asset->brand }} {{ $this->asset->model }}
                    </p>
                @endif
            </div>
        </div>
        
        <div class="flex items-center gap-2">
            <button wire:click="updateDepreciation"
                    class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors duration-200 text-sm font-semibold">
                Update Depreciation
            </button>
            <a href="{{ route('assets.edit', $this->asset->id) }}"
               class="px-4 py-2 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 transition-colors duration-200 text-sm font-semibold">
                Edit Asset
            </a>
            <button wire:click="delete" 
                    wire:confirm="Are you sure you want to delete this asset?"
                    class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors duration-200 text-sm font-semibold">
                Delete
            </button>
        </div>
    </div>

    <!-- Financial Summary -->
    <div class="grid gap-6 md:grid-cols-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">Purchase Price</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                        UGX {{ number_format($this->asset->purchase_price, 0) }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">{{ $this->asset->purchase_date->format('M d, Y') }}</p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-xl">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">Accumulated Depreciation</p>
                    <p class="text-2xl font-bold text-orange-600 dark:text-orange-400 mt-2">
                        UGX {{ number_format($this->asset->accumulated_depreciation, 0) }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">{{ number_format($this->asset->depreciation_percentage, 1) }}% depreciated</p>
                </div>
                <div class="p-3 bg-orange-100 dark:bg-orange-900 rounded-xl">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">Current Book Value</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-2">
                        UGX {{ number_format($this->asset->current_book_value, 0) }}
                    </p>
                    @if($this->asset->is_fully_depreciated)
                        <p class="text-xs text-red-600 dark:text-red-400 mt-1">Fully Depreciated</p>
                    @endif
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-xl">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">Total Cost of Ownership</p>
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-2">
                        UGX {{ number_format($this->asset->total_cost_of_ownership, 0) }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">Purchase + Maintenance</p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-xl">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Asset Details -->
    <div class="grid gap-6 md:grid-cols-3">
        <!-- Asset Photo -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-6">
                <h2 class="text-xl font-bold text-white">Asset Photo</h2>
            </div>
            
            <div class="p-6">
                <div class="aspect-square w-full rounded-xl overflow-hidden bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/30 dark:to-purple-900/30">
                    @if($this->asset->photo_path)
                        <img src="{{ $this->asset->photo_url }}" alt="{{ $this->asset->name }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <div class="text-center">
                                <svg class="w-24 h-24 text-indigo-300 dark:text-indigo-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <p class="text-sm text-gray-500 dark:text-gray-400">No photo available</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Basic Information -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden md:col-span-2">
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-6">
                <h2 class="text-xl font-bold text-white">Asset Details</h2>
            </div>
            
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">Category</p>
                        <p class="text-gray-900 dark:text-white mt-1">{{ $this->asset->category_label }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">Funding Source</p>
                        <p class="text-gray-900 dark:text-white mt-1">{{ $this->asset->funding_source_label }}</p>
                    </div>
                </div>
                
                @if($this->asset->serial_number)
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">Serial Number</p>
                        <p class="text-gray-900 dark:text-white mt-1 font-mono">{{ $this->asset->serial_number }}</p>
                    </div>
                @endif
                
                @if($this->asset->location)
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">Location</p>
                        <p class="text-gray-900 dark:text-white mt-1">{{ $this->asset->location }}</p>
                    </div>
                @endif
                
                @if($this->asset->assigned_to_name)
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">Currently Assigned To</p>
                        <p class="text-gray-900 dark:text-white mt-1">{{ $this->asset->assigned_to_name }}</p>
                        @if($this->asset->assigned_date)
                            <p class="text-xs text-gray-500 mt-1">Since {{ $this->asset->assigned_date->format('M d, Y') }}</p>
                        @endif
                    </div>
                @endif
                
                @if($this->asset->warranty_expiry)
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">Warranty</p>
                        <p class="text-gray-900 dark:text-white mt-1">
                            Expires: {{ $this->asset->warranty_expiry->format('M d, Y') }}
                            @if($this->asset->is_under_warranty)
                                <span class="ml-2 px-2 py-1 text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 rounded-full">Active</span>
                            @else
                                <span class="ml-2 px-2 py-1 text-xs bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 rounded-full">Expired</span>
                            @endif
                        </p>
                    </div>
                @endif
                
                @if($this->asset->description)
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">Description</p>
                        <p class="text-gray-900 dark:text-white mt-1">{{ $this->asset->description }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Depreciation Info -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-6">
                <h2 class="text-xl font-bold text-white">Depreciation Information</h2>
            </div>
            
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">Method</p>
                        <p class="text-gray-900 dark:text-white mt-1">{{ ucfirst(str_replace('_', ' ', $this->asset->depreciation_method)) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">Rate</p>
                        <p class="text-gray-900 dark:text-white mt-1">{{ $this->asset->depreciation_rate }}%</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">Useful Life</p>
                        <p class="text-gray-900 dark:text-white mt-1">{{ $this->asset->useful_life_years }} years</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">Salvage Value</p>
                        <p class="text-gray-900 dark:text-white mt-1">UGX {{ number_format($this->asset->salvage_value, 0) }}</p>
                    </div>
                </div>
                
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold">Monthly Depreciation</p>
                    <p class="text-gray-900 dark:text-white mt-1">UGX {{ number_format($this->asset->calculateMonthlyDepreciation(), 0) }}</p>
                </div>
                
                @if($this->asset->supplier || $this->asset->invoice_number)
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-semibold mb-2">Purchase Details</p>
                        @if($this->asset->supplier)
                            <p class="text-sm text-gray-900 dark:text-white">Supplier: {{ $this->asset->supplier }}</p>
                        @endif
                        @if($this->asset->invoice_number)
                            <p class="text-sm text-gray-900 dark:text-white mt-1">Invoice: {{ $this->asset->invoice_number }}</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Depreciation Schedule -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-6">
            <h2 class="text-xl font-bold text-white">Depreciation Schedule</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Year</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Accumulated Depreciation</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Book Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($this->depreciationSchedule as $entry)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                Year {{ $entry['year'] }}
                                @if($entry['year'] == floor($this->asset->purchase_date->diffInYears(now())))
                                    <span class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 rounded-full">Current</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                {{ $entry['date'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-orange-600 dark:text-orange-400 font-semibold">
                                UGX {{ number_format($entry['accumulated_depreciation'], 0) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 dark:text-green-400 font-semibold">
                                UGX {{ number_format($entry['book_value'], 0) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Maintenance History -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-6 flex items-center justify-between">
            <h2 class="text-xl font-bold text-white">Maintenance History</h2>
            <a href="{{ route('assets.maintenance', $this->asset->id) }}"
               class="px-4 py-2 bg-white text-indigo-600 rounded-lg hover:bg-gray-100 transition-colors duration-200 text-sm font-semibold">
                Schedule Maintenance
            </a>
        </div>
        
        @if($this->maintenanceRecords->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Cost</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($this->maintenanceRecords as $maintenance)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $maintenance->scheduled_date->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400 capitalize">
                                    {{ str_replace('_', ' ', $maintenance->type) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ Str::limit($maintenance->description, 50) }}
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
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-sm font-semibold text-gray-700 dark:text-gray-300 text-right">
                                Total Maintenance Cost:
                            </td>
                            <td class="px-6 py-4 text-sm font-bold text-gray-900 dark:text-white text-right">
                                UGX {{ number_format($this->asset->total_maintenance_cost, 0) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <p class="mt-4 text-gray-600 dark:text-gray-400">No maintenance records yet</p>
                <a href="{{ route('assets.maintenance', $this->asset->id) }}"
                   class="mt-4 inline-block px-4 py-2 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 transition-colors duration-200 text-sm font-semibold">
                    Schedule First Maintenance
                </a>
            </div>
        @endif
    </div>

    <!-- Assignment History -->
    @if($this->assignmentHistory->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-6">
                <h2 class="text-xl font-bold text-white">Assignment History</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Assigned To</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Assigned Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Return Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Days</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Condition</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($this->assignmentHistory as $assignment)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $assignment->assigned_to_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    {{ $assignment->assigned_date->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    {{ $assignment->return_date?->format('M d, Y') ?? 'Not returned' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    {{ $assignment->days_assigned }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @php
                                        $statusColors = [
                                            'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                            'returned' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                            'overdue' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                        ];
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$assignment->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($assignment->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400 capitalize">
                                    {{ $assignment->condition_on_return ? str_replace('_', ' ', $assignment->condition_on_return) : 'N/A' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
