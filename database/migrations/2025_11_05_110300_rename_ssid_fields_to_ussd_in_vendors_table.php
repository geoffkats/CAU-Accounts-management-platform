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
            $table->renameColumn('ssid_provider', 'ussd_provider');
            $table->renameColumn('ssid_number', 'ussd_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->renameColumn('ussd_provider', 'ssid_provider');
            $table->renameColumn('ussd_number', 'ssid_number');
        });
    }
};
