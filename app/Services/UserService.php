<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class UserService
{
    public function __construct(protected UserRepository $repo) {}

    public function updateProfile($user, array $data)
    {
        // The login identity is never edited through the profile form. Both
        // fields are dropped even when a client sends them: the phone moves
        // only through the verified `/auth/phone-change/*` flow, and
        // `country_code` is half of that same identity.
        unset($data['phone'], $data['country_code']);

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
