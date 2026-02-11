<?php

namespace App\Console\Commands;

use App\Models\CustomerPayment;
use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Console\Command;

class FixCashPaymentAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:fix-cash-accounts {--dry-run : Preview changes without applying them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix cash payments that were incorrectly recorded to bank account instead of Cash on Hand';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Get the accounts
        $cashAccount = Account::where('code', '1000')->first();
        $bankAccount = Account::where('code', '1100')->first();
        $arAccount = Account::where('code', '1200')->first();

        if (!$cashAccount || !$bankAccount || !$arAccount) {
            $this->error('❌ Required accounts not found in the system');
            return 1;
        }

        $this->info("Cash on Hand Account: {$cashAccount->code} - {$cashAccount->name}");
        $this->info("Bank Account: {$bankAccount->code} - {$bankAccount->name}");
        $this->newLine();

        // Find all cash payments
        $cashPayments = CustomerPayment::where('payment_method', 'cash')
            ->with(['journalEntry.lines.account', 'sale', 'customer'])
            ->orderBy('payment_date', 'desc')
            ->get();

        $this->info("Found {$cashPayments->count()} cash payment(s) in the system");
        $this->newLine();

        $affectedCount = 0;
        $fixedCount = 0;
        $affectedPayments = [];

        foreach ($cashPayments as $payment) {
            $journalEntry = $payment->journalEntry;
            
            if (!$journalEntry) {
                $this->warn("⚠️  Payment #{$payment->id} has no journal entry - skipping");
                continue;
            }

            // Find the debit line (should be cash on hand for cash payments)
            $debitLine = $journalEntry->lines->where('debit', '>', 0)->first();
            
            if (!$debitLine) {
                $this->warn("⚠️  Payment #{$payment->id} has no debit line - skipping");
                continue;
            }

            // Check if it was incorrectly posted to bank account
            if ($debitLine->account_id === $bankAccount->id) {
                $affectedCount++;
                
                $invoiceRef = $payment->sale?->invoice_number ?? 'N/A';
                $customerName = $payment->customer?->name ?? 'Unknown';
                $amount = number_format($payment->amount, 0);
                $date = $payment->payment_date->format('Y-m-d');
                
                $affectedPayments[] = [
                    'ID' => $payment->id,
                    'Date' => $date,
                    'Customer' => substr($customerName, 0, 25),
                    'Invoice' => $invoiceRef,
                    'Amount' => $amount,
                    'Wrong Account' => $debitLine->account->name,
                ];
                
                if (!$dryRun) {
                    try {
                        // Update the journal entry line to use the correct account
                        $debitLine->update([
                            'account_id' => $cashAccount->id,
                            'description' => "Cash received - cash (CORRECTED)",
                        ]);
                        
                        $fixedCount++;
                    } catch (\Exception $e) {
                        $this->error("   ❌ Error fixing payment #{$payment->id}: {$e->getMessage()}");
                    }
                }
            }
        }

        if (!empty($affectedPayments)) {
            $this->newLine();
            $this->error('🔴 CASH PAYMENTS INCORRECTLY POSTED TO BANK ACCOUNT:');
            $this->table(
                ['ID', 'Date', 'Customer', 'Invoice', 'Amount', 'Wrong Account'],
                $affectedPayments
            );
        } else {
            $this->info('✅ All cash payments are correctly posted to Cash on Hand!');
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════════════');
        $this->info("📊 SUMMARY");
        $this->info('═══════════════════════════════════════════════');
        $this->info("Total cash payments: {$cashPayments->count()}");
        $this->info("Incorrectly posted to bank: {$affectedCount}");
        
        if ($dryRun) {
            $this->warn("Would fix: {$affectedCount} payment(s)");
            $this->newLine();
            $this->comment('💡 Run without --dry-run to apply the fixes');
        } else {
            $this->info("Successfully fixed: {$fixedCount} payment(s)");
            if ($fixedCount > 0) {
                $this->newLine();
                $this->info('✅ All incorrect cash payments have been corrected!');
            }
        }

        return 0;
    }
}
