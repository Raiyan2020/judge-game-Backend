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

        // In the individual editor, ALSO resolve what this member already holds
        // through their ROLE. It is surfaced as a read-only "inherited" flag (not
        // folded into has_permission, so the per-user toggle keeps writing/erasing
        // only the individual grant — the JG-025 rule). Without this, a permission
        // granted on the ROLE screen was invisible on the member screen, reading
        // as "the permission I gave the lawyer didn't take".
        $rolePermissions = [];
        if ($userId) {
            $memberRole = \App\Models\Group::find($groupId)
                ?->users()
                ->where('user_id', $userId)
                ->first()?->pivot?->role;
            if ($memberRole) {
                $rolePermissions = $this->repo->getGroupPermissions($groupId, $memberRole);
            }
        }

        $permissions->each(function ($permission) use ($groupPermissions, $userPermissions, $rolePermissions, $userId) {

            $inGroup = in_array($permission->id, $groupPermissions);
            $inUser  = in_array($permission->id, $userPermissions);
            $inRole  = in_array($permission->id, $rolePermissions);

            $permission->has_permission = $userId ? $inUser : $inGroup;
            // True only in the individual editor, for a permission the member
            // already has via their role. The app shows it ON + locked with a
            // "granted by role" hint, and points the owner to the role screen to
            // change it.
            $permission->inherited_from_role = $userId ? $inRole : false;
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

        // Arabic preposition attaches to the subject: when GRANTED the لام of
        // جر attaches to the following word — a label starting with "ال"
        // contracts to "لل" ("المستشارين" → "للمستشارين"), a bare name takes a
        // plain "لـ". When REVOKED the correct preposition is "من" ("سُحبت من …"),
        // not "لـ". (Was "لـ" for both, producing "مُنحت لـالمستشارين".)
        $subjectAr = $subjectLabel['ar'];
        if ($granted) {
            $subjectPhraseAr = mb_substr($subjectAr, 0, 2) === 'ال'
                ? 'ل' . mb_substr($subjectAr, 1)
                : 'ل' . $subjectAr;
        } else {
            $subjectPhraseAr = 'من ' . $subjectAr;
        }

        $this->events->notifyGroupEvent(
            $group,
            'permission_changed',
            title: ['ar' => 'تغيير صلاحية', 'en' => 'Permission changed'],
            body: [
                'ar' => 'صلاحية "' . $permAr . '" ' . $verbAr . ' ' . $subjectPhraseAr,
                'en' => 'Permission "' . $permEn . '" ' . $verbEn . ' ' . $subjectLabel['en'],
            ],
            actor: auth()->user(),
        );
    }
}
