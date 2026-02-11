<?php

use App\Models\Scholarship;
use Livewire\Volt\Component;

new class extends Component {
    public $scholarshipId = null;
    public $name = '';
    public $type = 'partial';
    public $amount = '';
    public $percentage = '';
    public $sponsor = '';
    public $description = '';
    public $start_date = '';
    public $end_date = '';
    public $is_active = true;

    public function mount($id = null)
    {
        if ($id) {
            $this->scholarshipId = $id;
            $scholarship = Scholarship::findOrFail($id);
            
            $this->name = $scholarship->name;
            $this->type = $scholarship->type;
            $this->amount = $scholarship->amount;
            $this->percentage = $scholarship->percentage;
            $this->sponsor = $scholarship->sponsor;
            $this->description = $scholarship->description;
            $this->start_date = $scholarship->start_date?->format('Y-m-d');
            $this->end_date = $scholarship->end_date?->format('Y-m-d');
            $this->is_active = $scholarship->is_active;
        }
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|in:full,partial,percentage',
            'sponsor' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'is_active' => 'boolean',
        ];

        if ($this->type === 'partial') {
            $rules['amount'] = 'required|numeric|min:0';
        } elseif ($this->type === 'percentage') {
            $rules['percentage'] = 'required|numeric|min:0|max:100';
        }

        $validated = $this->validate($rules);

        if ($this->type === 'full') {
            $validated['amount'] = null;
            $validated['percentage'] = null;
        } elseif ($this->type === 'partial') {
            $validated['percentage'] = null;
        } else {
            $validated['amount'] = null;
        }

        if ($this->scholarshipId) {
            $scholarship = Scholarship::findOrFail($this->scholarshipId);
            $scholarship->update($validated);
            session()->flash('message', 'Scholarship updated successfully.');
        } else {
            Scholarship::create($validated);
            session()->flash('message', 'Scholarship created successfully.');
        }

        return redirect()->route('scholarships.index');
    }

    public function with(): array
    {
        return [
            'isEdit' => $this->scholarshipId !== null,
        ];
    }
}; ?>

<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ $isEdit ? 'Edit Scholarship' : 'New Scholarship' }}
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ $isEdit ? 'Update scholarship details' : 'Create a new scholarship program' }}
                </p>
            </div>
            <a href="{{ route('scholarships.index') }}" 
               class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                Cancel
            </a>
        </div>

        <form wire:submit="save" class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Scholarship Details</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Scholarship Name *</label>
                        <input type="text" 
                               wire:model="name"
                               placeholder="e.g., Merit Scholarship, Need-Based Aid"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('name') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type *</label>
                        <select wire:model.live="type"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="full">Full Scholarship (100%)</option>
                            <option value="partial">Partial (Fixed Amount)</option>
                            <option value="percentage">Percentage-Based</option>
                        </select>
                        @error('type') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>

                    @if($type === 'partial')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount *</label>
                            <input type="number" 
                                   wire:model="amount"
                                   step="0.01"
                                   placeholder="Fixed amount"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            @error('amount') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>
                    @elseif($type === 'percentage')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Percentage *</label>
                            <input type="number" 
                                   wire:model="percentage"
                                   step="0.01"
                                   min="0"
                                   max="100"
                                   placeholder="e.g., 50 for 50%"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            @error('percentage') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sponsor</label>
                        <input type="text" 
                               wire:model="sponsor"
                               placeholder="Organization or individual sponsor"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('sponsor') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea wire:model="description"
                                  rows="3"
                                  placeholder="Scholarship criteria and details"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"></textarea>
                        @error('description') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                            <input type="date" 
                                   wire:model="start_date"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            @error('start_date') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                            <input type="date" 
                                   wire:model="end_date"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            @error('end_date') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   wire:model="is_active"
                                   class="rounded border-gray-300 text-blue-600 shadow-sm">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-4">
                <a href="{{ route('scholarships.index') }}" 
                   class="px-6 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    {{ $isEdit ? 'Update Scholarship' : 'Create Scholarship' }}
                </button>
            </div>
        </form>
</div>
