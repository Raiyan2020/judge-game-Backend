<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The app's achievement ladder needs two fields the `role_titles` schema
     * lacks: `tier` (rung order 1..5, drives the card icon/colour) and
     * `reward_points` (the points a rung is worth — display only; nothing awards
     * points yet). Also add the missing dedupe guard on `group_user_titles`.
     */
    public function up(): void
    {
        Schema::table('role_titles', function (Blueprint $table) {
            $table->unsignedTinyInteger('tier')->default(1)->after('role');
            $table->unsignedInteger('reward_points')->default(0)->after('tier');
            // Backs the seeder's updateOrCreate(role,tier) idempotency — without
            // it a repeated/concurrent seed could create two rows per (role,tier)
            // and the requirements resync would wipe the wrong one.
            $table->unique(['role', 'tier'], 'role_titles_role_tier_unique');
        });

        // Collapse any duplicate claims before the unique index.
        $dups = DB::table('group_user_titles')
            ->select('group_id', 'user_id', 'role_title_id', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as c'))
            ->groupBy('group_id', 'user_id', 'role_title_id')
            ->having('c', '>', 1)
            ->get();
        foreach ($dups as $row) {
            DB::table('group_user_titles')
                ->where('group_id', $row->group_id)
                ->where('user_id', $row->user_id)
                ->where('role_title_id', $row->role_title_id)
                ->where('id', '!=', $row->keep_id)
                ->delete();
        }

        Schema::table('group_user_titles', function (Blueprint $table) {
            $table->unique(['group_id', 'user_id', 'role_title_id'], 'group_user_titles_unique');
        });
    }

    public function down(): void
    {
        Schema::table('role_titles', function (Blueprint $table) {
            $table->dropUnique('role_titles_role_tier_unique');
            $table->dropColumn(['tier', 'reward_points']);
        });
        Schema::table('group_user_titles', function (Blueprint $table) {
            $table->dropUnique('group_user_titles_unique');
        });
    }
};
