<?php

use App\Models\StudentInvoice;
use App\Models\Student;
use App\Models\FeeStructure;
use App\Models\StudentInvoiceItem;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public $invoiceId = null;
    public $student_id = '';
    public $term = 'Term 1';
    public $academic_year = '';
    public $invoice_date = '';
    public $due_date = '';
    public $currency = 'UGX';
    public $exchange_rate = 1;
    public $notes = '';
    public $status = 'draft';
    
    public $items = [];
    public $availableFees = [];

    public function mount($id = null)
    {
        $this->academic_year = now()->year;
        $this->invoice_date = now()->format('Y-m-d');
        $this->due_date = now()->addDays(30)->format('Y-m-d');
        
        if (request('student_id')) {
            $this->student_id = request('student_id');
            $this->loadAvailableFees();
        }
        
        if ($id) {
            $this->invoiceId = $id;
            $invoice = StudentInvoice::with('items')->findOrFail($id);
            
            $this->student_id = $invoice->student_id;
            $this->term = $invoice->term;
            $this->academic_year = $invoice->academic_year;
            $this->invoice_date = $invoice->invoice_date->format('Y-m-d');
            $this->due_date = $invoice->due_date?->format('Y-m-d');
            $this->currency = $invoice->currency;
            $this->exchange_rate = $invoice->exchange_rate;
            $this->notes = $invoice->notes;
            $this->status = $invoice->status;
            
            foreach ($invoice->items as $item) {
                $this->items[] = [
                    'id' => $item->id,
                    'fee_structure_id' => $item->fee_structure_id,
                    'description' => $item->description,
                    'amount' => $item->amount,
                    'quantity' => $item->quantity,
                ];
            }
        } else {
            $this->addItem();
        }
    }

    public function updatedStudentId()
    {
        $this->loadAvailableFees();
    }

    public function loadAvailableFees()
    {
        if ($this->student_id) {
            $student = Student::find($this->student_id);
            if ($student) {
                $this->availableFees = FeeStructure::where('program_id', $student->program_id)
                    ->where('is_active', true)
                    ->get();
            }
        }
    }

    public function addItem()
    {
        $this->items[] = [
            'id' => null,
            'fee_structure_id' => '',
            'description' => '',
            'amount' => 0,
            'quantity' => 1,
        ];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updatedItems($value, $key)
    {
        if (str_contains($key, 'fee_structure_id')) {
            $index = (int) explode('.', $key)[0];
            $feeId = $this->items[$index]['fee_structure_id'];
            
            if ($feeId) {
                $fee = FeeStructure::find($feeId);
                if ($fee) {
                    $this->items[$index]['description'] = $fee->name;
                    $this->items[$index]['amount'] = $fee->amount;
                }
            }
        }
    }

    public function save()
    {
        $this->validate([
            'student_id' => 'required|exists:students,id',
            'term' => 'required|string',
            'academic_year' => 'required|integer',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'currency' => 'required|string|max:3',
            'exchange_rate' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::transaction(function () {
            $student = Student::findOrFail($this->student_id);
            
            $total = collect($this->items)->sum(fn($item) => $item['amount'] * $item['quantity']);
            
            $data = [
                'student_id' => $this->student_id,
                'program_id' => $student->program_id,
                'term' => $this->term,
                'academic_year' => $this->academic_year,
                'invoice_date' => $this->invoice_date,
                'due_date' => $this->due_date,
                'total_amount' => $total,
                'discount_amount' => 0,
                'paid_amount' => 0,
                'currency' => $this->currency,
                'exchange_rate' => $this->exchange_rate,
                'status' => $this->status,
                'notes' => $this->notes,
            ];

            if ($this->invoiceId) {
                $invoice = StudentInvoice::findOrFail($this->invoiceId);
                $invoice->update($data);
                $invoice->items()->delete();
            } else {
                $invoice = StudentInvoice::create($data);
            }

            foreach ($this->items as $item) {
                StudentInvoiceItem::create([
                    'student_invoice_id' => $invoice->id,
                    'fee_structure_id' => $item['fee_structure_id'] ?: null,
                    'description' => $item['description'],
                    'amount' => $item['amount'],
                    'quantity' => $item['quantity'],
                ]);
            }

            session()->flash('message', 'Invoice saved successfully.');
            return redirect()->route('invoices.show', $invoice->id);
        });
    }

    public function getTotal()
    {
        return collect($this->items)->sum(fn($item) => ($item['amount'] ?? 0) * ($item['quantity'] ?? 1));
    }

    public function with(): array
    {
        return [
            'students' => Student::where('status', 'active')->get(),
            'isEdit' => $this->invoiceId !== null,
            'terms' => ['Term 1', 'Term 2', 'Term 3'],
            'total' => $this->getTotal(),
        ];
    }
}; ?>

<div class="max-w-5xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ $isEdit ? 'Edit Invoice' : 'New Invoice' }}
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ $isEdit ? 'Update invoice details' : 'Create a new student invoice' }}
                </p>
            </div>
            <a href="{{ route('invoices.index') }}" 
               class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                Cancel
            </a>
        </div>

        <form wire:submit="save" class="space-y-6">
            <!-- Invoice Details -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Invoice Details</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Student *</label>
                        <select wire:model.live="student_id"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="">Select Student</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}">{{ $student->full_name }} ({{ $student->student_id }})</option>
                            @endforeach
                        </select>
                        @error('student_id') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                        <select wire:model="status"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="draft">Draft</option>
                            <option value="sent">Sent</option>
                        </select>
                    </div>

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
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('academic_year') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Invoice Date *</label>
                        <input type="date" 
                               wire:model="invoice_date"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('invoice_date') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Due Date</label>
                        <input type="date" 
                               wire:model="due_date"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('due_date') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Currency *</label>
                        <select wire:model="currency"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="UGX">UGX</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                        </select>
                        @error('currency') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Exchange Rate *</label>
                        <input type="number" 
                               wire:model="exchange_rate"
                               step="0.0001"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        @error('exchange_rate') <span class="text-sm text-red-600 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                        <textarea wire:model="notes"
                                  rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"></textarea>
                    </div>
                </div>
            </div>

            <!-- Invoice Items -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Line Items</h2>
                    <button type="button" 
                            wire:click="addItem"
                            class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
                        + Add Item
                    </button>
                </div>

                <div class="space-y-4">
                    @foreach($items as $index => $item)
                        <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                @if(count($availableFees) > 0)
                                    <div class="md:col-span-4">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fee Structure</label>
                                        <select wire:model.live="items.{{ $index }}.fee_structure_id"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm">
                                            <option value="">Select or enter custom</option>
                                            @foreach($availableFees as $fee)
                                                <option value="{{ $fee->id }}">{{ $fee->name }} ({{ number_format($fee->amount, 2) }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                <div class="{{ count($availableFees) > 0 ? 'md:col-span-3' : 'md:col-span-5' }}">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description *</label>
                                    <input type="text" 
                                           wire:model="items.{{ $index }}.description"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm">
                                    @error("items.{$index}.description") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount *</label>
                                    <input type="number" 
                                           wire:model="items.{{ $index }}.amount"
                                           step="0.01"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm">
                                    @error("items.{$index}.amount") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                </div>

                                <div class="md:col-span-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Qty *</label>
                                    <input type="number" 
                                           wire:model="items.{{ $index }}.quantity"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm">
                                </div>

                                <div class="md:col-span-2 flex items-end">
                                    <div class="w-full">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Total</label>
                                        <div class="px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-lg text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ number_format(($item['amount'] ?? 0) * ($item['quantity'] ?? 1), 2) }}
                                        </div>
                                    </div>
                                    @if(count($items) > 1)
                                        <button type="button" 
                                                wire:click="removeItem({{ $index }})"
                                                class="ml-2 p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">Total Amount:</span>
                        <span class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ number_format($total, 2) }} {{ $currency }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-4">
                <a href="{{ route('invoices.index') }}" 
                   class="px-6 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    {{ $isEdit ? 'Update Invoice' : 'Create Invoice' }}
                </button>
            </div>
        </form>
</div>
