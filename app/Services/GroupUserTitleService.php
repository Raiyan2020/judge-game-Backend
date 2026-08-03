<?php

namespace App\Services;

use App\Models\Group;
use App\Models\GroupUserTitle;
use App\Repositories\GroupUserTitleRepository;

class GroupUserTitleService
{
    public function __construct(protected GroupUserTitleRepository $repository)
    {
    }

    public function store(Group $group, int $userId, int $roleTitleId): GroupUserTitle
    {
        return $this->repository->create([
            'group_id' => $group->id,
            'user_id' => $userId,
            'role_title_id' => $roleTitleId,
        ]);
    }

    public function getUsage(Group $group, int $userId, int $roleTitleId): array
    {
        $assignment = $this->repository->getQuery()
            ->where('group_id', $group->id)
            ->where('user_id', $userId)
            ->where('role_title_id', $roleTitleId)
            ->first();

        return [
            'used' => (bool) $assignment,
            'used_at' => $assignment?->created_at,
        ];
    }
}
