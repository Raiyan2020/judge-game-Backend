<?php

namespace App\Services;

use App\Enums\ChatPollType;
use App\Events\MessageSent;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\ChatPoll;
use App\Models\Group;
use App\Models\GroupLaw;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MessageService
{
    public function index($type = 'group', $group = null)
    {
        if ($type === 'group' && $group) {
            $this->checkMembership($group);

            // Settle any poll whose 24h window elapsed BEFORE listing, so opening
            // the chat (not just the laws screen) enacts an affirmative majority
            // and closes it — the auto-execute stays reliable without the
            // scheduler. Idempotent: resolveExpiredPolls only touches open,
            // already-expired polls (M3b).
            $this->resolveExpiredPolls($group->id);
        }

        $userId = auth('sanctum')->id();

        $query = ChatMessage::query()->with([
            'user',
            'chat',
            // The targeted law, so an edit/delete poll can show before → after.
            'poll.groupLaw',
            'poll.options' => function ($q) use ($userId) {
                $q->withCount('votes')
                  ->withCount(['votes as mine_count' => function ($v) use ($userId) {
                      $v->where('user_id', $userId);
                  }]);
            }
        ]);

        if ($type === 'group') {
            $query->whereHas('chat', function ($q) use ($group) {
                $q->where('group_id', $group->id);
            });
        } else {
            // Only private messages where user is sender or receiver
            $query->whereHas('chat', function ($q) use ($userId) {
                $q->where('type', 'private')
                    ->whereHas('users', function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    });
            })->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhereHas('chat.users', function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    });
            });
        }

        return $query->latest()->get();
    }

    public function getChatMessages(array $filters = [])
    {
        $authId = auth('sanctum')->id();
        return Chat::query()
            // The "chats" inbox is the PRIVATE (one-to-one) list only. Without
            // this, group chats — which the user is always a member of — leaked
            // in, so a group message showed up as a private chat and the
            // `otherUser` picked an arbitrary group member as a bogus contact.
            ->where('type', 'private')
            ->whereHas('users', function ($q) use ($authId) {
                $q->where('user_id', $authId);
            })

            ->when(!empty($filters['name']), function ($q) use ($filters, $authId) {
                $q->whereHas('users', function ($q2) use ($filters, $authId) {
                    $q2->where('users.id', '!=', $authId)
                        ->where('name', 'like', '%' . $filters['name'] . '%');
                });
            })
            ->with([
                'lastMessage.user',
                'otherUser' => fn($q) => $q->where('users.id', '!=', $authId)
            ])
            // ->withCount([
            //     'messages as unread_count' => function ($q) use ($authId) {
            //         $q->whereNull('read_at')
            //             ->where('user_id', '!=', $authId);
            //     }
            // ])
            ->latest()
            ->get();
    }



    protected function checkMembership(Group $group)
    {
        $user = auth('sanctum')->user();

        if ($user->id === $group->user_id) {
            return;
        }

        // Null-safe on the chat: every group created through the app gets one
        // (GroupRepository::createGroupWithJudge), but a group made any other
        // way has none, and `$group->chat->users()` would then be a 500 rather
        // than a refusal. Fall back to the group pivot, which is the real
        // membership record — accepting an invitation writes both.
        $isMember = $group->chat
            ? $group->chat->users()->where('user_id', $user->id)->exists()
            : $group->users()
                ->where('users.id', $user->id)
                ->wherePivot('status', 'accepted')
                ->exists();

        if (!$isMember) {
            throw ValidationException::withMessages(['You are not a member of this group']);
        }
    }


    public function getGroupChat(Group $group)
    {
        return Chat::where('group_id', $group->id)->first();
    }

    /**
     * Posts a SYSTEM message into a group's chat timeline — a message NOT tied
     * to a live user (`user_id` null, `type` system). Used by
     * [GroupEventService] to mirror an event into the chat. Broadcasts so it
     * appears live. No-op when the group has no chat yet. Text is the Arabic
     * body (the app is Arabic-first, same call as the poll defaults).
     */
    public function postSystemMessage(Group $group, string $text): void
    {
        $chat = $this->getGroupChat($group);
        if (! $chat) {
            return;
        }

        $message = ChatMessage::create([
            'user_id' => null,
            'chat_id' => $chat->id,
            'message' => $text,
            'type' => 'system',
        ]);

        try {
            // NOT ->toOthers(): a system message is held by no client (nobody
            // POSTed it), so the actor's own chat would otherwise miss the
            // notice everyone else sees until they reload.
            broadcast(new MessageSent($message));
        } catch (\Throwable $e) {
            \Log::warning('Broadcast system MessageSent failed: ' . $e->getMessage());
        }
    }

    public function getOrCreatePrivateChat(int $userId): Chat
    {
        $authId = auth('sanctum')->id();

        $chat = Chat::where('type', 'private')
            ->whereHas('users', function ($q) use ($authId) {
                $q->where('user_id', $authId);
            })
            ->whereHas('users', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->withCount('users')
            ->having('users_count', 2)
            ->first();

        if ($chat) {
            return $chat;
        }

        $chat = Chat::create([
            'type' => 'private',
        ]);

        $chat->users()->sync([$authId, $userId]);

        return $chat;
    }
    public function sendMessage(array $data)
    {
        $userId = auth('sanctum')->id();

        if ($data['group_id'] ?? null) {
            $group = Group::find($data['group_id']);
            $chat = $this->getGroupChat($group);
            $this->checkMembership($group);
        } else {
            $chat = $this->getOrCreatePrivateChat($data['receiver_id']);
        }

        $message = ChatMessage::create([
            'user_id' => $userId,
            'chat_id' => $chat->id,
            'message' => $data['message'] ?? null,
            'type' => $data['type'] ?? 'text',
            'attachment' => $data['attachment'] ?? null,
        ]);

        // Fail-soft: a broadcast error (e.g. misconfigured Pusher creds) must
        // never fail the send itself — the message is already persisted.
        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Throwable $e) {
            \Log::warning('Broadcast MessageSent failed: ' . $e->getMessage());
        }

        return $message;
    }

    public function CreateAdsPoll(array $data)
    {
        // Publishing an announcement is a WRITE into the group timeline and
        // must be gated exactly like sendMessage(). Without this a user who
        // could not even read the group (getGroupMessages refuses them) could
        // still post a poll into it by passing its id.
        $this->checkMembership(Group::findOrFail($data['group_id']));

        $chat = $this->getChatByGroupId($data['group_id']);

        // The message + poll + options must be created atomically. Without a
        // transaction, a failure after createPollMessage() left an orphan
        // `type=poll` ChatMessage with NO ChatPoll row — which the app rendered
        // as a BLANK chat bubble ("the announcement published empty") while the
        // request itself still 500'd. Rolling back means a failed publish leaves
        // no trace in the timeline.
        [$message, $poll] = DB::transaction(function () use ($chat, $data) {
            $message = $this->createPollMessage($chat->id);
            $poll = $this->createPollRecord($message->id, $data, ChatPollType::ADS->value);
            $this->createPollOptions($poll, $data['options'] ?? null);

            return [$message, $poll];
        });

        // Broadcast the poll message so other members see the announcement live,
        // exactly like a text message (fail-soft — a broadcast error must never
        // fail the already-persisted poll).
        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Throwable $e) {
            \Log::warning('Broadcast poll MessageSent failed: ' . $e->getMessage());
        }

        return $poll;
    }

    public function createPoll($data, $type)
    {
        $chat = $this->getChatByGroupId($data['group_id']);

        // Atomic, exactly like CreateAdsPoll: without a transaction a failure
        // after createPollMessage() left an orphan `type=poll` ChatMessage with
        // no ChatPoll row, which the app renders as a blank bubble.
        [$message, $poll] = DB::transaction(function () use ($chat, $data, $type) {
            $message = $this->createPollMessage($chat->id);
            $poll = $this->createPollRecord($message->id, $data, $type);
            $this->createPollOptions($poll);

            return [$message, $poll];
        });

        // Broadcast the poll message so the proposed law's vote card appears in
        // «دراسة القانون» live — this was the missing piece vs CreateAdsPoll /
        // postSystemMessage (the law-vote card never showed in the open chat).
        // NOT ->toOthers(): the proposer's own chat must receive it too (mirrors
        // postSystemMessage). Fail-soft: a broadcast error must not fail the poll.
        try {
            broadcast(new MessageSent($message));
        } catch (\Throwable $e) {
            \Log::warning('Broadcast law-poll MessageSent failed: ' . $e->getMessage());
        }

        return $poll;
    }

    protected function getChatByGroupId($groupId)
    {
        return Chat::where('group_id', $groupId)->firstOrFail();
    }

    protected function createPollMessage($chatId)
    {
        return ChatMessage::create([
            'user_id' => auth('sanctum')->id(),
            'chat_id' => $chatId,
            'type' => 'poll',
        ]);
    }

    protected function createPollRecord($messageId, $data, $type)
    {
        return ChatPoll::create([
            'chat_message_id' => $messageId,
            // No 'user_id' — chat_polls has no such column (the INSERT 500'd);
            // the proposer is the owning chat_message's user_id.
            'type' => $type,
            'group_law_id' => $data['group_law_id'] ?? null,
            'data' => [
                'description' => $data['description'] ?? null,
                'reason' => $data['reason'] ?? null,
            ],
            'expires_at' => now()->addHours(24),
        ]);
    }
    protected function createPollOptions($poll, $options = null)
    {
        if ($options) {
            $options = collect($options)->map(function ($option) {
                return ['option' => $option];
            })->toArray();
        } else {
            // Arabic defaults — the app is Arabic-first and these render raw.
            $options = [
                ['option' => 'نعم'],
                ['option' => 'لا'],
            ];
        }

        return $poll->options()->createMany($options);
    }

    public function votePoll($messageId, $optionId)
    {
        $poll = ChatPoll::where('chat_message_id', $messageId)->firstOrFail();

        // Voting is a write into the group's poll, so it needs the same
        // membership gate as posting. Resolve the poll back to its group
        // through its chat; a private-chat poll has no group and is left alone.
        $group = $poll->chatMessage?->chat?->group;

        if ($group) {
            $this->checkMembership($group);
            $this->assertCanVote($group);
        }

        // Closed OR past its expiry — the app also stops sending a vote here.
        if ($poll->is_closed || ($poll->expires_at && $poll->expires_at->isPast())) {
            throw ValidationException::withMessages(['This poll is closed']);
        }

        $option = $poll->options()->where('id', $optionId)->firstOrFail();
        $userId = auth('sanctum')->id();

        // MOVE the user's vote: drop any existing vote across the WHOLE poll
        // first, then record the new one — so a user is never counted in two
        // options (the old code only checked the option being voted for, which
        // let "yes" then "no" both stick).
        \App\Models\ChatPollVote::whereIn('chat_poll_option_id', $poll->options()->pluck('id'))
            ->where('user_id', $userId)
            ->delete();

        $vote = $option->votes()->create([
            'user_id' => $userId,
        ]);

        // Push the new tallies to the channel so every member's bar moves live
        // (fail-soft — a broadcast error must never fail the recorded vote).
        try {
            $poll->load('chatMessage', 'options');
            broadcast(new \App\Events\PollVoted($poll))->toOthers();
        } catch (\Throwable $e) {
            \Log::warning('Broadcast PollVoted failed: ' . $e->getMessage());
        }

        return $vote;
    }

    /**
     * Enforces the `vote_rights` permission WITHOUT breaking the default open
     * voting the poll system relies on. Permissions are deny-by-default with no
     * default grants, so hard-gating every vote would let non-owners never vote
     * (and no non-owner law proposal could ever pass). Instead this is opt-in:
     * voting stays open until an admin GRANTS `vote_rights` to at least one role
     * or user in the group — from then on it's a real gate (owner always votes;
     * others need the grant). JG-025.
     */
    private function assertCanVote(Group $group): void
    {
        $userId = (int) auth('sanctum')->id();
        if ($group->user_id === $userId) {
            return; // Owner always votes.
        }

        $permissionId = \App\Models\Permission::where('key', 'vote_rights')->value('id');
        if (! $permissionId) {
            return; // Permission not seeded → don't gate.
        }

        $configured = $group->permissions()->where('permission_id', $permissionId)->exists()
            || $group->userPermissions()->where('permission_id', $permissionId)->exists();
        if (! $configured) {
            return; // Nobody restricted voting yet → stays open (backward compat).
        }

        if (! app(GroupPermissionService::class)->hasPermission($userId, $group, 'vote_rights')) {
            throw ValidationException::withMessages([
                'vote' => __('You do not have voting rights in this group'),
            ]);
        }
    }

    /**
     * Settle every poll whose 24h window has passed but is still open, enacting
     * the law change (create / update / delete) when the approve option carries
     * a majority. This is the SAME rule as the `ProcessExpiredPolls` job,
     * extracted here so it can also run lazily on read (group laws / group
     * messages fetch) — a non-owner's law proposal actually resolves without a
     * scheduler, which is otherwise optional infrastructure.
     */
    public function resolveExpiredPolls($groupId = null): void
    {
        ChatPoll::query()
            ->where('expires_at', '<=', now())
            ->where('is_closed', false)
            ->when($groupId, function ($q) use ($groupId) {
                $q->whereHas('chatMessage.chat', function ($sub) use ($groupId) {
                    $sub->where('group_id', $groupId);
                });
            })
            ->with(['options.votes', 'chatMessage.chat'])
            // chunkById (NOT chunk): the query filters on `is_closed = false` and
            // the loop flips that to true, so OFFSET-based chunk() would skip the
            // next page of unsettled polls once a claim actually closes a row.
            // chunkById keys off `id > last`, stable under this mutation.
            ->chunkById(50, function ($polls) {
                foreach ($polls as $poll) {
                    try {
                        // Close + apply atomically, and claim the poll so exactly
                        // ONE reader settles it — concurrent chat opens / the job
                        // can otherwise double-enact a law.
                        $announcement = DB::transaction(function () use ($poll) {
                            $claimed = ChatPoll::where('id', $poll->id)
                                ->where('is_closed', false)
                                ->update(['is_closed' => true]);

                            // Another request already settled this poll → skip it.
                            if ($claimed !== 1) {
                                return null;
                            }

                            return $this->applyPollResult($poll);
                        });

                        // Fire notifications AFTER the transaction commits:
                        // notifyGroupEvent does Pusher + FCM + DB writes and must
                        // not run while the poll row is locked in the txn (this
                        // path also runs on chat open, via index()).
                        if ($announcement) {
                            $this->firePollAnnouncement($announcement);
                        }
                    } catch (\Throwable $e) {
                        // One bad poll must never break the loop or 500 the caller
                        // (resolveExpiredPolls runs inside index() on chat open).
                        \Log::warning(
                            'resolveExpiredPolls: poll ' . ($poll->id ?? '?')
                            . ' failed: ' . $e->getMessage()
                        );
                    }
                }
            });
    }

    /**
     * Settle ONE already-claimed poll: tally, enact the law change on a carried
     * majority, and stamp `result`. Returns an announcement descriptor (or null)
     * so the caller can notify AFTER the transaction commits — this method only
     * touches the DB (see resolveExpiredPolls). Must be called with the poll's
     * is_closed already flipped to true (the atomic claim).
     */
    protected function applyPollResult($poll): ?array
    {
        // Only law polls carry an approve/reject decision. An ADS poll has
        // arbitrary custom options, so it just closes here (is_closed is already
        // claimed) — no positional tally, no result, no announcement.
        $lawTypes = [
            ChatPollType::CREATE_LAW->value,
            ChatPollType::UPDATE_LAW->value,
            ChatPollType::DELETE_LAW->value,
        ];
        if (! in_array($poll->type, $lawTypes, true)) {
            return null;
        }

        // Tally by POSITION, not by label. Law-poll options are ALWAYS created in
        // order [approve, reject] (نعم then لا) by createPollOptions() with no
        // explicit options — the order is backend-controlled. The labels are
        // Arabic نعم/لا (never 'yes'/'no'), so the old firstWhere('option','yes')
        // lookup counted 0-0 every time and NO proposal ever enacted. sortBy('id')
        // makes the creation order deterministic (the eager load has no ORDER BY).
        $ordered = $poll->options->sortBy('id')->values();
        $approveCount = $ordered->first()?->votes->count() ?? 0;
        $rejectCount = $ordered->skip(1)->first()?->votes->count() ?? 0;

        // Enact ONLY on a carried affirmative majority. 0-0 (no quorum) or a tie
        // must NOT pass — otherwise an unvoted "delete_law" proposal would
        // silently delete the law the moment anyone opens the laws screen.
        $enacted = $approveCount > 0 && $approveCount > $rejectCount;

        $groupId = $poll->chatMessage?->chat?->group_id;
        $announcement = null;

        if ($enacted) {
            switch ($poll->type) {
                case ChatPollType::DELETE_LAW->value:
                    $law = GroupLaw::find($poll->group_law_id);
                    if (! $law) {
                        // Target already gone → not a real enactment.
                        $enacted = false;
                        break;
                    }
                    $description = $law->description;
                    // nullOnDelete keeps the poll row + votes as an audit trail.
                    $law->delete();
                    $announcement = $this->approvedAnnouncement($groupId, 'تم حذف قانون بالتصويت', 'Law removed by vote', $description);
                    break;

                case ChatPollType::UPDATE_LAW->value:
                    $law = GroupLaw::find($poll->group_law_id);
                    if (! $law) {
                        $enacted = false;
                        break;
                    }
                    $description = $poll->data['description'] ?? null;
                    $attrs = ['description' => $description];
                    // Only overwrite the reason when the amendment actually
                    // carried one; a null/blank reason keeps the law's current
                    // reason instead of wiping it.
                    if (filled($poll->data['reason'] ?? null)) {
                        $attrs['reason'] = $poll->data['reason'];
                    }
                    $law->update($attrs);
                    $announcement = $this->approvedAnnouncement($groupId, 'تم تعديل قانون بالتصويت', 'Law amended by vote', $description);
                    break;

                case ChatPollType::CREATE_LAW->value:
                    $description = $poll->data['description'] ?? null;
                    GroupLaw::create([
                        'group_id' => $groupId,
                        'description' => $description,
                        'reason' => $poll->data['reason'] ?? null,
                    ]);
                    $announcement = $this->approvedAnnouncement($groupId, 'تم سنّ قانون جديد بالتصويت', 'New law enacted by vote', $description);
                    break;
            }
        }

        // Persist the outcome alongside the close. Query-builder update: bypasses
        // mass-assignment (is_closed / result are intentionally NOT fillable) and
        // never clobbers the is_closed already claimed in this transaction.
        ChatPoll::where('id', $poll->id)->update([
            'is_closed' => true,
            'result' => $enacted ? 'approved' : 'rejected',
        ]);

        // Announce a rejection too — a proposal that failed the vote (or whose
        // target law was already gone) tells the group it was rejected.
        if (! $enacted) {
            $announcement = $this->rejectedAnnouncement($groupId, $poll->type);
        }

        return $announcement;
    }

    /**
     * Builds the announcement descriptor for an ENACTED law change (no side
     * effects — the caller fires it after the transaction commits).
     */
    private function approvedAnnouncement($groupId, string $ar, string $en, ?string $description): ?array
    {
        if (! $groupId) {
            return null;
        }
        $suffix = $description ? (': ' . $description) : '';
        return [
            'groupId' => $groupId,
            'title' => ['ar' => 'تغيير في القوانين', 'en' => 'Law changed'],
            'body' => ['ar' => $ar . $suffix, 'en' => $en . $suffix],
        ];
    }

    /**
     * Builds the announcement descriptor for a REJECTED law proposal (failed the
     * vote or its target law was already gone). Same three-channel path as an
     * approval, so the group sees the outcome either way.
     */
    private function rejectedAnnouncement($groupId, string $type): ?array
    {
        if (! $groupId) {
            return null;
        }
        $labels = [
            ChatPollType::CREATE_LAW->value => ['ar' => 'إضافة', 'en' => 'add'],
            ChatPollType::UPDATE_LAW->value => ['ar' => 'تعديل', 'en' => 'edit'],
            ChatPollType::DELETE_LAW->value => ['ar' => 'حذف', 'en' => 'delete'],
        ];
        $label = $labels[$type] ?? ['ar' => '', 'en' => ''];
        return [
            'groupId' => $groupId,
            'title' => ['ar' => 'تغيير في القوانين', 'en' => 'Law changed'],
            'body' => [
                'ar' => 'رُفض اقتراح ' . $label['ar'] . ' قانون بالتصويت',
                'en' => 'The ' . $label['en'] . '-law proposal was rejected by vote',
            ],
        ];
    }

    /**
     * Fires a poll outcome on all three channels (news + bell + group chat). The
     * vote is a collective decision (no single actor), so everyone in the group
     * is notified. Resolved lazily (`app(...)`) to avoid the MessageService ↔
     * GroupEventService constructor cycle. Called OUTSIDE the settlement
     * transaction — notifyGroupEvent is itself fail-soft per channel.
     */
    private function firePollAnnouncement(array $announcement): void
    {
        $group = Group::find($announcement['groupId']);
        if (! $group) {
            return;
        }
        app(\App\Services\GroupEventService::class)->notifyGroupEvent(
            $group,
            'law_changed',
            title: $announcement['title'],
            body: $announcement['body'],
            actor: null,
        );
    }
}
