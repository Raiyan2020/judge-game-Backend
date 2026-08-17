<?php

namespace App\Services;

use App\Repositories\PermissionRepository;

class PermissionService
{


    public function __construct(
        protected PermissionRepository $repo,
        protected GroupEventService $events,
    ) {}

    public function getPermissionsWithState($groupId, $userId = null, $role = null)
    {
        $permissions = $this->repo->getAllPermissions();

        // Individual editor (user_id set): reflect ONLY the per-user grants —
        // that is exactly what the toggle writes (GroupUserPermission). Merging
        // role grants here (via a null-role read that returned EVERY role's
        // grants) showed permissions as ON that the toggle couldn't turn off,
        // so the owner "couldn't make it take effect". Role editor: role grants.
        $groupPermissions = $userId
            ? []
            : $this->repo->getGroupPermissions($groupId, $role);

        $userPermissions = $userId
            ? $this->repo->getUserPermissions($groupId, $userId)
            : [];

        $permissions->each(function ($permission) use ($groupPermissions, $userPermissions, $userId) {

            $inGroup = in_array($permission->id, $groupPermissions);
            $inUser  = in_array($permission->id, $userPermissions);

            $permission->has_permission = $userId ? $inUser : $inGroup;
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
                $granted = false;
            } else {
                \App\Models\GroupUserPermission::create([
                    'group_id' => $groupId,
                    'user_id' => $userId,
                    'permission_id' => $permissionId,
                ]);
                $granted = true;
            }
            $subject = \App\Models\User::find($userId);
            $subjectLabel = ['ar' => $subject?->name ?? '', 'en' => $subject?->name ?? ''];
        } else {
            $existing = \App\Models\GroupRolePermission::where('group_id', $groupId)
                ->where('role', $role)
                ->where('permission_id', $permissionId)
                ->first();

            if ($existing) {
                $existing->delete();
                $granted = false;
            } else {
                \App\Models\GroupRolePermission::create([
                    'group_id' => $groupId,
                    'role' => $role,
                    'permission_id' => $permissionId,
                ]);
                $granted = true;
            }
            $subjectLabel = $this->roleLabels((string) $role);
        }

        $this->announcePermissionChange($groupId, $permissionId, $subjectLabel, $granted);
    }

    /** Localized role labels for event copy. */
    private function roleLabels(string $role): array
    {
        return match ($role) {
            'judge' => ['ar' => 'القضاة', 'en' => 'judges'],
            'lawyer' => ['ar' => 'المحامين', 'en' => 'lawyers'],
            'consultant' => ['ar' => 'المستشارين', 'en' => 'consultants'],
            default => ['ar' => 'المواطنين', 'en' => 'citizens'],
        };
    }

    /** Fans a permission grant/revoke out to the bell, news feed and chat. */
    private function announcePermissionChange($groupId, $permissionId, array $subjectLabel, bool $granted): void
    {
        $group = \App\Models\Group::find($groupId);
        $permission = \App\Models\Permission::find($permissionId);
        if (! $group || ! $permission) {
            return;
        }

        $permAr = $permission->getTranslation('name', 'ar');
        $permEn = $permission->getTranslation('name', 'en');
        $verbAr = $granted ? 'مُنحت' : 'سُحبت';
        $verbEn = $granted ? 'granted to' : 'revoked from';

        $this->events->notifyGroupEvent(
            $group,
            'permission_changed',
            title: ['ar' => 'تغيير صلاحية', 'en' => 'Permission changed'],
            body: [
                'ar' => 'صلاحية "' . $permAr . '" ' . $verbAr . ' لـ' . $subjectLabel['ar'],
                'en' => 'Permission "' . $permEn . '" ' . $verbEn . ' ' . $subjectLabel['en'],
            ],
            actor: auth()->user(),
        );
    }
}
