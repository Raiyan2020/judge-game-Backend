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

        $users = $this->model
            ->with(['points', 'groups', 'country'])
            ->join('points', 'users.id', '=', 'points.user_id')
            ->when($request->country_id, function ($q) use ($request) {
                $q->where('users.country_id', $request->country_id);
            })
            ->orderByDesc($column)
            ->select('users.*')
            ->get();

        return $users->map(function ($user) use ($column, $request) {

            $topGroup = null;

            foreach ($user->groups as $group) {

                $topUserId = User::query()
                    ->join('points', 'users.id', '=', 'points.user_id')
                    ->whereHas('groups', function ($q) use ($group, $request) {
                        $q->where('groups.id', $group->id)
                            ->where('role', $request->role);
                    })
                    ->orderByDesc($column)
                    ->value('users.id');


                if ($topUserId == $user->id) {
                    $topGroup = $group->name;
                    break;
                }
            }

            $user->top_group_name = $topGroup;

            return $user;
        });
    }
}
