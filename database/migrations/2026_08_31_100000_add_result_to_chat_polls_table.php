<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Records the settled outcome of a LAW poll so the app can show a card
     * verdict after the vote closes:
     *   approved | rejected | null (no law outcome — still open, or an ads poll).
     * Set by MessageService::applyPollResult alongside is_closed.
     */
    public function up(): void
    {
        Schema::table('chat_polls', function (Blueprint $table) {
            $table->string('result')->nullable()->after('is_closed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_polls', function (Blueprint $table) {
            $table->dropColumn('result');
        });
    }
};
