<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class UserService
{
    public function __construct(protected UserRepository $repo) {}

    public function updateProfile($user, array $data)
    {
        return $this->repo->update($user, $data);
    }

    public function updateSettings($user, array $data)
    {
        return $this->repo->update($user, $data);
    }

    public function usersByRoleRank($request)
    {
        return $this->repo->usersByRoleRank($request);
    }
}
