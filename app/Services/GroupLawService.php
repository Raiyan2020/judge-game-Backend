<?php

namespace App\Services;

use App\Models\Group;
use App\Repositories\GroupLawRepository;
use Illuminate\Validation\ValidationException;

class GroupLawService
{
    public function __construct(protected GroupLawRepository $repo)
    {
    }

    public function index(Group $group)
    {
        return $this->repo->getByGroup($group->id);
    }

    public function store(array $data)
    {
        $this->checkAdmin($data['group_id']);
        return $this->repo->create($data);
    }

    public function update($groupLaw, array $data)
    {
        $this->checkAdmin($groupLaw->group_id);
        return $this->repo->update($groupLaw, $data);
    }

    public function destroy($groupLaw)
    {
        $this->checkAdmin($groupLaw->group_id);
        return $this->repo->delete($groupLaw);
    }

    protected function checkAdmin($groupId)
    {   $group = Group::findOrFail($groupId);
        $user = auth('sanctum')->user();
        if ($user->id !== $group->user_id) {
           throw ValidationException::withMessages(['You are not the admin of this group'] );
        }
    }
}