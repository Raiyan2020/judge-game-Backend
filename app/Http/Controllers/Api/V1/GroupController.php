<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\GroupRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ChangeRoleRequest;
use App\Http\Requests\Api\V1\GroupRequest;
use App\Http\Requests\Api\V1\GroupUpdateRequest;
use App\Http\Resources\Api\V1\GroupInvitationResource;
use App\Http\Resources\Api\V1\GroupResource;
use App\Models\Group;
use App\Services\GroupService;
use Illuminate\Http\Request;

class GroupController extends Controller
{

    public function __construct(protected GroupService $groupService)
    {
    }

     /**
     * @return \Illuminate\Http\JsonResponse
     */

   public function index()
   {
      return  \responder::success(GroupResource::collection($this->groupService->index()));
   }

   public function store(GroupRequest $request)
   {
       $data = $request->validated();
       $data['user_id'] = auth()->id();
       $group = $this->groupService->store($data, auth()->id());
       return \responder::success(new GroupResource($group));
   }

   public function update(GroupUpdateRequest $request, Group $group)
   {
       $data = $request->validated();
       $group = $this->groupService->update($group, $data);
       return \responder::success(new GroupResource($group));
   }

   public function myGroups()
   {
       $groups = $this->groupService->getUserGroups();
       return \responder::success(GroupResource::collection($groups));
   }

   public function myInvitations()
   {
       $invitations = $this->groupService->getMyInvitations();
       return \responder::success(GroupInvitationResource::collection($invitations));
   }

  


}
