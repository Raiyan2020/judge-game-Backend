<?php

namespace App\Repositories;

use App\Models\Group;

class GroupRepository extends BaseRepository
{
    /**
     * GroupRepository constructor.
     * @param Group $model
     */
    public function __construct(Group $model)
    {
        parent::__construct($model);
    }

    /**
     * Groups the CALLER belongs to (or owns).
     *
     * This used to return every group on the platform to any signed-in user,
     * which handed out a directory of group ids — the starting point for
     * reading other groups' members and laws. No client needs a global list:
     * the app only ever POSTs to `/groups` and reads `/my-groups`.
     */
    public function index()
    {
        $userId = auth()->id();

        return $this->model
            ->withCount('users')
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('users', function ($q) use ($userId) {
                        $q->where('users.id', $userId)
                            ->where('group_user.status', 'accepted');
                    });
            })
            ->latest()
            ->get();
    }

    public function createGroupWithJudge($data, $judgeId)
    {
        $group = $this->model->create($data);
        $group->users()->attach($judgeId, ['role' => \App\Enums\GroupRole::JUDGE->value , 'status' => 'accepted', 'title' => \App\Enums\GroupRole::JUDGE->value ]);
        $chat = $group->chat()->create(['type' => 'group']);
         $chat->users()->attach($judgeId);
        return $group->load('users');
    }

    public function getUserPendingInvitations($user)
    {
        // The groups the user has a PENDING invitation to — for the
        // "My Invitations" screen (accept / reject).
        return $user->groups()
            ->wherePivot('status', 'pending')
            ->withPivot('role')
            ->withCount(['users as members_count' => function ($q) {
                $q->where('group_user.status', 'accepted');
            }])
            ->withCount('legalCases')
            ->with('owner')
            ->get();
    }

    public function getUserGroupsWithRoles($user)
    {
        // Only ACCEPTED memberships — a pending invitation must NOT show up in
        // /my-groups (it used to, letting the invitee enter the group without
        // ever accepting the invite).
        return $user->groups()
            ->wherePivot('status', 'accepted')
            ->withPivot('role', 'status')
            ->withCount(['users as members_count' => function ($q) {
                // Only accepted members — a pending invite must NOT inflate the
                // count shown on the group card (was 3 with a pending, list 2).
                $q->where('group_user.status', 'accepted');
            }])
            ->get();
    }

    public function getGroupMembers($group)
    {
        return $group->users->where('pivot.status', 'accepted')->groupBy('pivot.role');
    }

    public function getGroupMemberPivot($group, $user)
    {
        return $group->users()->where('user_id', $user->id)->first()?->pivot;
    }

    public function attachGroupMember($group, $user, array $meta)
    {
        $group->users()->attach($user->id, $meta);
    }

    public function updateGroupMemberStatus($group, $user, string $status)
    {
        $group->users()->updateExistingPivot($user->id, ['status' => $status]);
        
    }

    public function removeGroupMember($group, $user)
    {
        $group->users()->detach($user->id);
    }

    public function getMembersByRole(Group $group, $role, $exceptUserId = null)
    {
        $query = $group->users()->wherePivot('role', $role)->wherePivot('status', 'accepted');
        if ($exceptUserId) {
            $query->where('user_id', '!=', $exceptUserId);
        }
        return $query->get();
    }    
}
