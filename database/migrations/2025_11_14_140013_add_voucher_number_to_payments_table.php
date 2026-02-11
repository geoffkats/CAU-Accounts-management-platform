<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('voucher_number')->nullable()->after('id');
        });

        // Generate voucher numbers for existing payments
        $payments = DB::table('payments')->orderBy('id')->get();
        foreach ($payments as $index => $payment) {
            DB::table('payments')
                ->where('id', $payment->id)
                ->update(['voucher_number' => 'PV-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT)]);
        }

        // Now make it unique and not nullable
        Schema::table('payments', function (Blueprint $table) {
            $table->string('voucher_number')->unique()->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('voucher_number');
        });
    }
};
