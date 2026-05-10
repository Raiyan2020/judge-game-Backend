<?php

namespace App\Services;

use App\Repositories\PermissionRepository;

class PermissionService
{


    public function __construct(protected PermissionRepository $repo) {}

    public function getPermissionsWithState($groupId, $userId = null, $role = null)
    {
        $permissions = $this->repo->getAllPermissions();

        $groupPermissions = $this->repo->getGroupPermissions($groupId, $role);

        $userPermissions = $userId
            ? $this->repo->getUserPermissions($groupId, $userId)
            : [];

        $permissions->each(function ($permission) use ($groupPermissions, $userPermissions, $userId) {

            $inGroup = in_array($permission->id, $groupPermissions);
            $inUser  = in_array($permission->id, $userPermissions);

            $permission->has_permission = $userId
                ? ($inUser || $inGroup)
                : $inGroup;
        });
        $grouped = $permissions
            ->groupBy('group')
            ->map(function ($permissions, $group) {

                return [
                    'group' => $group,
                    'permissions' => $permissions->values(),
                ];
            })
            ->values();

        return $grouped;
    }

    public function togglePermission($data)
    {
        $groupId = $data['group_id'];
        $userId = $data['user_id'] ?? null;
        $permissionId = $data['permission_id'];
        $role = $data['role'] ?? null;

        if ($userId) {
            $existing = \App\Models\GroupUserPermission::where('group_id', $groupId)
                ->where('user_id', $userId)
                ->where('permission_id', $permissionId)
                ->first();

            if ($existing) {
                $existing->delete();
            } else {
                \App\Models\GroupUserPermission::create([
                    'group_id' => $groupId,
                    'user_id' => $userId,
                    'permission_id' => $permissionId,
                ]);
            }
        } else {
            $existing = \App\Models\GroupRolePermission::where('group_id', $groupId)
                ->where('role', $role)
                ->where('permission_id', $permissionId)
                ->first();

            if ($existing) {
                $existing->delete();
            } else {
                \App\Models\GroupRolePermission::create([
                    'group_id' => $groupId,
                    'role' => $role,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }
}
