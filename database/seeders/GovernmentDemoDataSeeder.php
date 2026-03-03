<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\ExchangeRate;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\Program;
use App\Models\Sale;
use App\Models\Staff;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorInvoice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GovernmentDemoDataSeeder extends Seeder
{
    private const DEMO_TAG = 'GOV_DEMO_2026';

    public function run(): void
    {
        DB::disableQueryLog();

        $scale = max(1, (int) env('DEMO_DATA_SCALE', 5));
        $months = max(24, (int) env('DEMO_DATA_MONTHS', 36));
        $batchTag = self::DEMO_TAG . '|BATCH:' . now()->format('YmdHis');

        $this->command->info("Creating high-volume government demo data ({$months} months, scale x{$scale})...");

        $this->ensureMinimumReferenceData();

        $programs = Program::query()->get();
        $customers = Customer::query()->where('is_active', true)->get();
        $vendors = Vendor::query()->where('is_active', true)->get();
        $staff = Staff::query()->where('is_active', true)->get();

        $incomeAccounts = Account::query()->where('type', 'income')->where('is_active', true)->get();
        $expenseAccounts = Account::query()->where('type', 'expense')->where('is_active', true)->get();
        $receiptAccounts = Account::query()->whereIn('code', ['1000', '1100'])->where('is_active', true)->get();
        $paymentAccounts = Account::query()->whereIn('code', ['1000', '1100'])->where('is_active', true)->get();
        $accountsPayable = Account::query()->where('code', '2000')->where('is_active', true)->first();
        $payrollExpenseAccount = Account::query()->where('code', '5100')->where('is_active', true)->first()
            ?? $expenseAccounts->first();
        $approver = User::query()->whereIn('role', ['admin', 'accountant'])->orderBy('id')->first()
            ?? User::query()->orderBy('id')->first();

        if (
            $programs->isEmpty() ||
            $customers->isEmpty() ||
            $vendors->isEmpty() ||
            $staff->isEmpty() ||
            $incomeAccounts->isEmpty() ||
            $expenseAccounts->isEmpty() ||
            $receiptAccounts->isEmpty() ||
            $paymentAccounts->isEmpty() ||
            !$accountsPayable ||
            !$payrollExpenseAccount ||
            !$approver
        ) {
            $this->command->error('❌ Required reference records are missing. Ensure users, core accounts, staff, programs, customers, and vendors exist.');
            return;
        }

        $faker = fake();
        $start = now()->subMonths($months)->startOfMonth();
        $end = now()->subDay()->endOfDay();

        $salesCreated = 0;
        $customerPaymentsCreated = 0;
        $expensesCreated = 0;
        $paymentVouchersCreated = 0;
        $vendorInvoicesCreated = 0;
        $vendorInvoicePaymentsCreated = 0;
        $vendorInvoiceJournalEntriesCreated = 0;
        $payrollRunsCreated = 0;
        $payrollItemsCreated = 0;
        $payrollJournalEntriesCreated = 0;
        $saleSequence = 1;
        $runSequence = ((int) PayrollRun::query()->max('id')) + 1;

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd = $cursor->copy()->endOfMonth()->min($end);

            $salesPerMonth = random_int(140, 260) * $scale;
            for ($index = 0; $index < $salesPerMonth; $index++) {
                $saleDate = Carbon::instance($faker->dateTimeBetween($monthStart, $monthEnd));
                $documentType = $this->pickSalesDocumentType();
                $currency = $this->pickCurrency();
                $amount = $this->randomSaleAmount($currency);
                $terms = [7, 14, 30, 45][array_rand([7, 14, 30, 45])];

                $sale = Sale::create([
                    'program_id' => $programs->random()->id,
                    'customer_id' => $customers->random()->id,
                    'account_id' => $incomeAccounts->random()->id,
                    'document_type' => $documentType,
                    'product_area_code' => 'EDU-' . strtoupper($faker->bothify('??###')),
                    'invoice_number' => sprintf('GDEMO-%s-%06d', $saleDate->format('Ym'), $saleSequence++),
                    'sale_date' => $saleDate->toDateString(),
                    'due_date' => $saleDate->copy()->addDays($terms)->toDateString(),
                    'validity_date' => $saleDate->copy()->addDays(30)->toDateString(),
                    'delivery_date' => $saleDate->copy()->addDays(random_int(0, 10))->toDateString(),
                    'order_status' => ['fulfilled', 'pending', 'processing'][array_rand(['fulfilled', 'pending', 'processing'])],
                    'amount' => $amount,
                    'currency' => $currency,
                    'amount_paid' => 0,
                    'discount_amount' => $this->discountForAmount($amount, $currency),
                    'tax_amount' => $this->taxForAmount($amount, $currency),
                    'description' => 'Training and capacity-building services for public-sector cohorts',
                    'terms_conditions' => 'Payment due per agreed institutional schedule.',
                    'receipt_number' => 'RCPT-' . $faker->numerify('######'),
                    'status' => Sale::STATUS_UNPAID,
                    'payment_method' => null,
                    'reference_number' => null,
                    'notes' => $batchTag,
                ]);

                $salesCreated++;

                if ($sale->postsToLedger()) {
                    $customerPaymentsCreated += $this->seedCustomerPaymentsForSale($sale, $receiptAccounts, $monthEnd);
                }
            }

            $expensesPerMonth = random_int(180, 340) * $scale;
            for ($index = 0; $index < $expensesPerMonth; $index++) {
                $expenseDate = Carbon::instance($faker->dateTimeBetween($monthStart, $monthEnd));
                $currency = $this->pickCurrency();
                $amount = $this->randomExpenseAmount($currency);
                $charges = $this->randomCharges($currency);

                $expense = Expense::create([
                    'program_id' => $programs->random()->id,
                    'vendor_id' => $vendors->random()->id,
                    'account_id' => $expenseAccounts->random()->id,
                    'expense_date' => $expenseDate->toDateString(),
                    'amount' => $amount,
                    'charges' => $charges,
                    'currency' => $currency,
                    'status' => 'unpaid',
                    'description' => 'Operational and program delivery expense for ongoing institutional activities',
                    'category' => 'Operations',
                    'notes' => $batchTag,
                ]);

                $expensesCreated++;

                $paymentVouchersCreated += $this->seedPaymentsForExpense($expense, $paymentAccounts, $monthEnd);
            }

            $vendorInvoicesPerMonth = random_int(110, 240) * $scale;
            for ($index = 0; $index < $vendorInvoicesPerMonth; $index++) {
                $invoiceDate = Carbon::instance($faker->dateTimeBetween($monthStart, $monthEnd));
                $currency = $this->pickCurrency();
                $amount = $this->randomExpenseAmount($currency);
                $terms = $faker->randomElement(['immediate', 'net_7', 'net_15', 'net_30', 'net_60', 'net_90']);
                $expenseAccount = $expenseAccounts->random();
                $vendor = $vendors->random();
                $agingProfile = $this->pickVendorAgingProfile($invoiceDate, $end);

                $vendorInvoice = VendorInvoice::create([
                    'vendor_id' => $vendor->id,
                    'program_id' => $programs->random()->id,
                    'account_id' => $expenseAccount->id,
                    'invoice_date' => $invoiceDate->toDateString(),
                    'amount' => $amount,
                    'currency' => $currency,
                    'payment_terms' => $terms,
                    'category' => $expenseAccount->name,
                    'description' => 'Vendor invoice for operational support services and supplies',
                    'vendor_reference' => 'VREF-' . strtoupper($faker->bothify('??######')),
                    'notes' => $batchTag,
                ]);

                $vendorInvoicesCreated++;

                if ($this->createVendorInvoiceJournalEntry($vendorInvoice, $accountsPayable)) {
                    $vendorInvoiceJournalEntriesCreated++;
                }

                $vendorInvoicePaymentsCreated += $this->seedVendorInvoicePayments(
                    $vendorInvoice,
                    $paymentAccounts,
                    $accountsPayable,
                    $monthEnd,
                    $agingProfile,
                    $vendorInvoiceJournalEntriesCreated
                );
            }

            $this->seedPayrollForMonth(
                $monthStart,
                $monthEnd,
                $programs,
                $staff,
                $paymentAccounts,
                $accountsPayable,
                $payrollExpenseAccount,
                $approver->id,
                $batchTag,
                $runSequence,
                $payrollRunsCreated,
                $payrollItemsCreated,
                $payrollJournalEntriesCreated
            );

            $cursor->addMonth();
        }

        $this->command->info('✓ Government demo data created successfully');
        $this->command->line('Batch: ' . $batchTag);
        $this->command->table(
            ['Dataset', 'Records'],
            [
                ['Sales (1-2 years)', number_format($salesCreated)],
                ['Customer Payments', number_format($customerPaymentsCreated)],
                ['Expenses (1-2 years)', number_format($expensesCreated)],
                ['Payment Vouchers', number_format($paymentVouchersCreated)],
                ['Vendor Invoices', number_format($vendorInvoicesCreated)],
                ['Vendor Invoice Payments', number_format($vendorInvoicePaymentsCreated)],
                ['Journal Entries (Vendor AP flow)', number_format($vendorInvoiceJournalEntriesCreated)],
                ['Payroll Runs', number_format($payrollRunsCreated)],
                ['Payroll Items', number_format($payrollItemsCreated)],
                ['Journal Entries (Payroll flow)', number_format($payrollJournalEntriesCreated)],
            ]
        );
    }

    private function ensureMinimumReferenceData(): void
    {
        $this->ensurePrograms(30);
        $this->ensureCustomers(500);
        $this->ensureVendors(250);
        $this->ensureStaff(180);
    }

    private function ensurePrograms(int $target): void
    {
        $current = Program::query()->count();
        if ($current >= $target) {
            return;
        }

        for ($index = $current + 1; $index <= $target; $index++) {
            Program::firstOrCreate(
                ['code' => 'GPRG-' . str_pad((string) $index, 3, '0', STR_PAD_LEFT)],
                [
                    'name' => 'Government Skills Program ' . $index,
                    'description' => 'Public-sector digital and financial literacy training program',
                    'start_date' => now()->subMonths(random_int(1, 24))->toDateString(),
                    'end_date' => now()->addMonths(random_int(2, 12))->toDateString(),
                    'status' => 'active',
                    'budget' => random_int(40_000_000, 250_000_000),
                ]
            );
        }
    }

    private function ensureCustomers(int $target): void
    {
        $current = Customer::query()->count();
        if ($current >= $target) {
            return;
        }

        $faker = fake();
        for ($index = $current + 1; $index <= $target; $index++) {
            Customer::create([
                'name' => 'Institution Client ' . $index,
                'student_name' => $faker->name(),
                'date_of_birth' => now()->subYears(random_int(17, 38))->subDays(random_int(1, 360))->toDateString(),
                'email' => 'client' . str_pad((string) $index, 4, '0', STR_PAD_LEFT) . '@gov-demo.local',
                'phone' => '+2567' . $faker->numerify('########'),
                'address' => $faker->address(),
                'company' => 'Public Institution ' . $faker->numberBetween(1, 60),
                'tax_id' => 'TIN-' . $faker->numerify('##########'),
                'notes' => self::DEMO_TAG,
                'is_active' => true,
            ]);
        }
    }

    private function ensureVendors(int $target): void
    {
        $current = Vendor::query()->count();
        if ($current >= $target) {
            return;
        }

        $faker = fake();
        $vendorTypes = ['supplier', 'utility', 'contractor', 'service_provider'];
        $serviceTypes = ['Training Materials', 'Transport', 'ICT Services', 'Utilities', 'Facilities'];

        for ($index = $current + 1; $index <= $target; $index++) {
            $paymentMethod = ['bank', 'mobile_money', 'cash'][array_rand(['bank', 'mobile_money', 'cash'])];

            Vendor::create([
                'name' => 'Vendor Partner ' . $index,
                'vendor_type' => $vendorTypes[array_rand($vendorTypes)],
                'service_type' => $serviceTypes[array_rand($serviceTypes)],
                'email' => 'vendor' . str_pad((string) $index, 4, '0', STR_PAD_LEFT) . '@gov-demo.local',
                'phone' => '+2567' . $faker->numerify('########'),
                'address' => $faker->address(),
                'company' => 'Vendor Company ' . $faker->numberBetween(1, 80),
                'tin' => 'TIN-' . $faker->numerify('##########'),
                'account_number' => $faker->numerify('##########'),
                'payment_method' => $paymentMethod,
                'bank_name' => $paymentMethod === 'bank' ? $faker->randomElement(['Stanbic', 'Centenary', 'DFCU', 'Absa']) : null,
                'bank_account_number' => $paymentMethod === 'bank' ? $faker->numerify('############') : null,
                'bank_account_name' => $paymentMethod === 'bank' ? 'Vendor Partner ' . $index : null,
                'mobile_money_provider' => $paymentMethod === 'mobile_money' ? $faker->randomElement(['MTN', 'Airtel']) : null,
                'mobile_money_number' => $paymentMethod === 'mobile_money' ? '+2567' . $faker->numerify('########') : null,
                'business_type' => $faker->randomElement(['company', 'individual', 'government']),
                'currency' => $this->pickCurrency(),
                'notes' => self::DEMO_TAG,
                'is_active' => true,
            ]);
        }
    }

    private function ensureStaff(int $target): void
    {
        $current = Staff::query()->count();
        if ($current >= $target) {
            return;
        }

        $faker = fake();
        $employmentTypes = ['full_time', 'part_time', 'contract', 'consultant'];

        for ($index = $current + 1; $index <= $target; $index++) {
            $employmentType = $faker->randomElement($employmentTypes);
            $paymentMethod = $faker->randomElement(['bank', 'mobile_money', 'cash']);

            Staff::create([
                'employee_number' => 'EMP-' . str_pad((string) $index, 5, '0', STR_PAD_LEFT),
                'first_name' => $faker->firstName(),
                'last_name' => $faker->lastName(),
                'email' => 'staff' . str_pad((string) $index, 5, '0', STR_PAD_LEFT) . '@gov-demo.local',
                'phone' => '+2567' . $faker->numerify('########'),
                'employment_type' => $employmentType,
                'payment_method' => $paymentMethod,
                'mobile_money_provider' => $paymentMethod === 'mobile_money' ? $faker->randomElement(['MTN', 'Airtel']) : null,
                'mobile_money_number' => $paymentMethod === 'mobile_money' ? '+2567' . $faker->numerify('########') : null,
                'bank_name' => $paymentMethod === 'bank' ? $faker->randomElement(['Stanbic', 'Centenary', 'DFCU', 'Absa']) : null,
                'bank_account' => $paymentMethod === 'bank' ? $faker->numerify('############') : null,
                'nssf_number' => 'NSSF-' . $faker->numerify('########'),
                'tin_number' => 'TIN-' . $faker->numerify('##########'),
                'base_salary' => $employmentType === 'full_time' ? random_int(1_200_000, 9_000_000) : null,
                'hourly_rate' => $employmentType !== 'full_time' ? random_int(18_000, 120_000) : null,
                'is_active' => true,
                'hire_date' => now()->subDays(random_int(120, 2200))->toDateString(),
                'notes' => self::DEMO_TAG,
            ]);
        }
    }

    private function seedCustomerPaymentsForSale(Sale $sale, $receiptAccounts, Carbon $monthEnd): int
    {
        $faker = fake();
        $paymentProfile = $this->pickPaymentProfile();

        if ($paymentProfile === 'none') {
            return 0;
        }

        $ratio = $paymentProfile === 'full' ? 1.0 : $faker->randomFloat(2, 0.25, 0.85);
        $targetTotal = round((float) $sale->amount * $ratio, 2);

        if ($targetTotal <= 0) {
            return 0;
        }

        $parts = $paymentProfile === 'full'
            ? random_int(2, 4)
            : random_int(1, 3);

        $latestDate = $sale->sale_date->copy()->addDays(90)->min($monthEnd)->min(now());
        if ($latestDate->lt($sale->sale_date)) {
            $latestDate = $sale->sale_date->copy();
        }

        $remaining = $targetTotal;
        $created = 0;

        for ($part = 1; $part <= $parts; $part++) {
            $amount = $part === $parts
                ? $remaining
                : round($remaining * $faker->randomFloat(2, 0.25, 0.65), 2);

            if ($amount <= 0) {
                continue;
            }

            CustomerPayment::create([
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'payment_account_id' => $receiptAccounts->random()->id,
                'payment_date' => Carbon::instance($faker->dateTimeBetween($sale->sale_date, $latestDate))->toDateString(),
                'amount' => $amount,
                'currency' => $sale->currency,
                'exchange_rate' => $this->rateForCurrency($sale->currency),
                'payment_method' => $faker->randomElement(['cash', 'bank_transfer', 'mobile_money', 'check']),
                'reference_number' => 'CP-' . $faker->bothify('??######'),
                'notes' => self::DEMO_TAG,
            ]);

            $remaining = round($remaining - $amount, 2);
            $created++;

            if ($remaining <= 0) {
                break;
            }
        }

        return $created;
    }

    private function seedPaymentsForExpense(Expense $expense, $paymentAccounts, Carbon $monthEnd): int
    {
        $faker = fake();
        $paymentProfile = $this->pickPaymentProfile();

        if ($paymentProfile === 'none') {
            return 0;
        }

        $total = (float) $expense->amount + (float) ($expense->charges ?? 0);
        $ratio = $paymentProfile === 'full' ? 1.0 : $faker->randomFloat(2, 0.25, 0.85);
        $targetTotal = round($total * $ratio, 2);

        if ($targetTotal <= 0) {
            return 0;
        }

        $parts = $paymentProfile === 'full' ? random_int(2, 4) : random_int(1, 3);

        $latestDate = $expense->expense_date->copy()->addDays(75)->min($monthEnd)->min(now());
        if ($latestDate->lt($expense->expense_date)) {
            $latestDate = $expense->expense_date->copy();
        }

        $remaining = $targetTotal;
        $created = 0;

        for ($part = 1; $part <= $parts; $part++) {
            $amount = $part === $parts
                ? $remaining
                : round($remaining * $faker->randomFloat(2, 0.40, 0.70), 2);

            if ($amount <= 0) {
                continue;
            }

            Payment::create([
                'expense_id' => $expense->id,
                'payment_account_id' => $paymentAccounts->random()->id,
                'payment_date' => Carbon::instance($faker->dateTimeBetween($expense->expense_date, $latestDate))->toDateString(),
                'amount' => $amount,
                'payment_method' => $faker->randomElement(['cash', 'bank_transfer', 'mobile_money', 'check']),
                'payment_reference' => 'PVREF-' . $faker->bothify('??######'),
                'notes' => self::DEMO_TAG,
            ]);

            $remaining = round($remaining - $amount, 2);
            $created++;

            if ($remaining <= 0) {
                break;
            }
        }

        return $created;
    }

    private function seedVendorInvoicePayments(
        VendorInvoice $invoice,
        $paymentAccounts,
        Account $accountsPayable,
        Carbon $monthEnd,
        array $agingProfile,
        int &$journalEntriesCreated
    ): int {
        $faker = fake();
        $paymentProfile = $agingProfile['payment_profile'];

        if ($paymentProfile === 'none') {
            return 0;
        }

        $ratio = $paymentProfile === 'full' ? 1.0 : $faker->randomFloat(2, 0.25, 0.85);
        $targetTotal = round((float) $invoice->amount * $ratio, 2);
        if ($targetTotal <= 0) {
            return 0;
        }

        $parts = $paymentProfile === 'full' ? random_int(2, 4) : random_int(1, 3);

        $latestDate = $invoice->invoice_date->copy()->addDays($agingProfile['max_delay_days'])->min($monthEnd)->min(now());
        if ($latestDate->lt($invoice->invoice_date)) {
            $latestDate = $invoice->invoice_date->copy();
        }

        $remaining = $targetTotal;
        $created = 0;

        for ($part = 1; $part <= $parts; $part++) {
            $amount = $part === $parts
                ? $remaining
                : round($remaining * $faker->randomFloat(2, 0.30, 0.70), 2);

            if ($amount <= 0) {
                continue;
            }

            $paymentDate = Carbon::instance($faker->dateTimeBetween($invoice->invoice_date, $latestDate));
            $rate = $this->rateForCurrency($invoice->currency);
            $amountBase = round($amount * $rate, 2);
            $paymentAccount = $paymentAccounts->random();

            DB::table('vendor_payments')->insert([
                'vendor_invoice_id' => $invoice->id,
                'vendor_id' => $invoice->vendor_id,
                'payment_date' => $paymentDate->toDateString(),
                'amount' => $amount,
                'currency' => $invoice->currency,
                'exchange_rate' => $rate,
                'amount_base' => $amountBase,
                'payment_method' => $faker->randomElement(['cash', 'bank_transfer', 'mobile_money', 'check']),
                'reference_number' => 'VP-' . strtoupper($faker->bothify('??######')),
                'notes' => self::DEMO_TAG,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $created++;
            $remaining = round($remaining - $amount, 2);

            if ($this->createVendorInvoicePaymentJournalEntry($invoice, $paymentAccount, $accountsPayable, $amountBase, $paymentDate)) {
                $journalEntriesCreated++;
            }

            if ($remaining <= 0) {
                break;
            }
        }

        $invoice->refresh();
        $invoice->updatePaymentStatus();

        return $created;
    }

    private function seedPayrollForMonth(
        Carbon $monthStart,
        Carbon $monthEnd,
        $programs,
        $staff,
        $paymentAccounts,
        Account $accountsPayable,
        Account $payrollExpenseAccount,
        int $approverId,
        string $batchTag,
        int &$runSequence,
        int &$runsCreated,
        int &$itemsCreated,
        int &$journalEntriesCreated
    ): void {
        $faker = fake();

        $runStatus = $this->pickPayrollRunStatus($monthEnd);
        $runNumber = sprintf('PR-%s-%06d', $monthEnd->format('Ym'), $runSequence++);
        $paymentDate = $monthEnd->copy()->addDays(random_int(1, 7))->min(now());

        $run = PayrollRun::create([
            'run_number' => $runNumber,
            'period_start' => $monthStart->toDateString(),
            'period_end' => $monthEnd->toDateString(),
            'payment_date' => $paymentDate->toDateString(),
            'status' => $runStatus,
            'approved_by' => in_array($runStatus, ['approved', 'processed', 'paid'], true) ? $approverId : null,
            'approved_at' => in_array($runStatus, ['approved', 'processed', 'paid'], true) ? now() : null,
            'notes' => $batchTag,
        ]);

        $runsCreated++;

        $headcount = min($staff->count(), random_int(45, 110));
        $selectedStaff = $staff->random($headcount);

        $grossTotal = 0.0;
        $employeeDeductionsTotal = 0.0;
        $employerNssfTotal = 0.0;
        $netPaidTotal = 0.0;

        foreach ($selectedStaff as $staffMember) {
            $hoursWorked = null;
            $classes = null;

            if ($staffMember->employment_type === 'full_time') {
                $gross = (float) ($staffMember->base_salary ?: random_int(1_200_000, 8_000_000));
            } else {
                $hoursWorked = random_int(70, 210);
                $classes = random_int(6, 36);
                $rate = (float) ($staffMember->hourly_rate ?: random_int(18_000, 100_000));
                $gross = round($hoursWorked * $rate, 2);
            }

            $bonuses = random_int(1, 100) <= 22
                ? round($gross * (random_int(3, 18) / 100), 2)
                : 0.0;
            $paye = PayrollItem::calculatePAYE($gross);
            $nssf = PayrollItem::calculateNSSF($gross);
            $otherDeductions = random_int(1, 100) <= 18
                ? random_int(8_000, 220_000)
                : 0.0;

            $net = round($gross - $paye - $nssf['employee'] - $otherDeductions + $bonuses, 2);
            if ($net < 0) {
                $net = 0;
            }

            $itemStatus = $this->pickPayrollItemStatus($runStatus);
            $paidAt = $itemStatus === 'paid'
                ? Carbon::instance($faker->dateTimeBetween($paymentDate, $paymentDate->copy()->addDays(9)->min(now())))
                : null;

            PayrollItem::create([
                'payroll_run_id' => $run->id,
                'staff_id' => $staffMember->id,
                'program_id' => $programs->random()->id,
                'hours_worked' => $hoursWorked,
                'classes_taught' => $classes,
                'gross_amount' => $gross,
                'paye_amount' => $paye,
                'nssf_employee' => $nssf['employee'],
                'nssf_employer' => $nssf['employer'],
                'other_deductions' => $otherDeductions,
                'bonuses' => $bonuses,
                'net_amount' => $net,
                'payment_status' => $itemStatus,
                'transaction_reference' => $itemStatus === 'paid' ? 'SAL-' . strtoupper($faker->bothify('??######')) : null,
                'paid_at' => $paidAt,
                'notes' => $batchTag,
            ]);

            $itemsCreated++;
            $grossTotal += $gross;
            $employeeDeductionsTotal += ($paye + $nssf['employee'] + $otherDeductions);
            $employerNssfTotal += $nssf['employer'];

            if ($itemStatus === 'paid') {
                $netPaidTotal += $net;
            }
        }

        $run->recalculateTotals();

        $payrollCost = round($grossTotal + $employerNssfTotal + $run->items()->sum('bonuses'), 2);

        if ($this->createPayrollAccrualJournalEntry($run, $payrollExpenseAccount, $accountsPayable, $payrollCost)) {
            $journalEntriesCreated++;
        }

        if ($netPaidTotal > 0 && $this->createPayrollCashOutJournalEntry($run, $accountsPayable, $paymentAccounts->random(), $netPaidTotal, 'salary_disbursement')) {
            $journalEntriesCreated++;
        }

        $statutoryAmount = round(max($payrollCost - $netPaidTotal, 0), 2);
        if ($statutoryAmount > 0 && $monthEnd->lt(now()->subDays(45))) {
            $remitted = round($statutoryAmount * (random_int(70, 100) / 100), 2);
            if ($remitted > 0 && $this->createPayrollCashOutJournalEntry($run, $accountsPayable, $paymentAccounts->random(), $remitted, 'statutory_remittance')) {
                $journalEntriesCreated++;
            }
        }
    }

    private function createPayrollAccrualJournalEntry(
        PayrollRun $run,
        Account $payrollExpenseAccount,
        Account $accountsPayable,
        float $amount
    ): bool {
        if ($amount <= 0) {
            return false;
        }

        try {
            JournalEntry::createEntry(
                [
                    'date' => $run->period_end,
                    'type' => 'expense',
                    'description' => "Payroll accrual {$run->run_number}",
                    'created_by' => $run->approved_by,
                    'status' => 'posted',
                    'posted_at' => now(),
                ],
                [
                    [
                        'account_id' => $payrollExpenseAccount->id,
                        'debit' => $amount,
                        'credit' => 0,
                        'description' => 'Monthly payroll expense accrual',
                    ],
                    [
                        'account_id' => $accountsPayable->id,
                        'debit' => 0,
                        'credit' => $amount,
                        'description' => 'Payroll liabilities accrued',
                    ],
                ]
            );

            return true;
        } catch (\Throwable $e) {
            \Log::warning('Failed payroll accrual JE seed: ' . $e->getMessage());
            return false;
        }
    }

    private function createPayrollCashOutJournalEntry(
        PayrollRun $run,
        Account $accountsPayable,
        Account $paymentAccount,
        float $amount,
        string $purpose
    ): bool {
        if ($amount <= 0) {
            return false;
        }

        $desc = $purpose === 'salary_disbursement'
            ? "Payroll disbursement {$run->run_number}"
            : "Payroll statutory remittance {$run->run_number}";

        try {
            JournalEntry::createEntry(
                [
                    'date' => $run->payment_date,
                    'type' => 'payment',
                    'description' => $desc,
                    'created_by' => $run->approved_by,
                    'status' => 'posted',
                    'posted_at' => now(),
                ],
                [
                    [
                        'account_id' => $accountsPayable->id,
                        'debit' => $amount,
                        'credit' => 0,
                        'description' => 'Reduce payroll payable',
                    ],
                    [
                        'account_id' => $paymentAccount->id,
                        'debit' => 0,
                        'credit' => $amount,
                        'description' => 'Payroll cash outflow',
                    ],
                ]
            );

            return true;
        } catch (\Throwable $e) {
            \Log::warning('Failed payroll payout JE seed: ' . $e->getMessage());
            return false;
        }
    }

    private function pickPayrollRunStatus(Carbon $monthEnd): string
    {
        if ($monthEnd->lt(now()->subMonths(2))) {
            return 'paid';
        }

        if ($monthEnd->lt(now()->subMonth())) {
            return random_int(1, 100) <= 75 ? 'processed' : 'approved';
        }

        return random_int(1, 100) <= 65 ? 'approved' : 'draft';
    }

    private function pickPayrollItemStatus(string $runStatus): string
    {
        if ($runStatus === 'paid') {
            return random_int(1, 100) <= 96 ? 'paid' : 'failed';
        }

        if ($runStatus === 'processed') {
            $roll = random_int(1, 100);
            if ($roll <= 78) {
                return 'paid';
            }

            if ($roll <= 90) {
                return 'pending';
            }

            return 'failed';
        }

        if ($runStatus === 'approved') {
            return random_int(1, 100) <= 18 ? 'paid' : 'pending';
        }

        return 'pending';
    }

    private function pickVendorAgingProfile(Carbon $invoiceDate, Carbon $asAt): array
    {
        $ageDays = $invoiceDate->diffInDays($asAt);

        if ($ageDays <= 45) {
            return $this->agingProfile('current', [80, 17, 3], 35);
        }

        if ($ageDays <= 90) {
            return $this->agingProfile('30_days', [55, 35, 10], 70);
        }

        if ($ageDays <= 150) {
            return $this->agingProfile('60_days', [35, 45, 20], 120);
        }

        return $this->agingProfile('90_plus_days', [15, 45, 40], 200);
    }

    private function agingProfile(string $bucket, array $weights, int $maxDelayDays): array
    {
        $roll = random_int(1, 100);

        if ($roll <= $weights[0]) {
            $profile = 'full';
        } elseif ($roll <= ($weights[0] + $weights[1])) {
            $profile = 'partial';
        } else {
            $profile = 'none';
        }

        return [
            'bucket' => $bucket,
            'payment_profile' => $profile,
            'max_delay_days' => $maxDelayDays,
        ];
    }

    private function createVendorInvoiceJournalEntry(VendorInvoice $invoice, Account $accountsPayable): bool
    {
        $amountBase = (float) ($invoice->amount_base ?: ((float) $invoice->amount * (float) ($invoice->exchange_rate ?: 1)));
        if ($amountBase <= 0) {
            return false;
        }

        try {
            JournalEntry::createEntry(
                [
                    'date' => $invoice->invoice_date,
                    'type' => 'expense',
                    'description' => "Vendor invoice {$invoice->invoice_number} - " . ($invoice->vendor?->name ?? 'Unknown Vendor'),
                    'created_by' => 1,
                    'status' => 'posted',
                    'posted_at' => now(),
                ],
                [
                    [
                        'account_id' => $invoice->account_id,
                        'debit' => $amountBase,
                        'credit' => 0,
                        'description' => $invoice->description ?: 'Vendor invoice expense recognition',
                    ],
                    [
                        'account_id' => $accountsPayable->id,
                        'debit' => 0,
                        'credit' => $amountBase,
                        'description' => 'Accounts Payable - ' . ($invoice->vendor?->name ?? 'Vendor'),
                    ],
                ]
            );

            return true;
        } catch (\Throwable $e) {
            \Log::warning('Failed vendor invoice JE seed: ' . $e->getMessage());
            return false;
        }
    }

    private function createVendorInvoicePaymentJournalEntry(
        VendorInvoice $invoice,
        Account $paymentAccount,
        Account $accountsPayable,
        float $amountBase,
        Carbon $paymentDate
    ): bool {
        if ($amountBase <= 0) {
            return false;
        }

        try {
            JournalEntry::createEntry(
                [
                    'date' => $paymentDate,
                    'type' => 'payment',
                    'description' => "Vendor payment for {$invoice->invoice_number} - " . ($invoice->vendor?->name ?? 'Unknown Vendor'),
                    'created_by' => 1,
                    'status' => 'posted',
                    'posted_at' => now(),
                ],
                [
                    [
                        'account_id' => $accountsPayable->id,
                        'debit' => $amountBase,
                        'credit' => 0,
                        'description' => 'Settle AP - ' . ($invoice->vendor?->name ?? 'Vendor'),
                    ],
                    [
                        'account_id' => $paymentAccount->id,
                        'debit' => 0,
                        'credit' => $amountBase,
                        'description' => 'Cash/Bank payment',
                    ],
                ]
            );

            return true;
        } catch (\Throwable $e) {
            \Log::warning('Failed vendor payment JE seed: ' . $e->getMessage());
            return false;
        }
    }

    private function pickSalesDocumentType(): string
    {
        $roll = random_int(1, 100);

        if ($roll <= 70) {
            return Sale::DOC_INVOICE;
        }

        if ($roll <= 82) {
            return Sale::DOC_TILL_SALE;
        }

        if ($roll <= 90) {
            return Sale::DOC_SALES_ORDER;
        }

        if ($roll <= 96) {
            return Sale::DOC_QUOTATION;
        }

        return Sale::DOC_ESTIMATE;
    }

    private function pickPaymentProfile(): string
    {
        $roll = random_int(1, 100);

        if ($roll <= 68) {
            return 'full';
        }

        if ($roll <= 93) {
            return 'partial';
        }

        return 'none';
    }

    private function pickCurrency(): string
    {
        $roll = random_int(1, 100);

        if ($roll <= 82) {
            return 'UGX';
        }

        if ($roll <= 94) {
            return 'USD';
        }

        return 'EUR';
    }

    private function randomSaleAmount(string $currency): float
    {
        return match ($currency) {
            'USD' => random_int(120, 6500),
            'EUR' => random_int(100, 5200),
            default => random_int(350_000, 18_000_000),
        };
    }

    private function randomExpenseAmount(string $currency): float
    {
        return match ($currency) {
            'USD' => random_int(80, 4200),
            'EUR' => random_int(70, 3600),
            default => random_int(180_000, 9_500_000),
        };
    }

    private function randomCharges(string $currency): float
    {
        $roll = random_int(1, 100);
        if ($roll <= 72) {
            return 0;
        }

        return match ($currency) {
            'USD' => random_int(2, 90),
            'EUR' => random_int(2, 75),
            default => random_int(5_000, 95_000),
        };
    }

    private function discountForAmount(float $amount, string $currency): float
    {
        $roll = random_int(1, 100);
        if ($roll > 35) {
            return 0;
        }

        $discount = $amount * (random_int(1, 10) / 100);
        return round($discount, $currency === 'UGX' ? 0 : 2);
    }

    private function taxForAmount(float $amount, string $currency): float
    {
        $roll = random_int(1, 100);
        if ($roll > 80) {
            return 0;
        }

        $tax = $amount * 0.18;
        return round($tax, $currency === 'UGX' ? 0 : 2);
    }

    private function rateForCurrency(string $currency): float
    {
        if ($currency === 'UGX') {
            return 1.0;
        }

        $rate = ExchangeRate::query()
            ->where('from_currency', $currency)
            ->where('to_currency', 'UGX')
            ->latest('effective_date')
            ->value('rate');

        if ($rate) {
            return (float) $rate;
        }

        return match ($currency) {
            'USD' => 3700.0,
            'EUR' => 4000.0,
            default => 1.0,
        };
    }
}
