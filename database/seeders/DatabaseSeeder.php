<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@codeacademy.ug'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        // Create Accountant User
        $accountant = User::firstOrCreate(
            ['email' => 'accountant@codeacademy.ug'],
            [
                'name' => 'Accountant User',
                'password' => bcrypt('password'),
                'role' => 'accountant',
            ]
        );

        // Create Company Settings
        \App\Models\CompanySetting::firstOrCreate(
            ['company_email' => 'info@codeacademy.ug'],
            [
                'company_name' => 'Code Academy Uganda',
                'company_address' => 'Kampala, Uganda',
                'company_phone' => '+256-XXX-XXXXXX',
                'company_website' => 'https://codeacademy.ug',
                'currency' => 'UGX',
                'currency_symbol' => 'UGX',
                'fiscal_year_start' => now()->startOfYear(),
                'fiscal_year_end' => now()->endOfYear(),
                'timezone' => 'Africa/Kampala',
                'date_format' => 'Y-m-d',
            ]
        );

        // Create Chart of Accounts - Core Accounts (Assets, Liabilities, Equity, Income)
        $coreAccounts = [
            // Assets (1000-1999)
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
        ];

        foreach ($coreAccounts as $account) {
            \App\Models\Account::updateOrCreate(
                ['code' => $account['code']],
                $account
            );
        }

        // Create sample Programs
        $programs = [
            [
                'name' => 'Introduction to Web Development',
                'code' => 'WEB101',
                'description' => 'Beginner-friendly web development course covering HTML, CSS, and JavaScript fundamentals',
                'start_date' => now()->subMonths(2),
                'end_date' => now()->addMonths(4),
                'status' => 'active',
            ],
            [
                'name' => 'Python for Data Science',
                'code' => 'PY201',
                'description' => 'Learn Python programming with focus on data analysis and visualization',
                'start_date' => now()->subMonth(),
                'end_date' => now()->addMonths(5),
                'status' => 'active',
            ],
        ];

        foreach ($programs as $programData) {
            \App\Models\Program::firstOrCreate(
                ['code' => $programData['code']],
                $programData
            );
        }

        // Create sample Customers
        $customers = [
            [
                'name' => 'Kampala District Education',
                'email' => 'education@kampala.go.ug',
                'phone' => '+256-700-123-456',
                'address' => 'City Hall, Kampala',
                'company' => 'Kampala District',
            ],
            [
                'name' => 'Tech Hub Uganda',
                'email' => 'info@techhub.ug',
                'phone' => '+256-700-789-012',
                'address' => 'Innovation Center, Nakawa',
                'company' => 'Tech Hub Uganda',
            ],
        ];

        foreach ($customers as $customerData) {
            \App\Models\Customer::firstOrCreate(
                ['email' => $customerData['email']],
                $customerData
            );
        }

        // Create sample Vendors
        $vendors = [
            [
                'name' => 'Office Supplies Ltd',
                'email' => 'sales@officesupplies.ug',
                'phone' => '+256-700-111-222',
                'address' => 'Industrial Area, Kampala',
                'company' => 'Office Supplies Ltd',
            ],
            [
                'name' => 'Tech Equipment Co',
                'email' => 'orders@techequipment.ug',
                'phone' => '+256-700-333-444',
                'address' => 'Ntinda, Kampala',
                'company' => 'Tech Equipment Company',
            ],
        ];

        foreach ($vendors as $vendorData) {
            \App\Models\Vendor::firstOrCreate(
                ['email' => $vendorData['email']],
                $vendorData
            );
        }

        // Call additional seeders in proper order
        $this->call([
            ExpenseAccountsSeeder::class,      // Professional expense accounts (5000-6003)
            AssetSeeder::class,                // Asset categories and sample assets
            BudgetSeeder::class,               // Program budgets
            SalesExpensesSeeder::class,        // Sample sales and expenses
        ]);

        $this->command->info('✓ Database seeded successfully with comprehensive accounting data!');
        $this->command->info('✓ Chart of Accounts: Core accounts + 65 professional expense accounts');
        $this->command->info('✓ Sample Programs, Customers, Vendors created');
        $this->command->info('✓ Asset categories and sample assets created');
        $this->command->info('✓ Program budgets created');
        $this->command->info('✓ Sample sales and expenses with journal entries created');
    }
}
