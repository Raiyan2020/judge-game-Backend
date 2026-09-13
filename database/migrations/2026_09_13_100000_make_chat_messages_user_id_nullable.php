<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SYSTEM messages have no author. [MessageService::postSystemMessage] inserts
 * `user_id => null` (and MessageResource / the app both render by `type=system`,
 * null user included) — but `chat_messages.user_id` was created with
 * `foreignIdFor(User::class)->constrained()`, i.e. NOT NULL.
 *
 * So EVERY system message ever posted threw an integrity-constraint
 * QueryException, which [GroupEventService::pushChat] (fail-soft per channel)
 * caught and logged. That is why group events landed in the news feed and the
 * bell but never in the group chat — the chat channel was failing silently for
 * every event (member_joined, role_changed, law_changed and every
 * `postChat` case-lifecycle mirror), not just for one.
 *
 * The foreign key itself is a separate table constraint and is left in place:
 * only the column's nullability changes (matching the spelling the
 * `legal_case_news` group-event migration already used successfully here).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Left nullable on rollback: restoring NOT NULL would fail on any system
        // message written in the meantime (they are authorless by design).
        // Intentionally irreversible.
    }
};
