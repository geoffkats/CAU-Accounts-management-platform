<?php

use App\Models\VendorInvoice;
use App\Models\Program;
use App\Models\Currency;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public ?int $programFilter = null;

    public function with(): array
    {
        $query = VendorInvoice::with(['vendor', 'program', 'account'])
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('invoice_number', 'like', '%' . $this->search . '%')
                        ->orWhere('vendor_reference', 'like', '%' . $this->search . '%')
                        ->orWhereHas('vendor', fn($q) => $q->where('name', 'like', '%' . $this->search . '%'));
                });
            })
            ->when($this->statusFilter !== 'all', fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->programFilter, fn($q) => $q->where('program_id', $this->programFilter))
            ->latest('invoice_date');

        $totals = VendorInvoice::selectRaw('
            SUM(amount_base) as total_amount,
            SUM(amount_paid) as total_paid,
            SUM(amount_base - amount_paid) as total_outstanding
        ')->first();

        return [
            'invoices' => $query->paginate(15),
            'programs' => Program::all(),
            'totalInvoices' => $totals->total_amount ?? 0,
            'totalPaid' => $totals->total_paid ?? 0,
            'totalOwed' => $totals->total_outstanding ?? 0,
            'overdueCount' => VendorInvoice::whereNotNull('due_date')
                ->where('due_date', '<', now())
                ->whereIn('status', ['unpaid', 'partially_paid'])
                ->count(),
            'baseCurrency' => Currency::getBaseCurrency(),
        ];
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }
    public function updatedProgramFilter(): void { $this->resetPage(); }

    public function deleteInvoice(int $id): void
    {
        $invoice = VendorInvoice::findOrFail($id);
        $invoice->delete();
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Vendor invoice deleted successfully.']);
    }
}; ?>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 bg-clip-text text-transparent">Vendor Invoices (Bills)</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Track what you owe to vendors</p>
        </div>
        <a href="{{ route('vendor-invoices.create') }}" class="px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-xl hover:shadow-xl transition-all duration-200 font-semibold">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
            New Vendor Invoice
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 p-6 rounded-xl border-2 border-purple-200 dark:border-purple-800 shadow-lg">
            <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Total Invoices</div>
            <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $baseCurrency->symbol }} {{ number_format($totalInvoices, 0) }}</div>
        </div>
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 p-6 rounded-xl border-2 border-green-200 dark:border-green-800 shadow-lg">
            <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Total Paid</div>
            <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $baseCurrency->symbol }} {{ number_format($totalPaid, 0) }}</div>
        </div>
        <div class="bg-gradient-to-br from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 p-6 rounded-xl border-2 border-orange-200 dark:border-orange-800 shadow-lg">
            <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Amount Owed</div>
            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ $baseCurrency->symbol }} {{ number_format($totalOwed, 0) }}</div>
        </div>
        <div class="bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 p-6 rounded-xl border-2 border-red-200 dark:border-red-800 shadow-lg">
            <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Overdue Bills</div>
            <div class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $overdueCount }}</div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Invoice # or vendor..." class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                <select wire:model.live="statusFilter" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                    <option value="all">All Status</option>
                    <option value="unpaid">Unpaid</option>
                    <option value="partially_paid">Partially Paid</option>
                    <option value="paid">Paid</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Program</label>
                <select wire:model.live="programFilter" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                    <option value="">All Programs</option>
                    @foreach($programs as $program)
                        <option value="{{ $program->id }}">{{ $program->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Invoice #</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Date / Due</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Vendor</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Amount</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Status</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($invoices as $invoice)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors cursor-pointer" onclick="window.Livewire.navigate('{{ route('vendor-invoices.show', $invoice->id) }}')">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-mono text-sm font-semibold text-purple-600 dark:text-purple-400">{{ $invoice->invoice_number }}</span>
                            @if($invoice->vendor_reference)<div class="text-xs text-gray-500">Ref: {{ $invoice->vendor_reference }}</div>@endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div>{{ $invoice->invoice_date->format('M d, Y') }}</div>
                            @if($invoice->due_date)
                                <div class="text-xs {{ $invoice->is_overdue ? 'text-red-600 font-semibold' : 'text-gray-500' }}">
                                    Due: {{ $invoice->due_date->format('M d, Y') }}
                                    @if($invoice->is_overdue)<span class="ml-1">({{ $invoice->days_overdue }}d)</span>@endif
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $invoice->vendor->name }}</div>
                            <div class="text-xs text-gray-500">{{ $invoice->program->name }}</div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $invoice->currency }} {{ number_format($invoice->amount, 0) }}</div>
                            @if($invoice->remaining_balance > 0)
                                <div class="text-xs text-red-600">Owed: {{ number_format($invoice->remaining_balance, 0) }}</div>
                            @else
                                <div class="text-xs text-gray-400">Paid in full</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold
                                {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                {{ $invoice->status === 'partially_paid' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                {{ $invoice->status === 'unpaid' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}">
                                {{ str_replace('_', ' ', ucfirst($invoice->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right" onclick="event.stopPropagation()">
                            <div class="flex items-center justify-end gap-2">
                                @if($invoice->remaining_balance > 0)
                                    <a href="{{ route('vendor-invoices.show', $invoice->id) }}" wire:navigate class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-semibold">Pay</a>
                                @endif
                                <button wire:click="deleteInvoice({{ $invoice->id }})" wire:confirm="Delete this invoice?" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-12 text-center">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        <p class="text-gray-500 font-medium mb-4">No vendor invoices found</p>
                        <a href="{{ route('vendor-invoices.create') }}" class="inline-flex px-6 py-2 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-lg hover:shadow-lg text-sm font-medium">Create First Invoice</a>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages())<div class="px-6 py-4 border-t">{{ $invoices->links() }}</div>@endif
    </div>
</div>
