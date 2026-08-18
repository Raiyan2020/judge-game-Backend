<?php

namespace App\Services;

use App\Enums\ChatPollType;
use App\Models\Group;
use App\Models\GroupLawRequest;
use App\Repositories\GroupLawRepository;
use Illuminate\Validation\ValidationException;

class GroupLawService
{
    public function __construct(protected GroupLawRepository $repo, protected MessageService $messageService , protected GroupPermissionService $permissionService, protected GroupEventService $events) {}

    /** Fans an owner-enacted law change out to the bell, news feed and chat. */
    private function announceLaw(int $groupId, string $ar, string $en): void
    {
        $group = Group::find($groupId);
        if (! $group) {
            return;
        }
        $this->events->notifyGroupEvent(
            $group,
            'law_changed',
            title: ['ar' => 'تغيير في القوانين', 'en' => 'Law changed'],
            body: ['ar' => $ar, 'en' => $en],
            actor: auth('sanctum')->user(),
        );
    }

    public function index($groupId)
    {
        // Settle any expired law polls first, so a proposal that passed its 24h
        // vote is materialised as a real law before the list is returned
        // (the scheduler is optional — this makes approval self-contained).
        $this->messageService->resolveExpiredPolls($groupId);
        return $this->repo->getByGroup($groupId);
    }

    // The owner enacts a law change directly; ANY OTHER MEMBER opens a poll and
    // the group votes (a 24h majority-yes enacts it — see
    // MessageService::resolveExpiredPolls). The vote IS the gate, so no per-role
    // `add_laws`/`edit_law`/`delete_law` permission is required to PROPOSE —
    // that permission was never seeded, which made every non-owner proposal
    // 422 and left the whole poll mechanism unreachable.

    public function store(array $data)
    {
        $this->checkMembership($data['group_id']);

        // Owner OR a member granted `add_laws` enacts directly; everyone else
        // opens a vote. Previously ONLY the owner enacted, so a granted member's
        // permission was inert (JG-025).
        if ($this->canEnact($data['group_id'], 'add_laws')) {
            $law = $this->repo->create([
                'group_id' => $data['group_id'],
                'description' => $data['description'],
                'reason' => $data['reason'] ?? null,
            ]);
            $this->announceLaw(
                (int) $data['group_id'],
                'تم سنّ قانون جديد: ' . $data['description'],
                'New law enacted: ' . $data['description'],
            );
            return $law;
        }

        return $this->createPoll($data, ChatPollType::CREATE_LAW->value);
    }

    public function update($groupLaw, array $data)
    {
        $this->checkMembership($groupLaw->group_id);

        if ($this->canEnact($groupLaw->group_id, 'edit_law')) {
            $updated = $this->repo->update($groupLaw, [
                'description' => $data['description'],
                'reason' => $data['reason']
            ]);
            $this->announceLaw(
                (int) $groupLaw->group_id,
                'تم تعديل قانون: ' . $data['description'],
                'Law amended: ' . $data['description'],
            );
            return $updated;
        }

        return $this->createPoll(array_merge($data, ['group_law_id' => $groupLaw->id , 'group_id' => $groupLaw->group_id]), ChatPollType::UPDATE_LAW->value);
    }

    public function destroy($groupLaw, array $data = [])
    {
        $this->checkMembership($groupLaw->group_id);

        if ($this->canEnact($groupLaw->group_id, 'delete_law')) {
            $groupId = (int) $groupLaw->group_id;
            $description = $groupLaw->description;
            $result = $this->repo->delete($groupLaw);
            $this->announceLaw(
                $groupId,
                'تم حذف قانون: ' . $description,
                'Law removed: ' . $description,
            );
            return $result;
        }

        return $this->createPoll(array_merge($data, ['group_law_id' => $groupLaw->id , 'group_id' => $groupLaw->group_id]), ChatPollType::DELETE_LAW->value);
    }

    /**
     * Whether the current user may enact a law change directly (skip the vote):
     * the group owner, or a member granted [$permissionKey]
     * (add_laws / edit_law / delete_law). hasPermission already owner-short-
     * circuits, but resolving the group once keeps this readable.
     */
    protected function canEnact($groupId, string $permissionKey): bool
    {
        $group = Group::findOrFail($groupId);
        return $this->permissionService->hasPermission(
            (int) auth('sanctum')->id(),
            $group,
            $permissionKey,
        );
    }

    protected function createPoll(array $data, string $type)
    {
        return $this->messageService->createPoll($data, $type);
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
            throw ValidationException::withMessages([__('You are not a member of this group')]);
        }
    }

    public function isGroupOwner($groupId)
    {
        $group = Group::findOrFail($groupId);
        return auth('sanctum')->id() === $group->user_id;
    }
}
