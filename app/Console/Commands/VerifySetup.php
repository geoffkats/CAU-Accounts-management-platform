<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Account;
use App\Models\Currency;
use App\Models\CompanySetting;
use App\Models\Program;
use App\Models\Customer;
use App\Models\Vendor;
use Illuminate\Console\Command;

class VerifySetup extends Command
{
    protected $signature = 'setup:verify';
    protected $description = 'Verify that all required data has been seeded';

    public function handle(): int
    {
        $this->info('Verifying database setup...');
        $this->newLine();

        // Check Users
        $userCount = User::count();
        $adminExists = User::where('email', 'admin@codeacademy.ug')->exists();
        $accountantExists = User::where('email', 'accountant@codeacademy.ug')->exists();
        
        $this->info("Users: {$userCount} total");
        $this->line("  - Admin user: " . ($adminExists ? '✓ EXISTS' : '✗ MISSING'));
        $this->line("  - Accountant user: " . ($accountantExists ? '✓ EXISTS' : '✗ MISSING'));
        
        if (!$adminExists || !$accountantExists) {
            $this->newLine();
            $this->warn('⚠️  Default users missing! Run: php artisan users:create-defaults');
        }
        $this->newLine();

        // Check Company Settings
        $settingsExists = CompanySetting::exists();
        $this->info("Company Settings: " . ($settingsExists ? '✓ EXISTS' : '✗ MISSING'));
        if ($settingsExists) {
            $settings = CompanySetting::first();
            $this->line("  - Company: {$settings->company_name}");
            $this->line("  - Currency: {$settings->currency}");
        }
        $this->newLine();

        // Check Currencies
        $currencyCount = Currency::count();
        $ugxExists = Currency::where('code', 'UGX')->exists();
        $usdExists = Currency::where('code', 'USD')->exists();
        
        $this->info("Currencies: {$currencyCount} total");
        $this->line("  - UGX (base): " . ($ugxExists ? '✓ EXISTS' : '✗ MISSING'));
        $this->line("  - USD: " . ($usdExists ? '✓ EXISTS' : '✗ MISSING'));
        $this->newLine();

        // Check Chart of Accounts
        $accountCount = Account::count();
        $cashExists = Account::where('code', '1000')->exists();
        $bankExists = Account::where('code', '1100')->exists();
        $arExists = Account::where('code', '1200')->exists();
        $apExists = Account::where('code', '2000')->exists();
        
        $this->info("Chart of Accounts: {$accountCount} total");
        $this->line("  - Cash (1000): " . ($cashExists ? '✓ EXISTS' : '✗ MISSING'));
        $this->line("  - Bank (1100): " . ($bankExists ? '✓ EXISTS' : '✗ MISSING'));
        $this->line("  - Accounts Receivable (1200): " . ($arExists ? '✓ EXISTS' : '✗ MISSING'));
        $this->line("  - Accounts Payable (2000): " . ($apExists ? '✓ EXISTS' : '✗ MISSING'));
        $this->newLine();

        // Check Sample Data
        $programCount = Program::count();
        $customerCount = Customer::count();
        $vendorCount = Vendor::count();
        
        $this->info("Sample Data:");
        $this->line("  - Programs: {$programCount}");
        $this->line("  - Customers: {$customerCount}");
        $this->line("  - Vendors: {$vendorCount}");
        $this->newLine();

        // Summary
        $allGood = $adminExists && $accountantExists && $settingsExists && 
                   $ugxExists && $cashExists && $bankExists && $arExists && $apExists;

        if ($allGood) {
            $this->info('✓ Setup verification complete - all required data exists!');
            $this->newLine();
            $this->table(
                ['Email', 'Password', 'Role'],
                [
                    ['admin@codeacademy.ug', 'password', 'admin'],
                    ['accountant@codeacademy.ug', 'password', 'accountant'],
                ]
            );
            $this->warn('⚠️  IMPORTANT: Change these default passwords after first login!');
        } else {
            $this->error('✗ Setup incomplete - some required data is missing!');
            $this->newLine();
            $this->warn('Run: php artisan db:seed --force');
            if (!$adminExists || !$accountantExists) {
                $this->warn('Or run: php artisan users:create-defaults');
            }
        }

        return $allGood ? Command::SUCCESS : Command::FAILURE;
    }
}
