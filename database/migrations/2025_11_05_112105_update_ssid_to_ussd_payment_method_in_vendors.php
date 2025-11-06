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
        // Update any existing vendors with payment_method = 'ssid' to 'ussd'
        DB::table('vendors')
            ->where('payment_method', 'ssid')
            ->update(['payment_method' => 'ussd']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to ssid if needed
        DB::table('vendors')
            ->where('payment_method', 'ussd')
            ->update(['payment_method' => 'ssid']);
    }
};
