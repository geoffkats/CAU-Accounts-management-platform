<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'document_type')) {
                $table->string('document_type', 30)->default('invoice')->after('account_id');
                $table->index('document_type');
            }
            if (!Schema::hasColumn('sales', 'product_area_code')) {
                $table->string('product_area_code', 50)->nullable()->after('document_type');
                $table->index('product_area_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'product_area_code')) {
                $table->dropIndex(['product_area_code']);
                $table->dropColumn('product_area_code');
            }
            if (Schema::hasColumn('sales', 'document_type')) {
                $table->dropIndex(['document_type']);
                $table->dropColumn('document_type');
            }
        });
    }
};
