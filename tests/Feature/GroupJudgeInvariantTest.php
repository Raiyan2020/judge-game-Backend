<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The "one judge per group = the group owner, immutable" invariant.
 *
 * The judge is always the group OWNER (`groups.user_id`) and can never be
 * reassigned. These tests pin the two write paths that could otherwise mint a
 * second judge — change-role and invite — and confirm a legitimate non-judge
 * assignment still goes through.
 */
class GroupJudgeInvariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_change_role_to_judge_is_rejected_and_makes_no_change(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $group = Group::create(['name' => 'Test Group', 'user_id' => $owner->id]);
        $group->users()->attach($member->id, [
            'role' => 'citizen',
            'status' => 'accepted',
            'title' => 'citizen',
        ]);

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/groups/{$group->id}/change-role", [
                'user_id' => $member->id,
                'role' => 'judge',
            ]);

        $response->assertStatus(422);

        // The member's role is untouched and no judge pivot was created for them.
        $this->assertDatabaseHas('group_user', [
            'group_id' => $group->id,
            'user_id' => $member->id,
            'role' => 'citizen',
        ]);
        $this->assertDatabaseMissing('group_user', [
            'group_id' => $group->id,
            'user_id' => $member->id,
            'role' => 'judge',
        ]);
    }

    public function test_inviting_a_member_as_judge_is_rejected(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create();
        $group = Group::create(['name' => 'Test Group', 'user_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/groups/{$group->id}/invite", [
                'username' => $invitee->username,
                'role' => 'judge',
            ]);

        $response->assertStatus(422);

        // No membership row was created — the invite never reached the service.
        $this->assertDatabaseMissing('group_user', [
            'group_id' => $group->id,
            'user_id' => $invitee->id,
        ]);
    }

    public function test_assigning_a_non_judge_role_still_succeeds(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $member = User::factory()->create();
        $group = Group::create(['name' => 'Test Group', 'user_id' => $owner->id]);
        $group->users()->attach($member->id, [
            'role' => 'citizen',
            'status' => 'accepted',
            'title' => 'citizen',
        ]);

        // The owner holds every group permission, so this is a clean promotion.
        $response = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/groups/{$group->id}/change-role", [
                'user_id' => $member->id,
                'role' => 'lawyer',
            ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('group_user', [
            'group_id' => $group->id,
            'user_id' => $member->id,
            'role' => 'lawyer',
        ]);
    }
}
