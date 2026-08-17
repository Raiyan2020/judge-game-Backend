<?php

namespace App\Services;

use App\Models\Group;
use App\Models\LegalCaseNews;
use App\Models\User;
use App\Notifications\GroupEventNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Fans one group event out to the THREE channels the client asked for, so a
 * caller emits an event once instead of wiring each pipe by hand:
 *   1. News feed  — a `legal_case_news` row (group-level; `legal_case_id` null
 *      for non-case events).
 *   2. Bell       — a database + broadcast + FCM [GroupEventNotification].
 *   3. Group chat — a system message in the group's timeline.
 *
 * Every channel is fail-soft: one channel throwing (e.g. Pusher misconfigured)
 * must never abort the caller's transaction or the other channels.
 */
class GroupEventService
{
    public function __construct(protected MessageService $messages) {}

    /**
     * @param  string  $type        shared vocabulary with the app's notification switch
     * @param  array   $title       ['ar' => ..., 'en' => ...]
     * @param  array   $body        ['ar' => ..., 'en' => ...]
     * @param  ?User   $actor       who caused the event (excluded from the bell)
     * @param  ?int    $caseId      the case, when the event is case-scoped
     * @param  ?\Illuminate\Support\Collection  $notifiables  override recipients; defaults to accepted members
     */
    public function notifyGroupEvent(
        Group $group,
        string $type,
        array $title,
        array $body,
        ?User $actor = null,
        ?int $caseId = null,
        $notifiables = null,
    ): void {
        $this->pushNews($group, $type, $body, $actor, $caseId);
        $this->pushBell($group, $type, $title, $body, $actor, $caseId, $notifiables);
        $this->pushChat($group, $body);
    }

    private function pushNews(Group $group, string $type, array $body, ?User $actor, ?int $caseId): void
    {
        try {
            LegalCaseNews::create([
                'type' => $type,
                'content' => ['ar' => $body['ar'] ?? '', 'en' => $body['en'] ?? ''],
                'group_id' => $group->id,
                'legal_case_id' => $caseId,
                'actor_id' => $actor?->id,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('GroupEvent news failed: ' . $e->getMessage());
        }
    }

    private function pushBell(
        Group $group,
        string $type,
        array $title,
        array $body,
        ?User $actor,
        ?int $caseId,
        $notifiables,
    ): void {
        try {
            $recipients = $notifiables ?? $this->groupMembers($group, $actor);
            if ($recipients->isEmpty()) {
                return;
            }
            Notification::send($recipients, new GroupEventNotification([
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'group_id' => $group->id,
                'model_id' => $caseId ?? $group->id,
            ]));
        } catch (\Throwable $e) {
            \Log::warning('GroupEvent bell failed: ' . $e->getMessage());
        }
    }

    /**
     * Chat-only mirror, for events that ALREADY emit their own notification +
     * news row (the case-lifecycle events) and only need the missing chat
     * channel. [$ar] is the Arabic system-message text.
     */
    public function postChat(Group $group, string $ar): void
    {
        $this->pushChat($group, ['ar' => $ar]);
    }

    private function pushChat(Group $group, array $body): void
    {
        try {
            $this->messages->postSystemMessage($group, $body['ar'] ?? ($body['en'] ?? ''));
        } catch (\Throwable $e) {
            \Log::warning('GroupEvent chat failed: ' . $e->getMessage());
        }
    }

    /** Accepted members of the group, minus the actor (they caused the event). */
    private function groupMembers(Group $group, ?User $actor)
    {
        return $group->users()
            ->wherePivot('status', 'accepted')
            ->when($actor, fn ($q) => $q->where('users.id', '!=', $actor->id))
            ->get();
    }
}
