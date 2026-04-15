<?php

namespace App\Services;

use App\Enums\ChatPollType;
use App\Models\Group;
use App\Models\GroupLawRequest;
use App\Repositories\GroupLawRepository;
use Illuminate\Validation\ValidationException;

class GroupLawService
{
    public function __construct(protected GroupLawRepository $repo, protected MessageService $messageService) {}

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

        return $this->createPoll($data, ChatPollType::CREATE_LAW->value);
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

        return $this->createPoll(array_merge($data, ['group_law_id' => $groupLaw->id , 'group_id' => $groupLaw->group_id]), ChatPollType::UPDATE_LAW->value);
    }

    public function destroy($groupLaw, array $data = [])
    {
        $this->checkMembership($groupLaw->group_id);

        if ($this->isGroupOwner($groupLaw->group_id)) {
            return $this->repo->delete($groupLaw);
        }

        return $this->createPoll(array_merge($data, ['group_law_id' => $groupLaw->id , 'group_id' => $groupLaw->group_id]), ChatPollType::DELETE_LAW->value);
    }

    protected function createPoll(array $data, string $type)
    {

        $this->messageService->createPoll($data, $type);
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

    public function isGroupOwner($groupId)
    {
        $group = Group::findOrFail($groupId);
        return auth('sanctum')->id() === $group->user_id;
    }
}
