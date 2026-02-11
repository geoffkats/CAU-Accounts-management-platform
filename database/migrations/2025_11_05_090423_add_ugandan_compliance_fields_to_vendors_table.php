<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            // Vendor classification
            $table->string('vendor_type')->after('name')->nullable(); // utility, supplier, contractor, government
            
            // Utility-specific (UMEME, NWSC, etc.)
            $table->string('account_number')->nullable(); // Account/Meter number for utilities
            
            // Payment information
            $table->string('payment_method')->nullable(); // bank, mobile_money, cash
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('mobile_money_provider')->nullable(); // MTN, Airtel
            $table->string('mobile_money_number')->nullable();
            
            // Tax compliance (already has tax_id, rename for clarity)
            $table->renameColumn('tax_id', 'tin');
            
            // Business details
            $table->string('business_type')->nullable(); // individual, company, government
            $table->string('currency')->default('UGX'); // UGX, USD, etc.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'vendor_type',
                'account_number',
                'payment_method',
                'bank_name',
                'bank_account_number',
                'bank_account_name',
                'mobile_money_provider',
                'mobile_money_number',
                'business_type',
                'currency',
            ]);
            
            $table->renameColumn('tin', 'tax_id');
        });
    }
};
