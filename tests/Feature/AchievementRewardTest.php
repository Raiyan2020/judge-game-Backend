<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\PointTransaction;
use App\Models\RoleAction;
use App\Models\RoleTitle;
use App\Models\RoleTitleRequirement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the lazy, idempotent settlement of achievement reward_points added to
 * RoleAchievementService: reading a member's achievements pays each newly
 * completed rung's points exactly once and auto-backfills on first read.
 *
 * NOTE: `vendor/` is absent in this checkout, so this suite is UNEXECUTED here;
 * it is written to run under the project's normal phpunit once deps install.
 */
class AchievementRewardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A completed rung creates exactly one reward row on first read and none on
     * the second (idempotent). A completed-but-EMPTY-requirements rung — which
     * the display flag also marks "completed" — is never paid.
     */
    public function test_completed_rung_awards_reward_points_once(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $group = Group::create([
            'name' => 'Reward Group',
            'user_id' => $owner->id,
        ]);

        // The member plays as a citizen in this group (pivot drives the ladder).
        $member->groups()->attach($group->id, [
            'role' => 'citizen',
            'status' => 'accepted',
        ]);

        // A citizen rung whose single requirement is satisfied (required_count 0,
        // so current 0 >= 0) — a deterministically completed rung with a real,
        // non-empty requirement.
        $action = RoleAction::create([
            'role' => 'citizen',
            'key' => 'file_lawsuit',
            'title' => ['ar' => 'رفع دعوى', 'en' => 'File a lawsuit'],
        ]);

        $paidTitle = RoleTitle::create([
            'role' => 'citizen',
            'tier' => 1,
            'reward_points' => 100,
            'title' => ['ar' => 'مبتدئ', 'en' => 'Rookie'],
        ]);

        RoleTitleRequirement::create([
            'role_title_id' => $paidTitle->id,
            'role_action_id' => $action->id,
            'required_count' => 0,
        ]);

        // A rung with NO requirements: Collection::every() on [] is true so the
        // card shows "completed", but the award path must skip it (never pay an
        // unseeded/orphaned rung).
        RoleTitle::create([
            'role' => 'citizen',
            'tier' => 2,
            'reward_points' => 200,
            'title' => ['ar' => 'محترف', 'en' => 'Veteran'],
        ]);

        $reason = "title_reward:{$group->id}:{$paidTitle->id}:{$member->id}";

        // First read settles the reward. Asserting the tier-1 rung reads back as
        // completed splits the two failure modes: if the response says completed
        // but no point row lands below, the fault is the settle loop, not the
        // completion detection. (Ladder is ordered by tier, so index 0 is tier 1.)
        $this->actingAs($member, 'sanctum')
            ->getJson("/api/groups/{$group->id}/achievements")
            ->assertOk()
            ->assertJsonPath('data.0.is_completed', true);

        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $member->id,
            'role' => 'citizen',
            'points' => 100,
            'notes' => $reason,
        ]);

        // Only the requirement-backed rung was paid — the empty-requirements rung
        // was NOT (its 200 points never land).
        $this->assertSame(
            1,
            PointTransaction::where('notes', 'like', 'title_reward:%')->count(),
            'Exactly one achievement reward row should exist after first read.'
        );

        // Second read is idempotent: no new reward row.
        $this->actingAs($member, 'sanctum')
            ->getJson("/api/groups/{$group->id}/achievements")
            ->assertOk();

        $this->assertSame(
            1,
            PointTransaction::where('notes', 'like', 'title_reward:%')->count(),
            'Re-reading achievements must not double-pay the reward.'
        );
    }

    /**
     * Earning a rung announces it ONCE on the group channels, and the EARNER is
     * among the recipients — notifyGroupEvent excludes the actor from the bell,
     * which silenced the one person who needed it (QA: an earned achievement
     * reached neither notifications nor the news feed).
     *
     * Also pins the marker split: the announcement is guarded by its own
     * `title_announce:` row, NOT by the payment marker — sharing one made an
     * already-paid rung unannounceable forever.
     */
    public function test_completed_rung_announces_once_and_notifies_the_earner(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $group = Group::create([
            'name' => 'Announce Group',
            'user_id' => $owner->id,
        ]);

        foreach ([$owner, $member] as $participant) {
            $participant->groups()->attach($group->id, [
                'role' => 'citizen',
                'status' => 'accepted',
            ]);
        }

        $action = RoleAction::create([
            'role' => 'citizen',
            'key' => 'file_lawsuit',
            'title' => ['ar' => 'رفع دعوى', 'en' => 'File a lawsuit'],
        ]);

        $title = RoleTitle::create([
            'role' => 'citizen',
            'tier' => 1,
            'reward_points' => 100,
            'title' => ['ar' => 'مبتدئ', 'en' => 'Rookie'],
        ]);

        RoleTitleRequirement::create([
            'role_title_id' => $title->id,
            'role_action_id' => $action->id,
            'required_count' => 0,
        ]);

        $this->actingAs($member, 'sanctum')
            ->getJson("/api/groups/{$group->id}/achievements")
            ->assertOk();

        // News row, attributed to the earner as its subject.
        $this->assertDatabaseHas('legal_case_news', [
            'type' => 'achievement_earned',
            'group_id' => $group->id,
            'actor_id' => $member->id,
            'subject_id' => $member->id,
        ]);

        // The bell reaches the earner themselves, not just the rest of the group.
        $this->assertSame(
            1,
            $member->notifications()
                ->where('data->type', 'achievement_earned')
                ->count(),
            'The member who earned the achievement must be notified.'
        );

        // Announcement guarded by its OWN marker.
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $member->id,
            'points' => 0,
            'notes' => "title_announce:{$group->id}:{$title->id}:{$member->id}",
        ]);

        // Second read announces nothing new.
        $this->actingAs($member, 'sanctum')
            ->getJson("/api/groups/{$group->id}/achievements")
            ->assertOk();

        $this->assertSame(
            1,
            \App\Models\LegalCaseNews::where('type', 'achievement_earned')->count(),
            'Re-reading achievements must not re-announce the same rung.'
        );
    }
}
