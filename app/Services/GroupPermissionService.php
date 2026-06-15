<?php

namespace App\Services;

use App\Models\Group;
use App\Models\User;
use App\Models\Permission;

class GroupPermissionService
{
    public function hasPermission(
        int $userId,
        Group $group,
        string $permissionKey,
    ): bool {

        if ($group->user_id === $userId) {
            return true;
        }

        $permissionId = Permission::query()
            ->where('key', $permissionKey)
            ->value('id');

        if (! $permissionId) {
            return false;
        }

        $role = $group->users()
            ->where('user_id', $userId)
            ->first()?->pivot?->role;

        if ($role) {
            $hasRolePermission = $group->permissions()
                ->where('role', $role)
                ->where('permission_id', $permissionId)
                ->exists();

            if ($hasRolePermission) {
                return true;
            }
        }

        return $group->userPermissions()
            ->where('user_id', $userId)
            ->where('permission_id', $permissionId)
            ->exists();
    }
}
