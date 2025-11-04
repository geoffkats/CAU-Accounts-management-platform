<?php

use App\Models\Student;
use App\Models\Program;
use App\Models\FeeStructure;
use App\Models\StudentInvoice;
use App\Models\StudentInvoiceItem;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public $program_id = '';
    public $term = 'Term 1';
    public $academic_year = '';
    public $invoice_date = '';
    public $due_date = '';
    public $selectedStudents = [];
    public $selectedFees = [];
    public $preview = [];

    public function mount()
    {
        $this->academic_year = now()->year;
        $this->invoice_date = now()->format('Y-m-d');
        $this->due_date = now()->addDays(30)->format('Y-m-d');
    }

    public function updatedProgramId()
    {
        $this->selectedStudents = [];
        $this->selectedFees = [];
        $this->preview = [];
    }

    public function generatePreview()
    {
        $this->validate([
            'program_id' => 'required|exists:programs,id',
            'term' => 'required|string',
            'academic_year' => 'required|integer',
            'selectedStudents' => 'required|array|min:1',
            'selectedFees' => 'required|array|min:1',
        ]);

        $students = Student::whereIn('id', $this->selectedStudents)->get();
        $fees = FeeStructure::whereIn('id', $this->selectedFees)->get();
        $total = $fees->sum('amount');

        $this->preview = $students->map(function ($student) use ($fees, $total) {
            return [
                'student_id' => $student->id,
                'student_name' => $student->full_name,
                'student_number' => $student->student_id,
                'fees' => $fees,
                'total' => $total,
            ];
        })->all();
    }

    public function generate()
    {
        $this->validate([
            'program_id' => 'required|exists:programs,id',
            'term' => 'required|string',
            'academic_year' => 'required|integer',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'selectedStudents' => 'required|array|min:1',
            'selectedFees' => 'required|array|min:1',
        ]);

        DB::transaction(function () {
            $students = Student::whereIn('id', $this->selectedStudents)->get();
            $fees = FeeStructure::whereIn('id', $this->selectedFees)->get();
            $total = $fees->sum('amount');

            foreach ($students as $student) {
                $invoice = StudentInvoice::create([
                    'student_id' => $student->id,
                    'program_id' => $this->program_id,
                    'term' => $this->term,
                    'academic_year' => $this->academic_year,
                    'invoice_date' => $this->invoice_date,
                    'due_date' => $this->due_date,
                    'total_amount' => $total,
                    'discount_amount' => 0,
                    'paid_amount' => 0,
                    'currency' => 'UGX',
                    'exchange_rate' => 1,
                    'status' => 'sent',
                ]);

                foreach ($fees as $fee) {
                    StudentInvoiceItem::create([
                        'student_invoice_id' => $invoice->id,
                        'fee_structure_id' => $fee->id,
                        'description' => $fee->name,
                        'amount' => $fee->amount,
                        'quantity' => 1,
                    ]);
                }
            }
        });

        session()->flash('message', count($this->selectedStudents) . ' invoices generated successfully.');
        return redirect()->route('invoices.index');
    }

    public function with(): array
    {
        $students = [];
        $fees = [];

        if ($this->program_id) {
            $students = Student::where('program_id', $this->program_id)
                ->where('status', 'active')
                ->get();
            
            $fees = FeeStructure::where('program_id', $this->program_id)
                ->where('is_active', true)
                ->get();
        }

        return [
            'programs' => Program::all(),
            'students' => $students,
            'fees' => $fees,
            'terms' => ['Term 1', 'Term 2', 'Term 3'],
        ];
    }
}; ?>

<x-layouts.app title="Bulk Invoice Generation">
    <div class="max-w-6xl mx-auto space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Bulk Invoice Generation</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Generate invoices for multiple students at once</p>
            </div>
            <a href="{{ route('invoices.index') }}" 
               class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                Cancel
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Configuration Form -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Invoice Configuration</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Program *</label>
                            <select wire:model.live="program_id"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                <option value="">Select Program</option>
                                @foreach($programs as $program)
                                    <option value="{{ $program->id }}">{{ $program->name }}</option>
                                @endforeach
                            </select>
                            @error('program_id') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Term *</label>
                                <select wire:model="term"
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                    @foreach($terms as $termOption)
                                        <option value="{{ $termOption }}">{{ $termOption }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Academic Year *</label>
                                <input type="number" 
                                       wire:model="academic_year"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Invoice Date *</label>
                                <input type="date" 
                                       wire:model="invoice_date"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Due Date</label>
                                <input type="date" 
                                       wire:model="due_date"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            </div>
                        </div>
                    </div>
                </div>

                @if($program_id && count($students) > 0)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Select Students</h2>
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            @foreach($students as $student)
                                <label class="flex items-center p-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded cursor-pointer">
                                    <input type="checkbox" 
                                           wire:model="selectedStudents"
                                           value="{{ $student->id }}"
                                           class="rounded border-gray-300 text-blue-600">
                                    <span class="ml-3 text-gray-900 dark:text-gray-100">
                                        {{ $student->full_name }} ({{ $student->student_id }})
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('selectedStudents') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Select Fees</h2>
                        <div class="space-y-2">
                            @foreach($fees as $fee)
                                <label class="flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded cursor-pointer">
                                    <div class="flex items-center">
                                        <input type="checkbox" 
                                               wire:model="selectedFees"
                                               value="{{ $fee->id }}"
                                               class="rounded border-gray-300 text-blue-600">
                                        <span class="ml-3 text-gray-900 dark:text-gray-100">{{ $fee->name }}</span>
                                    </div>
                                    <span class="text-gray-600 dark:text-gray-400">{{ number_format($fee->amount, 2) }} {{ $fee->currency }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('selectedFees') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>
                @endif
            </div>

            <!-- Preview & Actions -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 sticky top-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Summary</h2>
                    
                    <dl class="space-y-3 mb-6">
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">Students Selected</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ count($selectedStudents) }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">Fees Selected</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ count($selectedFees) }}</dd>
                        </div>
                        @if(count($preview) > 0)
                            <div class="flex items-center justify-between pt-3 border-t border-gray-200 dark:border-gray-700">
                                <dt class="text-sm font-medium text-gray-900 dark:text-gray-100">Total per Invoice</dt>
                                <dd class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ number_format($preview[0]['total'], 2) }}</dd>
                            </div>
                        @endif
                    </dl>

                    <div class="space-y-2">
                        @if(count($selectedStudents) > 0 && count($selectedFees) > 0)
                            @if(count($preview) === 0)
                                <button wire:click="generatePreview"
                                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                    Preview Invoices
                                </button>
                            @else
                                <button wire:click="generate"
                                        class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                    Generate {{ count($preview) }} Invoices
                                </button>
                                <button wire:click="$set('preview', [])"
                                        class="w-full px-4 py-2 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                    Edit Selection
                                </button>
                            @endif
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                                Select students and fees to continue
                            </p>
                        @endif
                    </div>

                    @if(count($preview) > 0)
                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Preview</h3>
                            <div class="space-y-2 max-h-96 overflow-y-auto">
                                @foreach($preview as $item)
                                    <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded text-sm">
                                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ $item['student_name'] }}</p>
                                        <p class="text-gray-600 dark:text-gray-400">{{ number_format($item['total'], 2) }} UGX</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
