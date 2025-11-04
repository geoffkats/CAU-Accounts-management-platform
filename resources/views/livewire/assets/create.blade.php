<?php

use function Livewire\Volt\{state, mount, computed};
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Program;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Livewire\WithFileUploads;
use Livewire\Volt\Component;

new class extends Component {
    use WithFileUploads;

    public ?int $assetId = null;
    public $asset_category_id = '';
    public $program_id = '';
    public $asset_tag = '';
    public $name = '';
    public $description = '';
    public $photo = null;
    public $brand = '';
    public $model = '';
    public $serial_number = '';
    public $purchase_price = '';
    public $purchase_date = '';
    public $supplier = '';
    public $invoice_number = '';
    public $salvage_value = 0;
    public $depreciation_rate = '';
    public $depreciation_method = 'straight_line';
    public $useful_life_years = '';
    public $status = 'active';
    public $location = '';
    public $notes = '';
    public $warranty_expiry = '';
    public $assigned_to_staff_id = '';
    public $assigned_to_student = '';

    public function mount($id = null)
    {
        if ($id) {
            $this->assetId = $id;
            $asset = Asset::findOrFail($id);
            
            $this->asset_category_id = $asset->asset_category_id;
            $this->program_id = $asset->program_id;
            $this->asset_tag = $asset->asset_tag;
            $this->name = $asset->name;
            $this->description = $asset->description;
            $this->brand = $asset->brand;
            $this->model = $asset->model;
            $this->serial_number = $asset->serial_number;
            $this->purchase_price = $asset->purchase_price;
            $this->purchase_date = $asset->purchase_date->format('Y-m-d');
            $this->supplier = $asset->supplier;
            $this->invoice_number = $asset->invoice_number;
            $this->salvage_value = $asset->salvage_value;
            $this->depreciation_rate = $asset->depreciation_rate;
            $this->depreciation_method = $asset->depreciation_method;
            $this->useful_life_years = $asset->useful_life_years;
            $this->status = $asset->status;
            $this->location = $asset->location;
            $this->notes = $asset->notes;
            $this->warranty_expiry = $asset->warranty_expiry?->format('Y-m-d');
            $this->assigned_to_staff_id = $asset->assigned_to_staff_id;
            $this->assigned_to_student = $asset->assigned_to_student;
        } else {
            $this->purchase_date = now()->format('Y-m-d');
            $this->asset_tag = 'AST-' . str_pad(Asset::count() + 1, 4, '0', STR_PAD_LEFT);
        }
    }

    public function getCategoriesProperty()
    {
        return AssetCategory::active()->orderBy('name')->get();
    }

    public function getProgramsProperty()
    {
        return Program::orderBy('name')->get();
    }

    public function getStaffProperty()
    {
        return Staff::active()->orderBy('last_name')->get();
    }

    public function onCategoryChange()
    {
        if ($this->asset_category_id) {
            $category = AssetCategory::find($this->asset_category_id);
            if ($category) {
                $this->depreciation_rate = $category->default_depreciation_rate;
                $this->depreciation_method = $category->depreciation_method;
                $this->useful_life_years = $category->default_useful_life_years;
            }
        }
    }

    public function save()
    {
        $validated = $this->validate([
        'asset_category_id' => 'required|exists:asset_categories,id',
        'program_id' => 'nullable|exists:programs,id',
        'asset_tag' => 'required|string|max:50|unique:assets,asset_tag,' . $this->assetId,
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'photo' => 'nullable|image|max:5120',
        'brand' => 'nullable|string|max:100',
        'model' => 'nullable|string|max:100',
        'serial_number' => 'nullable|string|max:100',
        'purchase_price' => 'required|numeric|min:0',
        'purchase_date' => 'required|date',
        'supplier' => 'nullable|string|max:255',
        'invoice_number' => 'nullable|string|max:100',
        'salvage_value' => 'nullable|numeric|min:0',
        'depreciation_rate' => 'required|numeric|min:0|max:100',
        'depreciation_method' => 'required|in:straight_line,declining_balance',
        'useful_life_years' => 'required|integer|min:1',
        'status' => 'required|in:active,in_maintenance,retired,disposed',
        'location' => 'nullable|string|max:255',
        'notes' => 'nullable|string',
        'warranty_expiry' => 'nullable|date',
        'assigned_to_staff_id' => 'nullable|exists:staff,id',
        'assigned_to_student' => 'nullable|string|max:255',
    ]);

    // Convert empty strings to null for nullable integer fields
    if (empty($validated['assigned_to_staff_id'])) {
        $validated['assigned_to_staff_id'] = null;
    }
    if (empty($validated['assigned_to_student'])) {
        $validated['assigned_to_student'] = null;
    }
    if (empty($validated['program_id'])) {
        $validated['program_id'] = null;
    }
    if (empty($validated['asset_category_id'])) {
        $validated['asset_category_id'] = null;
    }
    
    // Calculate initial book value
    $validated['current_book_value'] = $validated['purchase_price'];
    $validated['accumulated_depreciation'] = 0;

    // Handle photo upload
    if ($this->photo) {
        $validated['photo_path'] = $this->photo->store('assets', 'public');
    }

    if ($this->assetId) {
        $asset = Asset::findOrFail($this->assetId);
        $asset->update($validated);
        $asset->updateDepreciation();
        
        session()->flash('success', 'Asset updated successfully.');
    } else {
        $asset = Asset::create($validated);
        session()->flash('success', 'Asset created successfully.');
    }

    $this->redirect(route('assets.show', $asset->id), navigate: true);
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('assets.index') }}" 
           class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
                {{ $assetId ? 'Edit Asset' : 'Add New Asset' }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $assetId ? 'Update asset information' : 'Register a new organizational asset' }}</p>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        <!-- Basic Information -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-6">
                <h2 class="text-xl font-bold text-white">Basic Information</h2>
            </div>
            
            <div class="p-6 space-y-4">
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Asset Tag <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               wire:model="asset_tag"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white font-mono">
                        @error('asset_tag')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Asset Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               wire:model="name"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Category <span class="text-red-500">*</span>
                        </label>
                        <select wire:model.live="asset_category_id"
                                wire:change="onCategoryChange"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Select Category</option>
                            @foreach($this->categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('asset_category_id')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Program
                        </label>
                        <select wire:model="program_id"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                            <option value="">No Program</option>
                            @foreach($this->programs as $program)
                                <option value="{{ $program->id }}">{{ $program->name }}</option>
                            @endforeach
                        </select>
                        @error('program_id')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Brand
                        </label>
                        <input type="text" 
                               wire:model="brand"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Model
                        </label>
                        <input type="text" 
                               wire:model="model"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Serial Number
                        </label>
                        <input type="text" 
                               wire:model="serial_number"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white font-mono">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Description
                    </label>
                    <textarea wire:model="description" 
                              rows="3"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white"></textarea>
                </div>

                <!-- Asset Photo -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Asset Photo
                    </label>
                    <input type="file" 
                           wire:model="photo"
                           accept="image/*"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/20 dark:file:text-indigo-400">
                    @error('photo')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Max size: 5MB. Formats: JPG, PNG, GIF, WEBP
                    </p>
                    @if ($photo)
                        <div class="mt-3 relative inline-block">
                            <img src="{{ $photo->temporaryUrl() }}" class="w-32 h-32 object-cover rounded-lg border-2 border-indigo-200 dark:border-indigo-700" alt="Preview">
                            <button type="button" wire:click="$set('photo', null)" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Financial Information -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-6">
                <h2 class="text-xl font-bold text-white">Financial & Purchase Details</h2>
            </div>
            
            <div class="p-6 space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Purchase Price (UGX) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               step="0.01"
                               wire:model="purchase_price"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        @error('purchase_price')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Purchase Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               wire:model="purchase_date"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        @error('purchase_date')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Supplier
                        </label>
                        <input type="text" 
                               wire:model="supplier"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Invoice Number
                        </label>
                        <input type="text" 
                               wire:model="invoice_number"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Salvage Value (UGX)
                        </label>
                        <input type="number" 
                               step="0.01"
                               wire:model="salvage_value"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Warranty Expiry
                        </label>
                        <input type="date" 
                               wire:model="warranty_expiry"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
            </div>
        </div>

        <!-- Depreciation Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-6">
                <h2 class="text-xl font-bold text-white">Depreciation Settings</h2>
            </div>
            
            <div class="p-6 space-y-4">
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Depreciation Method <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="depreciation_method"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                            <option value="straight_line">Straight Line</option>
                            <option value="declining_balance">Declining Balance</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Depreciation Rate (%) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               step="0.01"
                               wire:model="depreciation_rate"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        @error('depreciation_rate')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Useful Life (Years) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               wire:model="useful_life_years"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        @error('useful_life_years')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Status & Assignment -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-6">
                <h2 class="text-xl font-bold text-white">Status & Assignment</h2>
            </div>
            
            <div class="p-6 space-y-4">
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Status <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="status"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                            <option value="active">Active</option>
                            <option value="in_maintenance">In Maintenance</option>
                            <option value="retired">Retired</option>
                            <option value="disposed">Disposed</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Location
                        </label>
                        <input type="text" 
                               wire:model="location"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Assigned to Staff
                        </label>
                        <select wire:model="assigned_to_staff_id"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Not Assigned</option>
                            @foreach($this->staff as $member)
                                <option value="{{ $member->id }}">{{ $member->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Assigned to Student
                    </label>
                    <input type="text" 
                           wire:model="assigned_to_student"
                           placeholder="Student name or ID"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Notes
                    </label>
                    <textarea wire:model="notes" 
                              rows="3"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white"></textarea>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('assets.index') }}"
               class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-200 font-semibold">
                Cancel
            </a>
            <button type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl hover:shadow-xl transition-all duration-200 font-semibold">
                {{ $assetId ? 'Update Asset' : 'Create Asset' }}
            </button>
        </div>
    </form>
</div>
