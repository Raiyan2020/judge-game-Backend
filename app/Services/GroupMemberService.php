<?php

namespace App\Services;

use App\Models\Group;
use App\Models\User;
use App\Repositories\GroupRepository;
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
}
