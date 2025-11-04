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
        Schema::table('expenses', function (Blueprint $table) {
            $table->enum('payment_status', ['unpaid', 'paid', 'partial'])->default('unpaid')->after('status');
            $table->date('payment_date')->nullable()->after('payment_status');
            $table->string('payment_reference', 100)->nullable()->after('payment_date')->comment('Check number or payment transaction reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'payment_date', 'payment_reference']);
        });
    }
};
