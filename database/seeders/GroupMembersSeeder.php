<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\Group;
use App\Models\GroupLaw;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Test-data seeder: fills every group (or a chosen set) with a role-diverse
 * roster of ACCEPTED members PLUS a set of group laws, so the full case cycle
 * can be played freely — filing needs ≥2 citizens, lawyers on both sides, a
 * consultant to advise the judge, and at least one law to charge the defendant
 * with. The group owner is already the judge.
 *
 * Standalone and opt-in (NOT wired into DatabaseSeeder — you don't want fake
 * members on every production seed). Run with:
 *
 *     php artisan db:seed --class=GroupMembersSeeder
 *
 * Idempotent: users are keyed by a deterministic username and pivots are
 * upserted, so re-running (or adding a new group then re-running) only tops up.
 *
 * The seeded accounts are real, loginable users — phone + country_code `20` +
 * the static OTP (`STATIC_OTP`, `1234` by default). So you can sign in AS any of
 * them to act out every role: a seeded citizen files against another seeded
 * citizen, seeded lawyers take each side, a seeded consultant advises, and the
 * group owner (the judge) rules.
 */
class GroupMembersSeeder extends Seeder
{
    /**
     * How many of each role to add to every target group. The owner is already
     * the group's judge, so no judge is added here. This roster satisfies the
     * "≥2 lawyers or ≥2 citizens" filing rule with room to spare (a plaintiff
     * lawyer AND a distinct defense lawyer, several suable citizens).
     *
     * @var array<string,int>
     */
    private const ROSTER = [
        'consultant' => 1,
        'lawyer' => 3,
        'citizen' => 5,
    ];

    /**
     * The laws (القوانين) added to every target group. `description` is the law
     * text shown in the "القوانين المخالفة" picker; `reason` is its rationale.
     * The filer must select at least one, so a group needs laws to file at all.
     *
     * @var array<array{description:string,reason:string}>
     */
    private const LAWS = [
        ['description' => 'التأخر عن الجلسة دون عذر مقبول', 'reason' => 'الإخلال بمواعيد المحكمة يعطّل سير العدالة'],
        ['description' => 'الإدلاء بشهادة زور أمام المحكمة', 'reason' => 'الكذب على القضاء جريمة تقوّض الثقة في الأحكام'],
        ['description' => 'إهانة أحد أطراف الدعوى', 'reason' => 'حفظ كرامة المتقاضين وهيبة الجلسة'],
        ['description' => 'الإخلال بالنظام العام داخل المجموعة', 'reason' => 'الحفاظ على انضباط المجتمع'],
        ['description' => 'التهرب من تنفيذ حكم قضائي', 'reason' => 'الأحكام مُلزِمة ويجب تنفيذها'],
        ['description' => 'نشر معلومات كاذبة عن عضو', 'reason' => 'حماية السمعة من التشهير'],
    ];

    /**
     * Restrict to these group ids, or leave empty to populate EVERY group.
     *
     * @var array<int>
     */
    private const TARGET_GROUP_IDS = [];

    /** Fixed dial code (numeric — login/register validate `country_code|numeric`). */
    private const COUNTRY_CODE = '20';

    public function run(): void
    {
        $groups = empty(self::TARGET_GROUP_IDS)
            ? Group::all()
            : Group::whereIn('id', self::TARGET_GROUP_IDS)->get();

        if ($groups->isEmpty()) {
            $this->command?->warn('GroupMembersSeeder: no groups found to populate.');
            return;
        }

        $summary = [];

        foreach ($groups as $group) {
            // Ensure a group chat exists (older groups may predate it), so seeded
            // members can be joined to it exactly as the accept-invitation flow
            // does — otherwise they would be silent in the group chat.
            $chat = $group->chat ?? $group->chat()->create(['type' => 'group']);

            $memberIndex = 0;
            foreach (self::ROSTER as $role => $count) {
                for ($i = 1; $i <= $count; $i++) {
                    $memberIndex++;
                    $user = $this->makeUser($group->id, $role, $i, $memberIndex);
                    $this->attachToGroup($group, $user, $role);
                    $chat->users()->syncWithoutDetaching([$user->id]);

                    $summary[] = [
                        'group' => "#{$group->id} {$group->name}",
                        'role' => $role,
                        'name' => $user->name,
                        'phone' => self::COUNTRY_CODE . ' ' . $user->phone,
                        'username' => $user->username,
                    ];
                }
            }

            $lawsAdded = $this->seedLaws($group);

            $this->command?->info(
                "Populated group #{$group->id} ({$group->name}) — {$lawsAdded} laws present."
            );
        }

        $this->command?->table(
            ['Group', 'Role', 'Name', 'Phone (code + number)', 'Username'],
            $summary,
        );
        $this->command?->info(
            'Seeded members can sign in with the phone above + OTP ' .
            config('auth.static_otp', '1234') . '.'
        );
    }

    /**
     * Add [LAWS] to a group, idempotently. Keyed by (group_id, description) so a
     * re-run neither duplicates a law nor overwrites one edited in-app. Returns
     * the total number of laws the group now has from this set.
     */
    private function seedLaws(Group $group): int
    {
        foreach (self::LAWS as $law) {
            GroupLaw::firstOrCreate(
                ['group_id' => $group->id, 'description' => $law['description']],
                ['reason' => $law['reason']],
            );
        }
        return count(self::LAWS);
    }

    /**
     * Create (or reuse) a seed user for a group slot. Keyed by a deterministic
     * username so re-runs never duplicate; phone is derived to be globally
     * unique per (group, slot).
     */
    private function makeUser(int $groupId, string $role, int $roleSeq, int $memberIndex): User
    {
        $username = "seed_g{$groupId}_{$role}_{$roleSeq}";

        // Numeric, unique per (group, member slot): group ids are spaced by 100,
        // which is far above the per-group roster size, so no two slots collide.
        $phone = (string) (500000000 + $groupId * 100 + $memberIndex);

        return User::firstOrCreate(
            ['username' => $username],
            [
                'name' => ucfirst($role) . " {$roleSeq} · G{$groupId}",
                'phone' => $phone,
                'country_code' => self::COUNTRY_CODE,
                'language' => 'ar',
                'status' => UserStatus::ONLINE->value,
                // Pre-set so a check-code call works even if STATIC_OTP is unset;
                // login() re-mints it, and with STATIC_OTP it is always this.
                'code' => config('auth.static_otp', '1234'),
            ],
        );
    }

    /**
     * Attach the user to the group as an ACCEPTED member of [$role], upserting
     * the pivot so a re-run corrects a changed role instead of erroring on the
     * unique (group_id, user_id) index.
     */
    private function attachToGroup(Group $group, User $user, string $role): void
    {
        $pivot = [
            'role' => $role,
            'status' => 'accepted',
            'title' => $role, // `group_user.title` is NOT nullable.
        ];

        if ($group->users()->where('user_id', $user->id)->exists()) {
            $group->users()->updateExistingPivot($user->id, $pivot);
        } else {
            $group->users()->attach($user->id, $pivot);
        }
    }
}
