<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PermissionRequest;
use App\Http\Resources\Api\V1\GroupedPermissionResource;
use App\Services\PermissionService;
use Illuminate\Http\Request;


class PermissionController extends Controller
{

    public function __construct(protected PermissionService $permissionService)
    {
    }

     /**
     * @return \Illuminate\Http\JsonResponse
     */


    public function index(Request $request)
    {   
     $permissions = $this->permissionService->getPermissionsWithState($request->group_id, $request->user_id ,$request->role);
     
     return \responder::success(GroupedPermissionResource::collection($permissions));
    }

    public function togglePermission(PermissionRequest $request)
    {
        $this->permissionService->togglePermission($request->validated());
        
        return \responder::success(__('Permission updated successfully'));
    }
}
