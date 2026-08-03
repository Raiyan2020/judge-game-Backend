<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One membership row per user per group. The invite flow only dedupes with
     * an app-level read (`GroupMemberService::inviteMember`), which is a TOCTOU
     * gap under concurrent invites — this is the DB safety net.
     *
     * Any pre-existing duplicate rows are collapsed first (keeping the lowest
     * id) so the unique index can be created on an already-dirty table.
     */
    public function up(): void
    {
        // Collapse duplicates per (group_id, user_id). PREFER an `accepted` row
        // over a `pending` one — keeping the earliest id blindly could delete a
        // real membership and leave a pending invite, silently dropping the
        // user from /my-groups. Fall back to the earliest id otherwise.
        $duplicates = DB::table('group_user')
            ->select(
                'group_id',
                'user_id',
                DB::raw("MIN(CASE WHEN status = 'accepted' THEN id END) as accepted_id"),
                DB::raw('MIN(id) as first_id'),
                DB::raw('COUNT(*) as c')
            )
            ->groupBy('group_id', 'user_id')
            ->having('c', '>', 1)
            ->get();

        foreach ($duplicates as $row) {
            $keepId = $row->accepted_id ?? $row->first_id;
            DB::table('group_user')
                ->where('group_id', $row->group_id)
                ->where('user_id', $row->user_id)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        Schema::table('group_user', function (Blueprint $table) {
            $table->unique(['group_id', 'user_id'], 'group_user_group_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('group_user', function (Blueprint $table) {
            $table->dropUnique('group_user_group_user_unique');
        });
    }
};
