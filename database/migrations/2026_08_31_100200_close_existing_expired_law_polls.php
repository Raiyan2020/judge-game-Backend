<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Neutralise the pre-fix LAW-poll backlog.
 *
 * Two shipped bugs meant law polls never settled: the tally matched English
 * 'yes'/'no' against Arabic «نعم»/«لا» (always 0-0 → rejected), AND `is_closed`
 * was never fillable so `$poll->update(['is_closed'=>true])` was a silent no-op.
 * So every expired law poll is still `is_closed = 0`. Once the resolver fix
 * ships, the FIRST chat/laws read would settle the ENTIRE backlog at once and
 * enact every historically-approved proposal — a surprise batch of law changes.
 *
 * Product decision: LEAVE the backlog (do NOT retroactively enact). This closes
 * every already-expired, still-open LAW poll as `rejected` with no enactment, so
 * the fixed resolver only ever acts on NEW polls going forward. Must run AFTER
 * `add_result_to_chat_polls_table` (that column is written here).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('chat_polls')
            ->whereIn('type', ['create_law', 'update_law', 'delete_law'])
            ->where('is_closed', false)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['is_closed' => true, 'result' => 'rejected']);
    }

    public function down(): void
    {
        // One-way data cleanup — nothing to restore (we cannot know which of the
        // closed polls were originally open, and reopening them would re-trigger
        // the very backlog burst this migration exists to prevent).
    }
};
