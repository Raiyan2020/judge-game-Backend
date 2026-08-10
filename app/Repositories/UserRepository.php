<?php

namespace App\Repositories;


use App\Models\User;

class UserRepository extends BaseRepository
{
    /**
     * UserRepository constructor.
     * @param User $model
     */
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function checkUser($request)
    {
        return $this->model
            ->where('phone', $request['phone'])
            ->where('country_code', $request['country_code'])
            ->where('code', $request['code'])
            ->first();
    }


    public function getUserByPhone($phone, $country_code)
    {
        return $this->model->where('phone', $phone)->where('country_code', $country_code)->first();
    }


    public function usersByRoleRank($request)
    {
        $column = match ($request->role) {
            'judge'      => 'judge_points',
            'lawyer'     => 'lawyer_points',
            'consultant' => 'consultant_points',
            'citizen'    => 'citizen_points',
            default      => 'total_points',
        };

        // `points` is a VIEW over `point_transactions`, and nothing writes that
        // table yet — so an INNER join eliminated every row and the board came
        // back empty for every role. LEFT JOIN keeps the players visible;
        // COALESCE sorts the point-less behind anyone who does score, instead of
        // ordering on NULL.
        $sort = 'COALESCE(points.' . $column . ', 0)';

        $users = $this->model
            ->with(['points', 'groups', 'country'])
            ->leftJoin('points', 'users.id', '=', 'points.user_id')
            ->when($request->country_id, function ($q) use ($request) {
                $q->where('users.country_id', $request->country_id);
            })
            // Roles are per-GROUP (there is no global role), so a board lists
            // everyone who holds that role in at least one group. Without this
            // the role only chose a sort column and every board listed everyone
            // — the "judge" board was full of citizens.
            ->when($this->rankableRole($request->role), function ($q, $role) {
                $q->whereHas('groups', function ($group) use ($role) {
                    $group->where('group_user.role', $role)
                        ->where('group_user.status', 'accepted');
                });
            })
            ->orderByRaw($sort . ' desc')
            ->select('users.*')
            ->get();

        $topUserIdByGroup = $this->topUserIdPerGroup($column, $request->role);

        return $users->map(function ($user) use ($topUserIdByGroup) {
            $topGroup = null;

            foreach ($user->groups as $group) {
                if (($topUserIdByGroup[$group->id] ?? null) === $user->id) {
                    $topGroup = $group->name;
                    break;
                }
            }

            $user->top_group_name = $topGroup;

            return $user;
        });
    }

    /**
     * The role to filter a leaderboard by, or null to rank everyone.
     *
     * An unknown or absent role keeps the old unfiltered `total_points` board
     * rather than returning nothing.
     */
    private function rankableRole(?string $role): ?string
    {
        return in_array($role, ['judge', 'lawyer', 'consultant', 'citizen'], true)
            ? $role
            : null;
    }

    /**
     * `[group_id => highest-scoring user id]` for the given role, in ONE query.
     *
     * This replaces a nested loop that ran a query per user per group and
     * recomputed the same "top member of group G" answer once for every member
     * of G — O(total memberships) queries to produce O(groups) facts.
     */
    private function topUserIdPerGroup(string $column, ?string $role): array
    {
        $role = $this->rankableRole($role);

        $rows = \Illuminate\Support\Facades\DB::table('group_user')
            ->leftJoin('points', 'points.user_id', '=', 'group_user.user_id')
            ->where('group_user.status', 'accepted')
            ->when($role, fn ($q) => $q->where('group_user.role', $role))
            ->orderByRaw('COALESCE(points.' . $column . ', 0) desc')
            ->get(['group_user.group_id', 'group_user.user_id']);

        $top = [];

        // Rows arrive best-first, so the first sighting of a group is its leader.
        foreach ($rows as $row) {
            $top[$row->group_id] ??= (int) $row->user_id;
        }

        return $top;
    }
}
