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

    /**
     * Delete a group. Owner-only, and refused while the group still has any
     * non-closed case (same spirit as "cannot act with open cases as a party").
     * Every child relation (members pivot, chat + messages, laws + requests,
     * permissions, titles, closed cases) has an ON DELETE CASCADE, so the single
     * delete cleans them all up atomically.
     */
    public function deleteGroup(Group $group): void
    {
        if ((int) $group->user_id !== (int) auth()->id()) {
            throw ValidationException::withMessages([
                __('Only the group owner can delete the group'),
            ]);
        }

        $hasOpenCases = $group->legalCases()
            ->where('status', '!=', LegalCaseStatus::CLOSED->value)
            ->exists();

        if ($hasOpenCases) {
            throw ValidationException::withMessages([
                __('Cannot delete a group that still has open cases'),
            ]);
        }

        $group->delete();
    }

    public function getUserGroups()
    {
        $groups = $this->repo->getUserGroupsWithRoles(auth('sanctum')->user());

        // Attach each group's real points (sum of its members' total points)
        // and its global rank among all groups, so the app shows live values
        // instead of a hardcoded placeholder. One ranking query for the whole
        // table, then a lookup per returned group.
        [$pointsByGroup, $rankByGroup] = $this->groupRanking();
        foreach ($groups as $group) {
            $group->points = $pointsByGroup[$group->id] ?? 0;
            $group->global_rank = $rankByGroup[$group->id] ?? 0;
        }

        return $groups;
    }

    /**
     * The "best groups" board (JG-010): every group ordered by its summed
     * member points, each carrying points, rank and member count. Was empty —
     * there was no endpoint for it.
     */
    public function bestGroups()
    {
        [$pointsByGroup, $rankByGroup] = $this->groupRanking();

        $groups = \App\Models\Group::query()
            ->withCount('users')
            ->get();

        foreach ($groups as $group) {
            $group->points = $pointsByGroup[$group->id] ?? 0;
            $group->global_rank = $rankByGroup[$group->id] ?? 0;
        }

        // Exclude memberless groups: they never appear in the points ranking
        // (rank 0), so leaving them in would sort an empty group ABOVE rank 1 —
        // a 0-member group topping the "best groups" board (JG-010). Then order
        // by rank (highest points first), and renumber positions 1..N.
        $ranked = $groups
            ->filter(fn ($g) => (int) $g->users_count > 0 && (int) $g->global_rank > 0)
            ->sortBy('global_rank')
            ->values();

        return $ranked;
    }

    /// Returns [pointsByGroupId, rankByGroupId] for every group, ranked by the
    /// summed member points (standard competition ranking: ties share a rank).
    private function groupRanking(): array
    {
        $ranking = DB::table('group_user')
            ->leftJoin('points', 'points.user_id', '=', 'group_user.user_id')
            ->select('group_user.group_id', DB::raw('COALESCE(SUM(points.total_points),0) as pts'))
            ->groupBy('group_user.group_id')
            ->orderByDesc('pts')
            ->get();

        $pointsByGroup = [];
        $rankByGroup = [];
        $rank = 0;
        $seen = 0;
        $prevPts = null;
        foreach ($ranking as $row) {
            $seen++;
            if ($prevPts === null || (int) $row->pts !== (int) $prevPts) {
                $rank = $seen;
                $prevPts = $row->pts;
            }
            $pointsByGroup[$row->group_id] = (int) $row->pts;
            $rankByGroup[$row->group_id] = $rank;
        }

        return [$pointsByGroup, $rankByGroup];
    }

    public function getGroupMembers($group)
    {
        return $this->repo->getGroupMembers($group);
    }

    public function getMyInvitations()
    {
        return $this->repo->getUserPendingInvitations(auth('sanctum')->user());
    }
    public function update(Group $group, $data)
    {
        if (array_key_exists('name', $data)) {
            if (!$this->permissionService->hasPermission(
                auth()->id(),
                $group,
                'change_group_name'
            )) {
                throw ValidationException::withMessages([__('You are not authorized to change the group name.')]);
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
