<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Account;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure all required accounts exist for proper journal entry posting
        $accounts = [
            // Mobile Money Accounts
            ['code' => '1150', 'name' => 'Airtel Money', 'type' => 'asset', 'category' => 'short_term'],
            ['code' => '1160', 'name' => 'MTN Mobile Money (Momo)', 'type' => 'asset', 'category' => 'short_term'],
            
            // Discount Accounts
            ['code' => '5100', 'name' => 'Discounts Allowed', 'type' => 'expense', 'category' => null],
            ['code' => '5200', 'name' => 'Discounts Received', 'type' => 'expense', 'category' => null],
            
            // Charges Account
            ['code' => '5500', 'name' => 'Charges & Bank Fees', 'type' => 'expense', 'category' => null],
            
            // Tax Account
            ['code' => '2400', 'name' => 'Tax Payable', 'type' => 'liability', 'category' => 'short_term'],
        ];

        foreach ($accounts as $account) {
            Account::firstOrCreate(
                ['code' => $account['code']],
                array_merge($account, ['is_active' => true])
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration only creates accounts, no need to reverse
    }
};
