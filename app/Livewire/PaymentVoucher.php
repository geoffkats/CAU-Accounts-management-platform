<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;

class PaymentVoucher extends Component
{
    public $expense_id;
    public $expense;

    #[Validate('required|date')]
    public $payment_date;

    #[Validate('required|exists:accounts,id')]
    public $payment_account_id;

    #[Validate('required|numeric|min:0.01')]
    public $amount;

    #[Validate('nullable|string|max:50')]
    public $payment_method;

    #[Validate('nullable|string|max:100')]
    public $payment_reference;

    #[Validate('nullable|string')]
    public $notes;

    public function mount($expense = null, $id = null)
    {
        // Handle both route parameters
        $expenseId = $expense ?? $id;
        
        if ($expenseId) {
            $this->expense_id = $expenseId;
            $this->expense = Expense::findOrFail($expenseId);
            $this->amount = $this->expense->outstanding_balance;
        }

        $this->payment_date = now()->format('Y-m-d');
    }

    public function createPayment()
    {
        $this->validate();

        if (!$this->expense) {
            $this->expense = Expense::findOrFail($this->expense_id);
        }

        // Prevent overpayment
        if ($this->amount > $this->expense->outstanding_balance) {
            $this->addError('amount', 'Payment cannot exceed the outstanding balance of ' . number_format($this->expense->outstanding_balance, 2));
            return;
        }

        // Create Payment (payment_status is now computed automatically)
        $payment = Payment::create([
            'expense_id' => $this->expense_id,
            'payment_date' => $this->payment_date,
            'payment_account_id' => $this->payment_account_id,
            'amount' => $this->amount,
            'payment_method' => $this->payment_method,
            'payment_reference' => $this->payment_reference,
            'notes' => $this->notes,
            'status' => 'pending', // Requires approval
        ]);

        session()->flash('message', 'Payment voucher ' . $payment->voucher_number . ' created successfully.');
        return redirect()->route('expenses.show', $this->expense_id);
    }

    public function render()
    {
        // Get ALL active accounts - user can debit from any account
        // Grouped by type for better UI organization (Asset, Liability, Equity, Income, Expense)
        $accountsByType = Account::where('is_active', true)
            ->orderBy('type')
            ->orderBy('code')
            ->get()
            ->groupBy('type');

        return view('livewire.payment-voucher', [
            'accountsByType' => $accountsByType
        ]);
    }
}
