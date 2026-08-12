<?php

namespace App\Repositories;


use App\Models\Point;
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
        $column = $this->pointsColumnForRole($request->role);

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
            // Deterministic tiebreak: equal scores order by id, so the board is
            // stable AND agrees with `rankInRoleBoard` (which counts `id <` on a
            // tie). Without it, ties order arbitrarily and a user's profile rank
            // could disagree with their row here.
            ->orderByRaw($sort . ' desc')
            ->orderBy('users.id')
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
     * The points column a leaderboard sorts by for [$role]; `total_points` for
     * an unknown/absent role (the "everyone" board).
     */
    public function pointsColumnForRole(?string $role): string
    {
        return match ($role) {
            'judge'      => 'judge_points',
            'lawyer'     => 'lawyer_points',
            'consultant' => 'consultant_points',
            'citizen'    => 'citizen_points',
            default      => 'total_points',
        };
    }

    /**
     * The user's 1-based position on the [$role] leaderboard — the SAME board
     * `usersByRoleRank` renders, so a profile rank agrees with the ranking
     * screen. [$local] false = the global board; true = the same-country board.
     * Counts everyone strictly ahead — higher role points, ties broken by
     * smaller id (matching the board's `orderBy('users.id')`) — plus 1.
     */
    public function rankInRoleBoard(User $user, ?string $role, bool $local): int
    {
        $column = $this->pointsColumnForRole($role);
        $myScore = (int) (Point::where('user_id', $user->id)->value($column) ?? 0);
        $myId = (int) $user->id;

        $ahead = $this->model
            ->newQuery()
            ->leftJoin('points', 'users.id', '=', 'points.user_id')
            ->when($local, function ($q) use ($user) {
                // Prefer country_id (matches the board's country filter). Fall
                // back to country_code when it is unset, so a user with no
                // country_id still gets a real LOCAL rank instead of one that
                // silently equals their global rank (country_id is nullable).
                if ($user->country_id !== null) {
                    $q->where('users.country_id', $user->country_id);
                } else {
                    $q->where('users.country_code', $user->country_code);
                }
            })
            ->when($this->rankableRole($role), function ($q, $r) {
                $q->whereHas('groups', function ($group) use ($r) {
                    $group->where('group_user.role', $r)
                        ->where('group_user.status', 'accepted');
                });
            })
            ->whereRaw(
                '(COALESCE(points.' . $column . ', 0) > ? '
                . 'OR (COALESCE(points.' . $column . ', 0) = ? AND users.id < ?))',
                [$myScore, $myScore, $myId]
            )
            ->count();

        return $ahead + 1;
    }

    /**
     * The roles a user holds (accepted) across their groups, distinct. Empty
     * when they hold none — then only the "everyone" board applies.
     *
     * @return array<int,string>
     */
    public function acceptedRolesOf(User $user): array
    {
        return \Illuminate\Support\Facades\DB::table('group_user')
            ->where('user_id', $user->id)
            ->where('status', 'accepted')
            ->distinct()
            ->pluck('role')
            ->all();
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
