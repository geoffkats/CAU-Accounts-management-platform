<?php

use App\Models\StudentPayment;
use App\Models\Student;
use App\Models\StudentInvoice;
use Livewire\Volt\Component;

new class extends Component {
    public $student_id = '';
    public $student_invoice_id = '';
    public $payment_date = '';
    public $amount = '';
    public $currency = 'UGX';
    public $exchange_rate = 1;
    public $payment_method = 'cash';
    public $reference_number = '';
    public $notes = '';

    public function mount()
    {
        $this->payment_date = now()->format('Y-m-d');
        
        if (request('invoice_id')) {
            $invoice = StudentInvoice::findOrFail(request('invoice_id'));
            $this->student_id = $invoice->student_id;
            $this->student_invoice_id = $invoice->id;
            $this->amount = $invoice->balance;
            $this->currency = $invoice->currency;
            $this->exchange_rate = $invoice->exchange_rate;
        } elseif (request('student_id')) {
            $this->student_id = request('student_id');
        }
    }

    public function updatedStudentId()
    {
        $this->student_invoice_id = '';
        $this->amount = '';
    }

    public function updatedStudentInvoiceId()
    {
        if ($this->student_invoice_id) {
            $invoice = StudentInvoice::find($this->student_invoice_id);
            if ($invoice) {
                $this->amount = $invoice->balance;
                $this->currency = $invoice->currency;
                $this->exchange_rate = $invoice->exchange_rate;
            }
        }
    }

    public function save()
    {
        $validated = $this->validate([
            'student_id' => 'required|exists:students,id',
            'student_invoice_id' => 'nullable|exists:student_invoices,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|max:3',
            'exchange_rate' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,bank_transfer,mobile_money,cheque,card',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['received_by'] = auth()->id();
        $payment = StudentPayment::create($validated);

        session()->flash('message', 'Payment recorded successfully.');
        return redirect()->route('payments.show', $payment->id);
    }

    public function with(): array
    {
        $students = Student::where('status', 'active')->get();
        $invoices = [];

        if ($this->student_id) {
            $invoices = StudentInvoice::where('student_id', $this->student_id)
                ->outstanding()
                ->get();
        }

        return [
            'students' => $students,
            'invoices' => $invoices,
        ];
    }
}; ?>

<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Record Payment</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Record a student fee payment</p>
            </div>
            <a href="{{ route('payments.index') }}" 
               class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                Cancel
            </a>
        </div>

        <form wire:submit="save" class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Payment Details</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Student *</label>
                        <select wire:model.live="student_id"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="">Select Student</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}">{{ $student->full_name }} ({{ $student->student_id }})</option>
                            @endforeach
                        </select>
                        @error('student_id') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>

                    @if(count($invoices) > 0)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Invoice (Optional)</label>
                            <select wire:model.live="student_invoice_id"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                <option value="">Select Invoice or enter amount</option>
                                @foreach($invoices as $invoice)
                                    <option value="{{ $invoice->id }}">{{ $invoice->invoice_number }} - {{ number_format($invoice->balance, 2) }} {{ $invoice->currency }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Payment Date *</label>
                            <input type="date" 
                                   wire:model="payment_date"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            @error('payment_date') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount *</label>
                            <input type="number" 
                                   wire:model="amount"
                                   step="0.01"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            @error('amount') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Currency *</label>
                            <select wire:model="currency"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                <option value="UGX">UGX</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Exchange Rate *</label>
                            <input type="number" 
                                   wire:model="exchange_rate"
                                   step="0.0001"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Payment Method *</label>
                        <select wire:model="payment_method"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="cheque">Cheque</option>
                            <option value="card">Card</option>
                        </select>
                        @error('payment_method') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reference Number</label>
                        <input type="text" 
                               wire:model="reference_number"
                               placeholder="Transaction ID, Cheque number, etc."
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                        <textarea wire:model="notes"
                                  rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"></textarea>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-4">
                <a href="{{ route('payments.index') }}" 
                   class="px-6 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    Record Payment
                </button>
            </div>
        </form>
</div>
