<?php

namespace App\Services;

use App\Enums\CaseRole;
use App\Enums\GroupRole;
use App\Enums\LegalCaseStatus;
use App\Models\Group;
use App\Models\User;
use App\Repositories\GroupRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GroupMemberService
{
    public function __construct(protected GroupRepository $repo)
    {
    }

   public function getGroupMembers($group)
   {
       return $this->repo->getGroupMembers($group);
   }

   public function inviteMember(Group $group, array $data)
   {
       $query = User::query();
       if (isset($data['phone'])) {
           $query->where('phone', $data['phone']);
       }
       if (isset($data['username'])) {
           $query->orWhere('username', $data['username']);
       }
       $user = $query->first();

       if (!$user) {
           throw ValidationException::withMessages([ __('User not found with the provided phone or username')]);
       }

       if ($user->id === auth()->id()) {
           throw ValidationException::withMessages([ __('You cannot invite yourself to the group')]);
       }
       if($user->id === $group->user_id){
           throw ValidationException::withMessages([ __('You cannot invite the group creator to the group')]);
       }

       $pivot = $this->repo->getGroupMemberPivot($group, $user);
       if ($pivot) {
           if ($pivot->status === 'pending') {
               throw ValidationException::withMessages([ __('User already has a pending invitation to the group')]);
           }

           throw ValidationException::withMessages([ __('User is already a member of the group')]);
       }

       $this->repo->attachGroupMember($group, $user, [
           'role' => $data['role'],
           'status' => 'pending',
           'title' => $data['role'],
       ]);

       return $user;
   }

   public function acceptInvitation(Group $group, User $user)
   {
       $pivot = $this->repo->getGroupMemberPivot($group, $user);

       if (!$pivot || $pivot->status !== 'pending') {
           throw ValidationException::withMessages([ __('Pending invitation not found')]);
       }

       $this->repo->updateGroupMemberStatus($group, $user, 'accepted');
       $group->chat?->users()->attach($user->id);

       return $user;
   }

   public function rejectInvitation(Group $group, User $user)
   {
       $pivot = $this->repo->getGroupMemberPivot($group, $user);

       if (!$pivot || $pivot->status !== 'pending') {
           throw ValidationException::withMessages([ __('Pending invitation not found')]);
       }

       $this->repo->removeGroupMember($group, $user);

       return true;
   }

     public function leaveGroup($group)
    {
        $user = auth()->user();

        // Check if user is a member of the group
        $member = $group->users()->where('user_id', $user->id)->wherePivot('status', 'accepted')->first();
        if (!$member) {
            throw ValidationException::withMessages([
                'group_id' => __('You are not a member of this group'),
            ]);
        }

        // Check for open cases
        $this->checkForOpenCasesExcludingRole($group, $user->id, CaseRole::WITNESS->value);

        // Remove user from group
        $group->users()->detach($user->id);

        return true;
    }

    public function removeMember($group, $user)
    {
        $currentUser = auth()->user();
        $userId = $user->id;

        // Check if current user is the owner
        if ($group->user_id !== $currentUser->id) {
            throw ValidationException::withMessages([
                'group_id' => __('Only the group owner can remove members'),
            ]);
        }

        // Check if user is a member of the group
        $member = $group->users()->where('user_id', $userId)->wherePivot('status', 'accepted')->first();
        if (!$member) {
            throw ValidationException::withMessages([
                'user_id' => __('User is not a member of this group'),
            ]);
        }

        // Check for open cases
        $this->checkForOpenCasesExcludingRole($group, $userId, CaseRole::WITNESS->value);

        // Remove user from group
        $group->users()->detach($userId);

        return true;
    }

    public function changeRole($group,$data)
    {
        DB::beginTransaction();
        $currentUser = auth()->user();
        $userId = $data['user_id'];
        $newRole = $data['role'];

        if ($group->user_id !== $currentUser->id) {
            throw ValidationException::withMessages([
                'group_id' => __('Only the group owner can change roles'),
            ]);
        }

        $member = $group->users()->where('user_id', $userId)->wherePivot('status', 'accepted')->first();
        if (!$member) {
            throw ValidationException::withMessages([
                'user_id' => __('User is not a member of this group'),
            ]);
        }
         // Check for open cases, excluding WITNESS
        $this->checkForOpenCasesExcludingRole($group, $userId, \App\Enums\CaseRole::WITNESS->value);


        if ($newRole === GroupRole::JUDGE->value) {
            $existingJudge = $group->users()->wherePivot('role', GroupRole::JUDGE->value)->first();
            if ($existingJudge && $existingJudge->id !== $userId) {
                // Change existing judge to CITIZEN
                $group->users()->updateExistingPivot($existingJudge->id, ['role' => GroupRole::CITIZEN->value]);
            }
        }

       
        // Update the role
        $group->users()->updateExistingPivot($userId, ['role' => $newRole]);

        DB::commit();
        return true;
    }

    private function checkForOpenCasesExcludingRole($group, $userId, $excludedRole)
    {
        $openCases = $group->legalCases()
            ->whereHas('participants', function ($query) use ($userId, $excludedRole) {
                $query->where('user_id', $userId)
                      ->where('role', '!=', $excludedRole);
            })
            ->whereNot('status', LegalCaseStatus::CLOSED->value)
            ->count();

        if ($openCases > 0) {
            throw ValidationException::withMessages([
                'user_id' => __('Cannot complete action with open cases as a party'),
            ]);
        }
    }

    public function getMembersByRole(Group $group, $role ,$exceptUserId = null)
    {
        return $this->repo->getMembersByRole($group, $role, $exceptUserId);
    }    
}
