<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\RoleTitle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTitleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_store_a_title_assignment_for_a_group(): void
    {
        $user = User::factory()->create();
        $group = Group::create([
            'name' => 'Test Group',
            'user_id' => $user->id,
        ]);
        $roleTitle = RoleTitle::create([
            'role' => 'member',
            'title' => ['en' => 'Junior Member'],
        ]);

        $response = $this->actingAs($user)->postJson("/api/groups/{$group->id}/titles", [
            'role_title_id' => $roleTitle->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.group_id', $group->id)
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.role_title_id', $roleTitle->id);

        $this->assertDatabaseHas('group_user_titles', [
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role_title_id' => $roleTitle->id,
        ]);
    }
}
