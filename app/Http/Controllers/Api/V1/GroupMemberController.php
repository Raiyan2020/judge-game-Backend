<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ChangeRoleRequest;
use App\Http\Requests\Api\V1\GroupMemberInviteRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Group;
use App\Models\User;
use App\Services\GroupMemberService;
use App\Services\GroupPermissionService;
use Illuminate\Http\Request;

class GroupMemberController extends Controller
{

   public function __construct(
      protected GroupMemberService $groupMemberService,
      protected GroupPermissionService $permissionService,
   ) {}

   /**
    * @return \Illuminate\Http\JsonResponse
    */

   public function index(Group $group)
   {
      $grouped = $this->groupMemberService->getGroupMembers($group);

      // Flag lawsuit immunity per member so the submit-case form can red-note an
      // immune defendant BEFORE the server 422s. Resolved once for the whole
      // group (no N+1). A court officer is un-suable by role too; this covers
      // the citizen-with-granted-immunity case the app can't infer.
      $immune = array_flip(
         $this->permissionService->usersWithPermission($group, 'lawsuit_immunity')
      );
      foreach ($grouped as $bucket) {
         foreach ($bucket as $user) {
            $user->setAttribute('has_immunity', isset($immune[(int) $user->id]));
         }
      }

      return \responder::success([
         'judge' => UserResource::collection($grouped['judge'] ?? []),
         'consultant' => UserResource::collection($grouped['consultant'] ?? []),
         'lawyers' => UserResource::collection($grouped['lawyer'] ?? []),
         'citizens' => UserResource::collection($grouped['citizen'] ?? []),
      ]);
   }

   /**
    * Invite a member to the group
    */
   public function inviteMember(Group $group, GroupMemberInviteRequest $request)
   {
      try {
         $this->groupMemberService->inviteMember($group, $request->validated());
         return \responder::success(__('User invited successfully'));
      } catch (\Exception $e) {
         return \responder::error($e->getMessage());
      }
   }

   /**
    * Accept a pending invitation for the group.
    */
   public function acceptInvitation(Group $group)
   {
      try {
         $this->groupMemberService->acceptInvitation($group, auth()->user());
         return \responder::success(__('Invitation accepted successfully'));
      } catch (\Exception $e) {
         return \responder::error($e->getMessage());
      }
   }

   /**
    * Reject a pending invitation and remove the user from the group.
    */
   public function rejectInvitation(Group $group)
   {
      try {
         $this->groupMemberService->rejectInvitation($group, auth()->user());
         return \responder::success(__('Invitation rejected successfully'));
      } catch (\Exception $e) {
         return \responder::error($e->getMessage());
      }
   }

    public function leaveGroup(Group $group)
   {
       $this->groupMemberService->leaveGroup($group);
       return \responder::success([__('Successfully left the group')]);
   }

   public function removeMember(Group $group, User $user)
   {
       $this->groupMemberService->removeMember($group, $user);
       return \responder::success([ __('Member removed successfully')]);
   }

   public function changeRole(ChangeRoleRequest $request, Group $group)
   {
       $newRole = $request->role;
       $this->groupMemberService->changeRole($group, $request->validated());
       return \responder::success([ __('Role changed successfully')]);
   }

   public function getMemberByRole(Group $group, Request $request)
   {
       $members = $this->groupMemberService->getMembersByRole($group, $request->role,$request->except_user_id);
       return \responder::success(UserResource::collection($members));
   }
}
