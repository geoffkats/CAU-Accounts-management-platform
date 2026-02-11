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
        Schema::table('ai_chat_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_chat_sessions', 'user_id')) {
                $table->foreignId('user_id')->after('id')->constrained()->onDelete('cascade');
            }
            if (!Schema::hasColumn('ai_chat_sessions', 'title')) {
                $table->string('title')->after('user_id')->nullable();
            }
            if (!Schema::hasColumn('ai_chat_sessions', 'last_active_at')) {
                $table->timestamp('last_active_at')->after('title')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_chat_sessions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'title', 'last_active_at']);
        });
    }
};
