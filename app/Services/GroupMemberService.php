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
    public function __construct(protected GroupRepository $repo , protected GroupPermissionService $permissionService)
    {
    }

   public function getGroupMembers($group)
   {
       return $this->repo->getGroupMembers($group);
   }

   public function inviteMember(Group $group, array $data)
   {
       // Look the invitee up by phone or username. Phone is stored bare (with
       // country_code in its own column), so match on the digits alone AND on
       // country_code+phone — otherwise a number typed with its dial code, or
       // with spaces/+, would miss a registered user. (The old exact `where`
       // + `orWhere` also OR'd the two fields, which was a scoping bug.)
       $user = null;
       if (!empty($data['phone'])) {
           $digits = preg_replace('/\D/', '', $data['phone']);
           $user = User::query()
               ->where('phone', $digits)
               ->orWhereRaw('CONCAT(country_code, phone) = ?', [$digits])
               ->first();
       }
       if (!$user && !empty($data['username'])) {
           $user = User::where('username', $data['username'])->first();
       }

       if (!$user) {
           throw ValidationException::withMessages([ __('User not found.')]);
       }

       if ($user->id === auth()->id()) {
           throw ValidationException::withMessages([ __('You cannot invite yourself to the group.')]);
       }
       if($user->id === $group->user_id){
           throw ValidationException::withMessages([ __('You cannot invite the group creator to the group.')]);
       }
       if (!$this->permissionService->hasPermission(
           auth()->id(),
           $group,
           'invite_members'
       )) {
           throw ValidationException::withMessages([ __('You are not authorized to invite members to the group.')]);
       }


       $pivot = $this->repo->getGroupMemberPivot($group, $user);
       if ($pivot) {
           if ($pivot->status === 'pending') {
               throw ValidationException::withMessages([ __('User has already been invited to the group.')]);
           }

           throw ValidationException::withMessages([ __('User is already a member of the group.')]);
       }

       DB::transaction(function () use ($group, $user, $data) {
           $this->repo->attachGroupMember($group, $user, [
               'role' => $data['role'],
               'status' => 'pending',
               'title' => $data['role'],
           ]);
       });

       // Notify the invitee so the invitation actually surfaces in their
       // notifications (carrying THIS group's id/name, not a stale one). Sent
       // AFTER the pivot is committed and fail-soft: a notification failure must
       // not roll back the (successful) invite nor surface as an invite error.
       try {
           $inviter = auth()->user();
           $user->notify(new \App\Notifications\GroupInvitationNotification($group, $inviter));

           // Real push so the invite arrives without opening the app. Fail-soft
           // and a no-op until Firebase credentials are configured.
           app(\App\Services\FcmService::class)->sendToToken(
               $user->fcm_token,
               $group->name,
               trim(($inviter?->name ?? '') . ' دعاك للانضمام إلى ' . $group->name),
               ['type' => 'group_invite', 'id' => $group->id],
           );
       } catch (\Throwable $e) {
           \Log::warning('Group invite notification failed: ' . $e->getMessage());
       }

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

        // // Check if current user is the owner
        // if ($group->user_id !== $currentUser->id) {
        //     throw ValidationException::withMessages([
        //         'group_id' => __('Only the group owner can remove members'),
        //     ]);
        // }

        if (!$this->permissionService->hasPermission(
            auth()->id(),
            $group,
            'remove_members'
        )) {
            throw ValidationException::withMessages(['You are not authorized to remove members from the group.']);
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

        // The judge is the group OWNER, permanently (the case always presides
        // under `group->user_id`). So the judge role is NOT reassignable, and
        // the owner's own role cannot be changed — otherwise a member would
        // hold a `judge` pivot with no real judging power (a dead-end), or the
        // owner could be demoted and then file + judge their own case.
        if ($newRole === GroupRole::JUDGE->value) {
            throw ValidationException::withMessages([
                'role' => __('The judge role belongs to the group owner and cannot be reassigned'),
            ]);
        }
        if ((int) $userId === (int) $group->user_id) {
            throw ValidationException::withMessages([
                'user_id' => __('The group owner role cannot be changed'),
            ]);
        }

        $member = $group->users()->where('user_id', $userId)->wherePivot('status', 'accepted')->first();
        if (!$member) {
            throw ValidationException::withMessages([
                'user_id' => __('User is not a member of this group'),
            ]);
        }
        $this->checkRoleChangeAllowed($group, $userId, $newRole);
         // Check for open cases, excluding WITNESS
        $this->checkForOpenCasesExcludingRole($group, $userId, \App\Enums\CaseRole::WITNESS->value);

        // Update the role
        $group->users()->updateExistingPivot($userId, ['role' => $newRole]);

        DB::commit();
        return true;
    }

    private function checkRoleChangeAllowed($group, $userId ,$newRole)
    {
        if($newRole == GroupRole::LAWYER->value){
            $permission = 'assign_lawyers';
          
        }
        else if($newRole == GroupRole::CONSULTANT->value){
            $permission = 'assign_consultants';
        }else{
            $permission = 'assign_citizens';
        }
        if(!$this->permissionService->hasPermission(
            auth()->id(),
            $group,
            $permission
        )) {
            throw ValidationException::withMessages(['You are not authorized to change member roles in the group.']);
       
     }
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
