<?php

namespace App\Services;

use App\Enums\CaseRole;
use App\Enums\GroupRole;
use App\Enums\LegalCaseStatus;
use App\Models\Group;
use App\Repositories\GroupRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class GroupService
{
    public function __construct(protected GroupRepository $repo, protected GroupPermissionService $permissionService) {}

    public function index()
    {
        return $this->repo->index();
    }

    public function store($request, $judgeId)
    {
        return $this->repo->createGroupWithJudge($request, $judgeId);
    }

    public function getUserGroups()
    {
        return $this->repo->getUserGroupsWithRoles(auth('sanctum')->user());
    }

    public function getGroupMembers($group)
    {
        return $this->repo->getGroupMembers($group);
    }
    public function update(Group $group, $data)
    {
        if (array_key_exists('name', $data)) {
            if (!$this->permissionService->hasPermission(
                auth()->id(),
                $group,
                'change_group_name'
            )) {
                throw ValidationException::withMessages(['You are not authorized to change the group name.']);
            }
        }

        if (array_key_exists('image', $data)) {
            if (!$this->permissionService->hasPermission(
                auth()->id(),
                $group,
                'change_group_image'
            )) {
                throw ValidationException::withMessages(['You are not authorized to change the group image.']);
            }
        }
        return $this->repo->update($group, $data);
    }
}
