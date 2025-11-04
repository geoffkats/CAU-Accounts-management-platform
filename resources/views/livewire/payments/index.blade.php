<?php

use App\Models\StudentPayment;
use App\Models\Student;
use App\Models\StudentInvoice;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $studentFilter = 'all';
    public $methodFilter = 'all';

    public function with(): array
    {
        $query = StudentPayment::with(['student', 'invoice', 'receivedBy'])
            ->when($this->search, fn($q) => $q->where(function($query) {
                $query->where('payment_number', 'like', "%{$this->search}%")
                    ->orWhere('reference_number', 'like', "%{$this->search}%")
                    ->orWhereHas('student', fn($q) => $q->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%"));
            }))
            ->when($this->studentFilter !== 'all', fn($q) => $q->where('student_id', $this->studentFilter))
            ->when($this->methodFilter !== 'all', fn($q) => $q->where('payment_method', $this->methodFilter))
            ->latest();

        return [
            'payments' => $query->paginate(20),
            'students' => Student::where('status', 'active')->get(),
            'stats' => [
                'total' => StudentPayment::count(),
                'total_amount' => StudentPayment::sum('amount_base'),
                'today' => StudentPayment::whereDate('payment_date', today())->count(),
                'this_month' => StudentPayment::whereMonth('payment_date', now()->month)->sum('amount_base'),
            ],
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Student Payments</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Record and track student fee payments</p>
        </div>
        <a href="{{ route('payments.create') }}" 
           class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
            + Record Payment
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400">Total Payments</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ number_format($stats['total']) }}</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400">Total Collected</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ number_format($stats['total_amount'], 0) }}</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400">Today</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ number_format($stats['today']) }}</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400">This Month</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ number_format($stats['this_month'], 0) }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                    <input type="text" 
                           wire:model.live.debounce.300ms="search"
                           placeholder="Payment #, reference, student..."
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Student</label>
                    <select wire:model.live="studentFilter"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <option value="all">All Students</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}">{{ $student->full_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Payment Method</label>
                    <select wire:model.live="methodFilter"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <option value="all">All Methods</option>
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="cheque">Cheque</option>
                        <option value="card">Card</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Payment #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Student</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Method</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reference</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Received By</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($payments as $payment)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $payment->payment_number }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $payment->student->full_name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $payment->student->student_id }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-gray-100">{{ $payment->payment_date->format('M d, Y') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-green-600 dark:text-green-400">
                                    {{ number_format($payment->amount, 2) }} {{ $payment->currency }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-gray-100">{{ $payment->payment_method_label }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-gray-100">{{ $payment->reference_number ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-gray-100">{{ $payment->receivedBy->name ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('payments.show', $payment->id) }}" 
                                   class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-gray-500 dark:text-gray-400">No payments found</p>
                                    <a href="{{ route('payments.create') }}" 
                                       class="mt-4 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                        Record First Payment
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Pagination -->
            @if($payments->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>
</div>
