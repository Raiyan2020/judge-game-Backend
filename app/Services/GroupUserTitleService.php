<?php

namespace App\Services;

use App\Models\Group;
use App\Models\GroupUserTitle;
use App\Repositories\GroupUserTitleRepository;
use Illuminate\Support\Facades\DB;

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
        // Query the model directly — BaseRepository exposes no `getQuery()`, so
        // the previous call fatally threw (swallowed by the achievements
        // controller's Throwable catch, which is why every rung read as unused).
        $assignment = GroupUserTitle::query()
            ->where('group_id', $group->id)
            ->where('user_id', $userId)
            ->where('role_title_id', $roleTitleId)
            ->first();

        return [
            'used' => (bool) $assignment,
            'used_at' => $assignment?->created_at,
            // Whether THIS title is the member's currently-displayed اللقب (M4a).
            'is_active' => (bool) ($assignment?->is_active ?? false),
        ];
    }

    /**
     * M4a — make `$roleTitleId` the member's single active title in `$group`.
     * Clears any other active title for that member (one at a time) and upserts
     * the chosen row. Atomic so a member is never left with two active titles.
     */
    public function setActive(Group $group, int $userId, int $roleTitleId): GroupUserTitle
    {
        return DB::transaction(function () use ($group, $userId, $roleTitleId) {
            GroupUserTitle::query()
                ->where('group_id', $group->id)
                ->where('user_id', $userId)
                ->update(['is_active' => false]);

            $assignment = GroupUserTitle::query()
                ->where('group_id', $group->id)
                ->where('user_id', $userId)
                ->where('role_title_id', $roleTitleId)
                ->first();

            if ($assignment) {
                $assignment->update(['is_active' => true]);

                return $assignment;
            }

            return GroupUserTitle::create([
                'group_id' => $group->id,
                'user_id' => $userId,
                'role_title_id' => $roleTitleId,
                'is_active' => true,
            ]);
        });
    }
}
