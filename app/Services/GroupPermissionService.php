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

    /**
     * The permission KEYS [$userId] effectively holds in [$group] — the owner
     * holds every key; any other member gets the union of their ROLE grants and
     * their per-user grants. Powers the member-callable `my-permissions` so the
     * app can reveal only the action entry points this member may actually use.
     *
     * @return string[]
     */
    public function keysForUser(Group $group, int $userId): array
    {
        // Owner (the presiding judge) holds every permission.
        if ((int) $group->user_id === $userId) {
            return Permission::query()->pluck('key')->all();
        }

        $ids = [];

        $role = $group->users()
            ->where('user_id', $userId)
            ->first()?->pivot?->role;

        if ($role) {
            $ids = array_merge($ids, $group->permissions()
                ->where('role', $role)
                ->pluck('permission_id')
                ->all());
        }

        $ids = array_merge($ids, $group->userPermissions()
            ->where('user_id', $userId)
            ->pluck('permission_id')
            ->all());

        if (empty($ids)) {
            return [];
        }

        return Permission::query()
            ->whereIn('id', array_unique($ids))
            ->pluck('key')
            ->all();
    }

    /**
     * The set of user ids in [$group] holding [$permissionKey], resolved in ONE
     * pass — owner (always) + roles granted the permission + per-user overrides
     * — so callers can flag a whole members list without an N+1 of
     * [hasPermission] per row. Returns int ids.
     *
     * @return int[]
     */
    public function usersWithPermission(Group $group, string $permissionKey): array
    {
        // The owner holds every permission (mirrors hasPermission()).
        $ids = [(int) $group->user_id];

        $permissionId = Permission::query()
            ->where('key', $permissionKey)
            ->value('id');

        if (! $permissionId) {
            return array_values(array_unique($ids));
        }

        // Every accepted member whose ROLE carries the permission.
        $roles = $group->permissions()
            ->where('permission_id', $permissionId)
            ->pluck('role')
            ->all();

        if (! empty($roles)) {
            $ids = array_merge($ids, $group->users()
                ->wherePivotIn('role', $roles)
                ->pluck('users.id')
                ->all());
        }

        // Per-user overrides.
        $ids = array_merge($ids, $group->userPermissions()
            ->where('permission_id', $permissionId)
            ->pluck('user_id')
            ->all());

        return array_values(array_unique(array_map('intval', $ids)));
    }
}
