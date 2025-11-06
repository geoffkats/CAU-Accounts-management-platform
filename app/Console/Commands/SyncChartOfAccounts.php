<?php

namespace App\Console\Commands;

use App\Models\Account;
use Illuminate\Console\Command;

class SyncChartOfAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounts:sync {--dry-run : Preview changes without applying them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync production chart of accounts with standard seeder structure';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made to the database');
            $this->newLine();
        }

        // Standard chart of accounts from seeder
        $standardAccounts = $this->getStandardAccounts();

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        $this->info('📊 Syncing Chart of Accounts...');
        $this->newLine();

        $progressBar = $this->output->createProgressBar(count($standardAccounts));
        $progressBar->start();

        foreach ($standardAccounts as $accountData) {
            $progressBar->advance();

            $existing = Account::where('code', $accountData['code'])->first();

            if (!$existing) {
                // Account doesn't exist, create it
                if (!$dryRun) {
                    try {
                        Account::create($accountData);
                        $created++;
                    } catch (\Exception $e) {
                        $this->newLine();
                        $this->error("Failed to create {$accountData['code']}: {$e->getMessage()}");
                        $errors++;
                    }
                } else {
                    $created++;
                }
            } else {
                // Account exists, check if update needed
                $needsUpdate = false;
                $changes = [];

                if ($existing->name !== $accountData['name']) {
                    $needsUpdate = true;
                    $changes[] = "name: '{$existing->name}' → '{$accountData['name']}'";
                }

                if ($existing->type !== $accountData['type']) {
                    $needsUpdate = true;
                    $changes[] = "type: '{$existing->type}' → '{$accountData['type']}'";
                }

                if ($existing->description !== $accountData['description']) {
                    $needsUpdate = true;
                    $changes[] = "description updated";
                }

                if ($needsUpdate) {
                    if (!$dryRun) {
                        try {
                            $existing->update([
                                'name' => $accountData['name'],
                                'type' => $accountData['type'],
                                'description' => $accountData['description'],
                            ]);
                            $updated++;
                        } catch (\Exception $e) {
                            $this->newLine();
                            $this->error("Failed to update {$accountData['code']}: {$e->getMessage()}");
                            $errors++;
                        }
                    } else {
                        $updated++;
                        $this->newLine();
                        $this->warn("Would update {$accountData['code']} - {$existing->name}:");
                        foreach ($changes as $change) {
                            $this->line("  • $change");
                        }
                    }
                } else {
                    $skipped++;
                }
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('✅ Sync Complete!');
        $this->newLine();
        $this->table(
            ['Status', 'Count'],
            [
                ['Created', $created],
                ['Updated', $updated],
                ['Unchanged', $skipped],
                ['Errors', $errors],
            ]
        );

        if ($dryRun && ($created > 0 || $updated > 0)) {
            $this->newLine();
            $this->warn('⚠️  This was a dry run. Run without --dry-run to apply changes.');
        }

        if (!$dryRun && ($created > 0 || $updated > 0)) {
            $this->newLine();
            $this->info('💡 Changes applied successfully. Review your chart of accounts.');
        }

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Get standard chart of accounts matching seeder structure
     */
    private function getStandardAccounts(): array
    {
        return [
            // Core Assets (1000-1999)
            ['code' => '1000', 'name' => 'Cash on Hand', 'type' => 'asset', 'description' => 'Physical cash in office', 'is_active' => true],
            ['code' => '1100', 'name' => 'Bank Account - Main', 'type' => 'asset', 'description' => 'Primary bank account', 'is_active' => true],
            ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'description' => 'Money owed by customers/students', 'is_active' => true],

            // Liabilities (2000-2999)
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability', 'description' => 'Money owed to vendors', 'is_active' => true],
            ['code' => '2100', 'name' => 'Loans Payable', 'type' => 'liability', 'description' => 'Outstanding loans and borrowings', 'is_active' => true],

            // Equity (3000-3999)
            ['code' => '3000', 'name' => 'Owner\'s Equity', 'type' => 'equity', 'description' => 'Owner\'s capital investment', 'is_active' => true],
            ['code' => '3100', 'name' => 'Retained Earnings', 'type' => 'equity', 'description' => 'Accumulated profits/losses', 'is_active' => true],

            // Income (4000-4999)
            ['code' => '4000', 'name' => 'Program Fees & Tuition', 'type' => 'income', 'description' => 'Revenue from educational programs', 'is_active' => true],
            ['code' => '4100', 'name' => 'Donations & Contributions', 'type' => 'income', 'description' => 'Donation income from sponsors', 'is_active' => true],
            ['code' => '4200', 'name' => 'Grants & Funding', 'type' => 'income', 'description' => 'Grant income from organizations', 'is_active' => true],

            // 5000 – Administrative Expenses
            ['code' => '5000', 'name' => 'Office Rent', 'type' => 'expense', 'description' => 'Monthly office rental expenses', 'is_active' => true],
            ['code' => '5001', 'name' => 'Utilities', 'type' => 'expense', 'description' => 'Electricity, water, and other utilities', 'is_active' => true],
            ['code' => '5002', 'name' => 'Internet & Communication', 'type' => 'expense', 'description' => 'Internet, phone, and communication costs', 'is_active' => true],
            ['code' => '5003', 'name' => 'Office Supplies & Stationery', 'type' => 'expense', 'description' => 'Pens, paper, and office consumables', 'is_active' => true],
            ['code' => '5004', 'name' => 'Printing & Photocopying', 'type' => 'expense', 'description' => 'Printing and copying services', 'is_active' => true],
            ['code' => '5005', 'name' => 'Repairs & Maintenance (General)', 'type' => 'expense', 'description' => 'General repairs and maintenance', 'is_active' => true],
            ['code' => '5006', 'name' => 'Cleaning & Sanitation', 'type' => 'expense', 'description' => 'Cleaning services and supplies', 'is_active' => true],
            ['code' => '5007', 'name' => 'Insurance', 'type' => 'expense', 'description' => 'Insurance premiums', 'is_active' => true],
            ['code' => '5008', 'name' => 'Licenses & Permits', 'type' => 'expense', 'description' => 'Business licenses and permits', 'is_active' => true],
            ['code' => '5009', 'name' => 'Bank Charges', 'type' => 'expense', 'description' => 'Bank fees and charges', 'is_active' => true],

            // 5100 – Staff & Facilitator Costs
            ['code' => '5100', 'name' => 'Salaries & Wages', 'type' => 'expense', 'description' => 'Employee salaries and wages', 'is_active' => true],
            ['code' => '5101', 'name' => 'Facilitator Payments', 'type' => 'expense', 'description' => 'Payments to trainers and facilitators', 'is_active' => true],
            ['code' => '5102', 'name' => 'NSSF & PAYE Contributions', 'type' => 'expense', 'description' => 'Statutory employee contributions', 'is_active' => true],
            ['code' => '5103', 'name' => 'Staff Welfare', 'type' => 'expense', 'description' => 'Staff welfare and benefits', 'is_active' => true],
            ['code' => '5104', 'name' => 'Staff Training & Capacity Building', 'type' => 'expense', 'description' => 'Employee training and development', 'is_active' => true],
            ['code' => '5105', 'name' => 'Staff Transport Allowance', 'type' => 'expense', 'description' => 'Staff transportation allowances', 'is_active' => true],
            ['code' => '5106', 'name' => 'Team Building & Staff Retreats', 'type' => 'expense', 'description' => 'Team building activities and retreats', 'is_active' => true],

            // 5200 – Program Expenses
            ['code' => '5200', 'name' => 'Training Materials', 'type' => 'expense', 'description' => 'Educational and training materials', 'is_active' => true],
            ['code' => '5201', 'name' => 'Software & Licenses', 'type' => 'expense', 'description' => 'Software licenses for programs', 'is_active' => true],
            ['code' => '5202', 'name' => 'Venue & Equipment Hire', 'type' => 'expense', 'description' => 'Venue and equipment rental', 'is_active' => true],
            ['code' => '5203', 'name' => 'Meals & Refreshments (Students)', 'type' => 'expense', 'description' => 'Student meals and refreshments', 'is_active' => true],
            ['code' => '5204', 'name' => 'Certificates & Printing', 'type' => 'expense', 'description' => 'Certificates and program printing', 'is_active' => true],
            ['code' => '5205', 'name' => 'Prizes & Awards', 'type' => 'expense', 'description' => 'Student prizes and awards', 'is_active' => true],
            ['code' => '5206', 'name' => 'Program Logistics', 'type' => 'expense', 'description' => 'Program logistics and coordination', 'is_active' => true],
            ['code' => '5207', 'name' => 'Internet for Labs', 'type' => 'expense', 'description' => 'Internet connectivity for labs', 'is_active' => true],
            ['code' => '5208', 'name' => 'Curriculum Development', 'type' => 'expense', 'description' => 'Curriculum design and development', 'is_active' => true],

            // 5300 – Marketing & Outreach
            ['code' => '5300', 'name' => 'Advertising & Promotions', 'type' => 'expense', 'description' => 'Advertising and promotional activities', 'is_active' => true],
            ['code' => '5301', 'name' => 'Social Media Marketing', 'type' => 'expense', 'description' => 'Social media advertising and content', 'is_active' => true],
            ['code' => '5302', 'name' => 'Public Relations & Branding', 'type' => 'expense', 'description' => 'PR and brand development', 'is_active' => true],
            ['code' => '5303', 'name' => 'Photography & Videography', 'type' => 'expense', 'description' => 'Photography and video services', 'is_active' => true],
            ['code' => '5304', 'name' => 'Website Hosting & Maintenance', 'type' => 'expense', 'description' => 'Website hosting and maintenance', 'is_active' => true],
            ['code' => '5305', 'name' => 'School Outreach & Engagements', 'type' => 'expense', 'description' => 'School visits and outreach programs', 'is_active' => true],

            // 5400 – Transport & Travel
            ['code' => '5400', 'name' => 'Fuel & Lubricants', 'type' => 'expense', 'description' => 'Vehicle fuel and lubricants', 'is_active' => true],
            ['code' => '5401', 'name' => 'Vehicle Repairs & Maintenance', 'type' => 'expense', 'description' => 'Vehicle repairs and servicing', 'is_active' => true],
            ['code' => '5402', 'name' => 'Driver Allowances', 'type' => 'expense', 'description' => 'Driver allowances and wages', 'is_active' => true],
            ['code' => '5403', 'name' => 'Transport for Events', 'type' => 'expense', 'description' => 'Transportation for events and activities', 'is_active' => true],
            ['code' => '5404', 'name' => 'Travel & Accommodation', 'type' => 'expense', 'description' => 'Travel and accommodation expenses', 'is_active' => true],

            // 5500 – ICT & Technical Infrastructure
            ['code' => '5500', 'name' => 'Computer & Equipment Maintenance', 'type' => 'expense', 'description' => 'Computer repairs and maintenance', 'is_active' => true],
            ['code' => '5501', 'name' => 'Software Subscriptions', 'type' => 'expense', 'description' => 'Software subscriptions and licenses', 'is_active' => true],
            ['code' => '5502', 'name' => 'Website & System Development', 'type' => 'expense', 'description' => 'Website and system development', 'is_active' => true],
            ['code' => '5503', 'name' => 'Hosting & Cloud Services', 'type' => 'expense', 'description' => 'Cloud hosting and services', 'is_active' => true],
            ['code' => '5504', 'name' => 'Internet Devices & Accessories', 'type' => 'expense', 'description' => 'Modems, routers, and accessories', 'is_active' => true],

            // 5600 – Events & Competitions
            ['code' => '5600', 'name' => 'Event Coordination', 'type' => 'expense', 'description' => 'Event planning and coordination', 'is_active' => true],
            ['code' => '5601', 'name' => 'Stage & Sound', 'type' => 'expense', 'description' => 'Stage setup and sound systems', 'is_active' => true],
            ['code' => '5602', 'name' => 'Decorations & Branding', 'type' => 'expense', 'description' => 'Event decorations and branding', 'is_active' => true],
            ['code' => '5603', 'name' => 'Judges\' & Jury Allowances', 'type' => 'expense', 'description' => 'Judges and jury payments', 'is_active' => true],
            ['code' => '5604', 'name' => 'Media Coverage', 'type' => 'expense', 'description' => 'Media coverage and publicity', 'is_active' => true],
            ['code' => '5605', 'name' => 'Refreshments', 'type' => 'expense', 'description' => 'Event refreshments and catering', 'is_active' => true],
            ['code' => '5606', 'name' => 'Guest Tokens & Gifts', 'type' => 'expense', 'description' => 'Guest gifts and tokens of appreciation', 'is_active' => true],

            // 5700 – Professional Services
            ['code' => '5700', 'name' => 'Audit & Accounting Fees', 'type' => 'expense', 'description' => 'External audit and accounting services', 'is_active' => true],
            ['code' => '5701', 'name' => 'Consultancy Fees', 'type' => 'expense', 'description' => 'Consultant and advisory fees', 'is_active' => true],
            ['code' => '5702', 'name' => 'Legal Fees', 'type' => 'expense', 'description' => 'Legal services and counsel', 'is_active' => true],
            ['code' => '5703', 'name' => 'Interest Expense', 'type' => 'expense', 'description' => 'Interest on loans and financing', 'is_active' => true],
            ['code' => '5704', 'name' => 'Exchange / Transaction Charges', 'type' => 'expense', 'description' => 'Foreign exchange and transaction fees', 'is_active' => true],

            // 5800 – Asset & Depreciation
            ['code' => '5800', 'name' => 'Depreciation Expense', 'type' => 'expense', 'description' => 'Asset depreciation charges', 'is_active' => true],
            ['code' => '5801', 'name' => 'Asset Maintenance', 'type' => 'expense', 'description' => 'Fixed asset maintenance and repairs', 'is_active' => true],
            ['code' => '5802', 'name' => 'Asset Tagging & Inventory', 'type' => 'expense', 'description' => 'Asset tracking and inventory management', 'is_active' => true],

            // 5900 – Taxes & Statutory Payments
            ['code' => '5900', 'name' => 'Corporate Taxes', 'type' => 'expense', 'description' => 'Corporate income tax', 'is_active' => true],
            ['code' => '5901', 'name' => 'Withholding Taxes', 'type' => 'expense', 'description' => 'Withholding tax payments', 'is_active' => true],
            ['code' => '5902', 'name' => 'Trading License / Local Taxes', 'type' => 'expense', 'description' => 'Trading licenses and local government taxes', 'is_active' => true],
            ['code' => '5903', 'name' => 'NSSF Employer Contribution', 'type' => 'expense', 'description' => 'Employer NSSF contributions', 'is_active' => true],

            // 6000 – Miscellaneous
            ['code' => '6000', 'name' => 'Donations & CSR', 'type' => 'expense', 'description' => 'Corporate social responsibility and donations', 'is_active' => true],
            ['code' => '6001', 'name' => 'Bad Debts / Write-offs', 'type' => 'expense', 'description' => 'Bad debts and write-offs', 'is_active' => true],
            ['code' => '6002', 'name' => 'Miscellaneous Expenses', 'type' => 'expense', 'description' => 'Other miscellaneous expenses', 'is_active' => true],
            ['code' => '6003', 'name' => 'Exchange Rate Differences', 'type' => 'expense', 'description' => 'Foreign exchange gains/losses', 'is_active' => true],
        ];
    }
}
