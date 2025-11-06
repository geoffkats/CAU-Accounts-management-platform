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
        Schema::table('assets', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['asset_category_id']);
            
            // Change the column type to string
            $table->string('asset_category_id', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // Change back to unsignedBigInteger
            $table->unsignedBigInteger('asset_category_id')->nullable()->change();
            
            // Re-add the foreign key
            $table->foreign('asset_category_id')->references('id')->on('asset_categories')->nullOnDelete();
        });
    }
};
