<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * DB-level last line of defence for the "one judge per group" invariant.
 *
 * The judge is ALWAYS the group OWNER (`groups.user_id`): written exactly once,
 * at group creation (`GroupRepository::createGroupWithJudge`), and never
 * reassignable. Every application path already refuses a second judge —
 * `ChangeRoleRequest` (role restricted to citizen/lawyer/consultant),
 * `GroupMemberInviteRequest` (`Rule::notIn([judge])`) and
 * `GroupMemberService::changeRole` (explicit guard). This migration adds the one
 * guarantee those app guards cannot give under concurrency: at most one
 * `role = 'judge'` row per `group_id`, enforced by the database itself.
 *
 * Mechanism (MySQL only): a VIRTUAL generated column `judge_slot` equal to
 * `group_id` for judge rows and NULL for everyone else, plus a UNIQUE index on
 * it. MySQL unique indexes ignore NULLs, so non-judge rows stay unconstrained,
 * while two judges in the same group would collide on the same `judge_slot`
 * value. VIRTUAL (not STORED) avoids a full rewrite of the pivot table.
 *
 * Guarded to MySQL in BOTH up() and down(): the phpunit suite runs on in-memory
 * SQLite (`DB::getDriverName() === 'sqlite'`), whose handling of generated
 * columns and NULL-in-unique differs — the early return emits no DDL there.
 *
 * NOTE: this project never runs migrations; they ship for the server to run.
 */
return new class extends Migration
{
    private const INDEX = 'group_user_one_judge_per_group_unique';

    public function up(): void
    {
        // SQLite (phpunit) and any non-MySQL driver: emit no DDL.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Pre-flight — DO NOT delete or mutate any row here.
        //
        // The invite-as-judge bug (since fixed) once allowed a second, NON-owner
        // `judge` row, and the owner-role backfill only repaired the OWNER's row
        // (`WHERE gu.user_id = g.user_id`) — it never removed such strays. Adding
        // the unique index over that dirty data would die with an opaque MySQL
        // 1062. Detect it first and stop with an actionable message instead:
        // demoting a stray judge is a product decision for the operator, not
        // something this migration may silently do.
        $offenders = DB::table('group_user')
            ->select('group_id', DB::raw('COUNT(*) as judge_count'))
            ->where('role', 'judge')
            ->groupBy('group_id')
            ->having('judge_count', '>', 1)
            ->pluck('group_id')
            ->all();

        if (! empty($offenders)) {
            throw new \RuntimeException(
                'Cannot enforce one-judge-per-group: group_id(s) ['
                . implode(', ', $offenders)
                . '] hold more than one row with role=judge. Demote the '
                . 'non-owner judge row(s) — the real judge is groups.user_id — to '
                . 'citizen, then re-run this migration.'
            );
        }

        // Judge rows expose their group_id; every other row is NULL. The unique
        // index then permits at most one judge per group and leaves non-judge
        // rows entirely unconstrained.
        DB::statement(
            "ALTER TABLE `group_user`
             ADD COLUMN `judge_slot` BIGINT UNSIGNED
             GENERATED ALWAYS AS (CASE WHEN `role` = 'judge' THEN `group_id` END) VIRTUAL"
        );

        DB::statement(
            'CREATE UNIQUE INDEX `' . self::INDEX . '` ON `group_user` (`judge_slot`)'
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Drop the index BEFORE the generated column it is built on.
        DB::statement('DROP INDEX `' . self::INDEX . '` ON `group_user`');
        DB::statement('ALTER TABLE `group_user` DROP COLUMN `judge_slot`');
    }
};
