<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'due_date')) {
                $table->date('due_date')->nullable()->after('sale_date');
            }
            if (!Schema::hasColumn('sales', 'validity_date')) {
                $table->date('validity_date')->nullable()->after('due_date');
            }
            if (!Schema::hasColumn('sales', 'delivery_date')) {
                $table->date('delivery_date')->nullable()->after('validity_date');
            }
            if (!Schema::hasColumn('sales', 'order_status')) {
                $table->string('order_status', 30)->nullable()->after('delivery_date');
            }
            if (!Schema::hasColumn('sales', 'discount_amount')) {
                $table->decimal('discount_amount', 15, 2)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('sales', 'tax_amount')) {
                $table->decimal('tax_amount', 15, 2)->nullable()->after('discount_amount');
            }
            if (!Schema::hasColumn('sales', 'terms_conditions')) {
                $table->text('terms_conditions')->nullable()->after('description');
            }
            if (!Schema::hasColumn('sales', 'receipt_number')) {
                $table->string('receipt_number', 50)->nullable()->after('invoice_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'receipt_number')) {
                $table->dropColumn('receipt_number');
            }
            if (Schema::hasColumn('sales', 'terms_conditions')) {
                $table->dropColumn('terms_conditions');
            }
            if (Schema::hasColumn('sales', 'tax_amount')) {
                $table->dropColumn('tax_amount');
            }
            if (Schema::hasColumn('sales', 'discount_amount')) {
                $table->dropColumn('discount_amount');
            }
            if (Schema::hasColumn('sales', 'order_status')) {
                $table->dropColumn('order_status');
            }
            if (Schema::hasColumn('sales', 'delivery_date')) {
                $table->dropColumn('delivery_date');
            }
            if (Schema::hasColumn('sales', 'validity_date')) {
                $table->dropColumn('validity_date');
            }
            if (Schema::hasColumn('sales', 'due_date')) {
                $table->dropColumn('due_date');
            }
        });
    }
};
