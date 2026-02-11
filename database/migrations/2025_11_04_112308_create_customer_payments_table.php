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
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('UGX');
            $table->decimal('exchange_rate', 10, 6)->default(1);
            $table->decimal('amount_base', 15, 2)->nullable();
            $table->string('payment_method', 50)->nullable(); // cash, bank_transfer, mobile_money, check
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Migrate existing payment data from sales table
        DB::statement("
            INSERT INTO customer_payments (sale_id, customer_id, payment_date, amount, currency, exchange_rate, amount_base, reference_number, created_at, updated_at)
            SELECT 
                id,
                customer_id,
                sale_date,
                amount_paid,
                currency,
                COALESCE(exchange_rate, 1),
                amount_paid * COALESCE(exchange_rate, 1),
                reference_number,
                created_at,
                updated_at
            FROM sales
            WHERE amount_paid > 0
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_payments');
    }
};
