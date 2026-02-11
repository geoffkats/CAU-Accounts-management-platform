<?php

use App\Models\FeeStructure;
use App\Models\Program;
use Livewire\Volt\Component;

new class extends Component {
    public $feeId = null;
    public $program_id = '';
    public $name = '';
    public $term = 'Term 1';
    public $academic_year = '';
    public $amount = '';
    public $currency = 'UGX';
    public $is_mandatory = true;
    public $description = '';
    public $due_date = '';
    public $is_active = true;

    public function mount($id = null)
    {
        $this->academic_year = now()->year;
        
        if ($id) {
            $this->feeId = $id;
            $fee = FeeStructure::findOrFail($id);
            
            $this->program_id = $fee->program_id;
            $this->name = $fee->name;
            $this->term = $fee->term;
            $this->academic_year = $fee->academic_year;
            $this->amount = $fee->amount;
            $this->currency = $fee->currency;
            $this->is_mandatory = $fee->is_mandatory;
            $this->description = $fee->description;
            $this->due_date = $fee->due_date?->format('Y-m-d');
            $this->is_active = $fee->is_active;
        }
    }

    public function save()
    {
        $validated = $this->validate([
            'program_id' => 'required|exists:programs,id',
            'name' => 'required|string|max:255',
            'term' => 'required|string|max:50',
            'academic_year' => 'required|integer|min:2020|max:2100',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'is_mandatory' => 'boolean',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        if ($this->feeId) {
            $fee = FeeStructure::findOrFail($this->feeId);
            $fee->update($validated);
            session()->flash('message', 'Fee structure updated successfully.');
        } else {
            FeeStructure::create($validated);
            session()->flash('message', 'Fee structure created successfully.');
        }

        return redirect()->route('fees.index');
    }

    public function with(): array
    {
        return [
            'programs' => Program::all(),
            'isEdit' => $this->feeId !== null,
            'terms' => ['Term 1', 'Term 2', 'Term 3'],
            'currencies' => ['UGX', 'USD', 'EUR'],
        ];
    }
}; ?>

<div class="max-w-3xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ $isEdit ? 'Edit Fee Structure' : 'New Fee Structure' }}
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ $isEdit ? 'Update fee structure details' : 'Configure a new fee structure' }}
                </p>
            </div>
            <a href="{{ route('fees.index') }}" 
               class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                Cancel
            </a>
        </div>

        <form wire:submit="save" class="space-y-6">
            <!-- Basic Information -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Fee Details</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Program *</label>
                        <select wire:model="program_id"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="">Select Program</option>
                            @foreach($programs as $program)
                                <option value="{{ $program->id }}">{{ $program->name }}</option>
                            @endforeach
                        </select>
                        @error('program_id') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fee Name *</label>
                        <input type="text" 
                               wire:model="name"
                               placeholder="e.g., Tuition Fee, Library Fee"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('name') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Term *</label>
                            <select wire:model="term"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                @foreach($terms as $termOption)
                                    <option value="{{ $termOption }}">{{ $termOption }}</option>
                                @endforeach
                            </select>
                            @error('term') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Academic Year *</label>
                            <input type="number" 
                                   wire:model="academic_year"
                                   min="2020"
                                   max="2100"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            @error('academic_year') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount *</label>
                            <input type="number" 
                                   wire:model="amount"
                                   step="0.01"
                                   min="0"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            @error('amount') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Currency *</label>
                            <select wire:model="currency"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                @foreach($currencies as $curr)
                                    <option value="{{ $curr }}">{{ $curr }}</option>
                                @endforeach
                            </select>
                            @error('currency') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Due Date</label>
                        <input type="date" 
                               wire:model="due_date"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('due_date') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea wire:model="description"
                                  rows="3"
                                  placeholder="Additional information about this fee"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"></textarea>
                        @error('description') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center space-x-6">
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   wire:model="is_mandatory"
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Mandatory Fee</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" 
                                   wire:model="is_active"
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-4">
                <a href="{{ route('fees.index') }}" 
                   class="px-6 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    {{ $isEdit ? 'Update Fee Structure' : 'Create Fee Structure' }}
                </button>
            </div>
        </form>
</div>
