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
            // Drop the old ssid column
            $table->dropColumn('ssid');
            
            // Add new ssid_provider and ssid_number columns
            $table->string('ssid_provider', 50)->nullable()->after('currency');
            $table->string('ssid_number', 50)->nullable()->after('ssid_provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            // Drop the new columns
            $table->dropColumn(['ssid_provider', 'ssid_number']);
            
            // Restore the old ssid column
            $table->string('ssid', 50)->nullable()->after('currency');
        });
    }
};
