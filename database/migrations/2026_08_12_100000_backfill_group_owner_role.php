<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The group owner must be the judge, but some owners' `group_user.role`
     * drifted to another role (a citizen owner showed up on the role screens
     * and was even offered as a suable defendant). This repairs the data:
     * collapse any duplicate owner membership rows to one, then force that row
     * to `judge`. Idempotent — safe to re-run.
     */
    public function up(): void
    {
        // 1) Keep only the lowest-id membership row per owner (dedupe), so an
        //    owner with both a judge and a citizen row ends up with one.
        DB::statement("
            DELETE gu FROM group_user gu
            JOIN group_user keep
              ON keep.group_id = gu.group_id
             AND keep.user_id  = gu.user_id
             AND keep.id       < gu.id
            JOIN `groups` g ON g.id = gu.group_id
            WHERE gu.user_id = g.user_id
        ");

        // 2) Force the owner's remaining row to the judge role/title, accepted.
        DB::statement("
            UPDATE group_user gu
            JOIN `groups` g ON g.id = gu.group_id
            SET gu.role = 'judge', gu.title = 'judge', gu.status = 'accepted'
            WHERE gu.user_id = g.user_id
        ");
    }

    public function down(): void
    {
        // No-op: the pre-backfill (possibly wrong) roles cannot be restored.
    }
};
