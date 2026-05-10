<?php

namespace App\Repositories;

use App\Models\GroupRolePermission;
use App\Models\GroupUserPermission;
use App\Models\Permission;

class PermissionRepository extends BaseRepository
{
    /**
     * PermissionRepository constructor.
     * @param Permission $model
     */
    public function __construct(Permission $model)
    {
        parent::__construct($model);
    }

    public function getAllPermissions()
    {
        return Permission::all();
    }

    public function getGroupPermissions($groupId, $role = null)
    {
        $query = GroupRolePermission::where('group_id', $groupId);

        if ($role) {
            $query->where('role', $role);
        }

        return $query->pluck('permission_id')
            ->toArray();
    }

    public function getUserPermissions($groupId, $userId)
    {
        return GroupUserPermission::where('group_id', $groupId)
            ->where('user_id', $userId)
            ->pluck('permission_id')
            ->toArray();
    }

   
}
