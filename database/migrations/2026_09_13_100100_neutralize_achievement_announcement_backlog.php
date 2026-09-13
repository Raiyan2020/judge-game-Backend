<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The `achievement_earned` group event is now guarded by its OWN marker
 * (`title_announce:{group}:{title}:{user}`) instead of sharing the payment
 * marker (`title_reward:…`) — otherwise a rung whose points were already paid
 * could never announce, which is why earned achievements reached neither the
 * bell nor the news feed.
 *
 * That fix alone would make the FIRST read after deploy replay every
 * historically-paid rung at once: one notification to every group member, one
 * news row and one chat system message per rung, per member. Same backlog burst
 * the law-vote auto-execute deliberately neutralized.
 *
 * So: pre-stamp an announce marker for every rung that was ALREADY PAID before
 * this deploy. Those stay silent; everything earned from here on announces
 * exactly once. The marker rows carry 0 points — the `points` view sums points,
 * so they can never move a score.
 *
 * To make one historical rung announce again (e.g. to demo it), delete its
 * `title_announce:…` row.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('point_transactions')
            ->where('notes', 'like', 'title_reward:%')
            ->orderBy('id')
            ->chunkById(500, function ($paid) {
                $markers = [];

                foreach ($paid as $row) {
                    $markers[$row->id] = str_replace(
                        'title_reward:',
                        'title_announce:',
                        (string) $row->notes
                    );
                }

                $existing = DB::table('point_transactions')
                    ->whereIn('notes', array_values($markers))
                    ->pluck('notes')
                    ->all();

                $insert = [];
                $now = now();

                foreach ($paid as $row) {
                    $marker = $markers[$row->id];

                    if (in_array($marker, $existing, true)) {
                        continue;
                    }

                    $insert[] = [
                        'user_id' => $row->user_id,
                        // NULL role + 0 points: a marker, never an award — it
                        // sits outside every role bucket the `points` view sums.
                        'role' => null,
                        'points' => 0,
                        'notes' => $marker,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($insert) {
                    DB::table('point_transactions')->insert($insert);
                }
            });
    }

    public function down(): void
    {
        DB::table('point_transactions')
            ->where('notes', 'like', 'title_announce:%')
            ->delete();
    }
};
