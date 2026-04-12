<?php

namespace App\Services;

use App\Models\Group;
use App\Models\GroupLawRequest;
use App\Repositories\GroupLawRepository;
use Illuminate\Validation\ValidationException;

class GroupLawService
{
    public function __construct(protected GroupLawRepository $repo)
    {
    }

    public function index($groupId)
    {
        return $this->repo->getByGroup($groupId);
    }

    public function store(array $data)
    {
        $this->checkMembership($data['group_id']);

        if ($this->isGroupOwner($data['group_id'])) {
            return $this->repo->create([
                'group_id' => $data['group_id'],
                'description' => $data['description'],
                'reason' => $data['reason'] ?? null,
            ]);
        }

        return GroupLawRequest::create([
            'group_id' => $data['group_id'],
            'user_id' => auth('sanctum')->id(),
            'action' => 'create',
            'description' => $data['description'],
            'reason' => $data['reason']
           
        ]);
    }

    public function update($groupLaw, array $data)
    {
        $this->checkMembership($groupLaw->group_id);

        if ($this->isGroupOwner($groupLaw->group_id)) {
            return $this->repo->update($groupLaw, [
                'description' => $data['description'],
                'reason' => $data['reason']
            ]);
        }

        return GroupLawRequest::create([
            'group_id' => $groupLaw->group_id,
            'group_law_id' => $groupLaw->id,
            'user_id' => auth('sanctum')->id(),
            'action' => 'update',
            'description' => $data['description'],
            'reason' => $data['reason']
           
        ]);
    }

    public function destroy($groupLaw, array $data = [])
    {
        $this->checkMembership($groupLaw->group_id);

        if ($this->isGroupOwner($groupLaw->group_id)) {
            return $this->repo->delete($groupLaw);
        }

        return GroupLawRequest::create([
            'group_id' => $groupLaw->group_id,
            'group_law_id' => $groupLaw->id,
            'user_id' => auth('sanctum')->id(),
            'action' => 'delete',
            'reason' => $data['reason'] ,
           
        ]);
    }

    protected function checkMembership($groupId)
    {
        $group = Group::findOrFail($groupId);
        $user = auth('sanctum')->user();

        if ($user->id === $group->user_id) {
            return;
        }

        $isMember = $group->users()
            ->where('user_id', $user->id)
            ->wherePivot('status', 'accepted')
            ->exists();

        if (!$isMember) {
            throw ValidationException::withMessages(['You are not a member of this group']);
        }
    }

    protected function isGroupOwner($groupId)
    {
        $group = Group::findOrFail($groupId);
        return auth('sanctum')->id() === $group->user_id;
    }
}