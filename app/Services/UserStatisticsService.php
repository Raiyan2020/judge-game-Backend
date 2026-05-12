<?php

namespace App\Services;


use App\Models\Group;
use App\Models\User;
use App\Repositories\LegalCaseRepository;
use Illuminate\Validation\ValidationException;

class UserStatisticsService
{
    public function __construct(protected LegalCaseRepository $legalCaseRepo) {}

    public function getForUserGroup(User $user, Group $group): array
    {
        $user = $user->load('points'); 
        $groupUser = $group->users()->where('user_id', $user->id)->first();

        if (! $groupUser) {
            throw ValidationException::withMessages([
                'group_id' => __('User is not a member of this group'),
            ]);
        }

        $statistics = $this->legalCaseRepo->getUserGroupStatistics($user->id, $group->id);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'image' => $user->image,
                'title' => $groupUser->pivot->title,
                'role' => $groupUser->pivot->role,
                'is_online' => $user->status === 'online',
                'points' => $user->points->total_points ?? 0,
            ],
            'statistics' => $statistics,
        ];
    }
}
