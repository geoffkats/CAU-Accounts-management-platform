<?php

use App\Models\Student;
use App\Models\StudentInvoice;
use App\Models\StudentPayment;
use Livewire\Volt\Component;

new class extends Component {
    public $studentId;
    public $student;
    public $activeTab = 'overview';

    public function mount($id)
    {
        $this->studentId = $id;
        $this->student = Student::with(['program', 'invoices.items', 'payments', 'scholarships.scholarship', 'paymentPlans.installments'])
            ->findOrFail($id);
    }

    public function with(): array
    {
        $totalInvoiced = $this->student->invoices()->sum('total_amount');
        $totalPaid = $this->student->payments()->sum('amount_base');
        $totalOutstanding = $this->student->invoices()->outstanding()->sum('balance');
        $totalOverdue = $this->student->invoices()->overdue()->sum('balance');

        $recentInvoices = $this->student->invoices()
            ->with('items')
            ->latest()
            ->take(5)
            ->get();

        $recentPayments = $this->student->payments()
            ->with('receivedBy')
            ->latest()
            ->take(5)
            ->get();

        return [
            'stats' => [
                'total_invoiced' => $totalInvoiced,
                'total_paid' => $totalPaid,
                'total_outstanding' => $totalOutstanding,
                'total_overdue' => $totalOverdue,
            ],
            'recentInvoices' => $recentInvoices,
            'recentPayments' => $recentPayments,
            'activeScholarship' => $this->student->active_scholarship,
        ];
    }

    public function getStatusColor($status): string
    {
        return match($status) {
            'active' => 'green',
            'graduated' => 'blue',
            'suspended' => 'yellow',
            'withdrawn' => 'red',
            default => 'zinc',
        };
    }
}; ?>

<x-layouts.app :title="$student->full_name">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-start justify-between">
            <div class="flex items-start space-x-4">
                <div class="p-4 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center space-x-3">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $student->full_name }}</h1>
                        <span class="px-3 py-1 text-sm font-semibold rounded-full 
                            bg-{{ $this->getStatusColor($student->status) }}-100 
                            text-{{ $this->getStatusColor($student->status) }}-800 
                            dark:bg-{{ $this->getStatusColor($student->status) }}-900 
                            dark:text-{{ $this->getStatusColor($student->status) }}-200">
                            {{ ucfirst($student->status) }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $student->student_id }}</p>
                    <div class="flex items-center space-x-4 mt-2 text-sm text-gray-600 dark:text-gray-400">
                        <span>{{ $student->program->name }}</span>
                        @if($student->class_level)
                            <span>•</span>
                            <span>{{ $student->class_level }}</span>
                        @endif
                        <span>•</span>
                        <span>Enrolled: {{ $student->enrollment_date->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('invoices.create') }}?student_id={{ $student->id }}" 
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Create Invoice
                </a>
                <a href="{{ route('students.edit', $student->id) }}" 
                   class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    Edit
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400">Total Invoiced</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                    {{ number_format($stats['total_invoiced'], 2) }} UGX
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400">Total Paid</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">
                    {{ number_format($stats['total_paid'], 2) }} UGX
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400">Outstanding</p>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">
                    {{ number_format($stats['total_outstanding'], 2) }} UGX
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400">Overdue</p>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">
                    {{ number_format($stats['total_overdue'], 2) }} UGX
                </p>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="flex space-x-8 px-6" aria-label="Tabs">
                    <button wire:click="$set('activeTab', 'overview')"
                            class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'overview' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400' }}">
                        Overview
                    </button>
                    <button wire:click="$set('activeTab', 'invoices')"
                            class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'invoices' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400' }}">
                        Invoices
                    </button>
                    <button wire:click="$set('activeTab', 'payments')"
                            class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'payments' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400' }}">
                        Payments
                    </button>
                    <button wire:click="$set('activeTab', 'contact')"
                            class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'contact' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400' }}">
                        Contact Info
                    </button>
                </nav>
            </div>

            <div class="p-6">
                @if($activeTab === 'overview')
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Recent Invoices -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Recent Invoices</h3>
                            <div class="space-y-3">
                                @forelse($recentInvoices as $invoice)
                                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                        <div class="flex items-center justify-between mb-2">
                                            <a href="{{ route('invoices.show', $invoice->id) }}" 
                                               class="font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                                {{ $invoice->invoice_number }}
                                            </a>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                                bg-{{ $invoice->status_color }}-100 
                                                text-{{ $invoice->status_color }}-800 
                                                dark:bg-{{ $invoice->status_color }}-900 
                                                dark:text-{{ $invoice->status_color }}-200">
                                                {{ ucfirst($invoice->status) }}
                                            </span>
                                        </div>
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-600 dark:text-gray-400">{{ $invoice->invoice_date->format('M d, Y') }}</span>
                                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ number_format($invoice->balance, 2) }} {{ $invoice->currency }}
                                            </span>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-gray-500 dark:text-gray-400 text-center py-8">No invoices yet</p>
                                @endforelse
                            </div>
                        </div>

                        <!-- Recent Payments -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Recent Payments</h3>
                            <div class="space-y-3">
                                @forelse($recentPayments as $payment)
                                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $payment->payment_number }}</span>
                                            <span class="text-green-600 dark:text-green-400 font-medium">
                                                {{ number_format($payment->amount, 2) }} {{ $payment->currency }}
                                            </span>
                                        </div>
                                        <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                                            <span>{{ $payment->payment_date->format('M d, Y') }}</span>
                                            <span>{{ $payment->payment_method_label }}</span>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-gray-500 dark:text-gray-400 text-center py-8">No payments yet</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    @if($activeScholarship)
                        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                                </svg>
                                <h4 class="font-semibold text-blue-900 dark:text-blue-100">Active Scholarship</h4>
                            </div>
                            <p class="text-sm text-blue-800 dark:text-blue-200 mt-2">
                                {{ $activeScholarship->scholarship->name }} - {{ $activeScholarship->scholarship->type_label }}
                            </p>
                        </div>
                    @endif
                @endif

                @if($activeTab === 'invoices')
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Invoice #</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Term</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Balance</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($student->invoices as $invoice)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $invoice->invoice_number }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $invoice->invoice_date->format('M d, Y') }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $invoice->term }} {{ $invoice->academic_year }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ number_format($invoice->total_amount, 2) }}</td>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ number_format($invoice->balance, 2) }}</td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                                bg-{{ $invoice->status_color }}-100 
                                                text-{{ $invoice->status_color }}-800">
                                                {{ ucfirst($invoice->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm">
                                            <a href="{{ route('invoices.show', $invoice->id) }}" class="text-blue-600 hover:text-blue-800">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No invoices</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif

                @if($activeTab === 'payments')
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Payment #</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amount</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Method</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Reference</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Received By</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($student->payments as $payment)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $payment->payment_number }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $payment->payment_date->format('M d, Y') }}</td>
                                        <td class="px-4 py-3 text-sm font-medium text-green-600 dark:text-green-400">{{ number_format($payment->amount, 2) }} {{ $payment->currency }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $payment->payment_method_label }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $payment->reference_number ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $payment->receivedBy->name ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No payments</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif

                @if($activeTab === 'contact')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Student Contact</h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $student->email }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $student->phone }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Address</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $student->address ?? 'Not provided' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Date of Birth</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $student->date_of_birth?->format('M d, Y') }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Guardian Contact</h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $student->guardian_name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $student->guardian_phone }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $student->guardian_email ?? 'Not provided' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    @if($student->notes)
                        <div class="mt-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Notes</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $student->notes }}</p>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
