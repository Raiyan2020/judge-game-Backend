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

    public function index()
    {
        return $this->model->withCount('users')->latest()->get();
    }

    public function createGroupWithJudge($data, $judgeId)
    {
        $group = $this->model->create($data);
        $group->users()->attach($judgeId, ['role' => \App\Enums\GroupRole::JUDGE->value , 'status' => 'accepted', 'title' => \App\Enums\GroupRole::JUDGE->value ]);
        $chat = $group->chat()->create(['type' => 'group']);
         $chat->users()->attach($judgeId);
        return $group->load('users');
    }

    public function getUserGroupsWithRoles($user)
    {
        return  $user->groups()->withPivot('role')->withCount('users as members_count')->get();
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
