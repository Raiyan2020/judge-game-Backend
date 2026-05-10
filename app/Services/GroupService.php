<?php

namespace App\Services;

use App\Enums\LegalCaseStatus;
use App\Enums\GroupRole;
use App\Enums\CaseRole;
use App\Repositories\GroupRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class GroupService
{
    public function __construct(protected GroupRepository $repo)
    {
    }

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

  
}
