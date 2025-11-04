<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Currencies table
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique(); // USD, UGX, EUR
            $table->string('name');
            $table->string('symbol', 10);
            $table->boolean('is_base')->default(false); // Base currency for conversions
            $table->boolean('is_active')->default(true);
            $table->integer('decimal_places')->default(2);
            $table->timestamps();
        });

        // Exchange rates table
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->decimal('rate', 18, 6);
            $table->date('effective_date');
            $table->string('source')->nullable(); // API source
            $table->timestamps();
            
            $table->index(['from_currency', 'to_currency', 'effective_date']);
            $table->unique(['from_currency', 'to_currency', 'effective_date']);
        });

        // Add currency fields to sales table
        Schema::table('sales', function (Blueprint $table) {
            $table->string('currency', 3)->default('UGX')->after('amount');
            $table->decimal('amount_base', 15, 2)->nullable()->after('currency'); // Amount in base currency
            $table->decimal('exchange_rate', 18, 6)->nullable()->after('amount_base');
        });

        // Add currency fields to expenses table
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('currency', 3)->default('UGX')->after('amount');
            $table->decimal('amount_base', 15, 2)->nullable()->after('currency'); // Amount in base currency
            $table->decimal('exchange_rate', 18, 6)->nullable()->after('amount_base');
        });

        // Seed initial currencies
        DB::table('currencies')->insert([
            [
                'code' => 'UGX',
                'name' => 'Ugandan Shilling',
                'symbol' => 'UGX',
                'is_base' => true,
                'is_active' => true,
                'decimal_places' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'is_base' => false,
                'is_active' => true,
                'decimal_places' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'EUR',
                'name' => 'Euro',
                'symbol' => '€',
                'is_base' => false,
                'is_active' => true,
                'decimal_places' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Seed initial exchange rates (as of Nov 2025)
        DB::table('exchange_rates')->insert([
            [
                'from_currency' => 'USD',
                'to_currency' => 'UGX',
                'rate' => 3700.00,
                'effective_date' => now()->toDateString(),
                'source' => 'manual',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'from_currency' => 'EUR',
                'to_currency' => 'UGX',
                'rate' => 4000.00,
                'effective_date' => now()->toDateString(),
                'source' => 'manual',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'from_currency' => 'UGX',
                'to_currency' => 'USD',
                'rate' => 0.00027027,
                'effective_date' => now()->toDateString(),
                'source' => 'manual',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'from_currency' => 'UGX',
                'to_currency' => 'EUR',
                'rate' => 0.00025,
                'effective_date' => now()->toDateString(),
                'source' => 'manual',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['currency', 'amount_base', 'exchange_rate']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['currency', 'amount_base', 'exchange_rate']);
        });

        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('currencies');
    }
};
