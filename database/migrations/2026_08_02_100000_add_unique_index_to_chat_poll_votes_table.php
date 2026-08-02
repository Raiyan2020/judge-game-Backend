<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One vote per user per option — a safety net against duplicate votes on
     * the same option (votePoll already moves a user's vote across the poll).
     */
    public function up(): void
    {
        Schema::table('chat_poll_votes', function (Blueprint $table) {
            $table->unique(['chat_poll_option_id', 'user_id'], 'chat_poll_votes_option_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('chat_poll_votes', function (Blueprint $table) {
            $table->dropUnique('chat_poll_votes_option_user_unique');
        });
    }
};
