<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PermissionRequest;
use App\Http\Resources\Api\V1\GroupedPermissionResource;
use App\Models\Group;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;


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
     // Only the group owner (the presiding judge) may view/manage the group's
     // permission matrix. Without this any user could read any group's grants.
     $this->ensureGroupOwner($request->group_id);

     $permissions = $this->permissionService->getPermissionsWithState($request->group_id, $request->user_id ,$request->role);

     return \responder::success(GroupedPermissionResource::collection($permissions));
    }

    public function togglePermission(PermissionRequest $request)
    {
        // Granting/revoking a permission is owner-only — otherwise any member
        // could self-grant `assign_lawyers` / `invite_members` / etc. and
        // nullify the whole permission gate.
        $this->ensureGroupOwner($request->group_id);

        $this->permissionService->togglePermission($request->validated());

        return \responder::success(__('Permission updated successfully'));
    }

    /**
     * The signed-in member's OWN effective permission keys in a group. Unlike
     * [index] this is member-callable (any accepted member may read their own
     * grants) so the app can reveal the action entry points they're allowed to
     * use — the owner gets every key.
     */
    public function mine(Request $request)
    {
        $groupId = $request->group_id;

        $group = Group::find($groupId);

        // A non-member (or unknown group) simply holds nothing here — never leak
        // another group's matrix, and never error the group screens that call it.
        $isMember = $group && (
            (int) $group->user_id === (int) auth()->id()
            || $group->users()->where('user_id', auth()->id())->exists()
        );

        if (! $isMember) {
            return \responder::success([]);
        }

        $keys = app(\App\Services\GroupPermissionService::class)
            ->keysForUser($group, (int) auth()->id());

        return \responder::success(array_values($keys));
    }

    /**
     * Guard: abort unless the authenticated user owns the group. Thrown as a
     * validation message so the app surfaces it through the standard first-error
     * path.
     */
    private function ensureGroupOwner($groupId): void
    {
        $isOwner = Group::where('id', $groupId)
            ->where('user_id', auth()->id())
            ->exists();

        if (! $isOwner) {
            throw ValidationException::withMessages([
                __('You are not authorized to manage group permissions'),
            ]);
        }
    }
}
