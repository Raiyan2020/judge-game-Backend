<?php

namespace App\Services;

use App\Enums\ChatPollType;
use App\Events\MessageSent;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\ChatPoll;
use App\Models\Group;
use App\Models\GroupLaw;
use Illuminate\Validation\ValidationException;

class MessageService
{
    public function index($type = 'group', $group = null)
    {
        if ($type === 'group' && $group) {
            $this->checkMembership($group);
        }

        $userId = auth('sanctum')->id();

        $query = ChatMessage::query()->with([
            'user',
            'chat',
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

        $message = $this->createPollMessage($chat->id);

        $poll = $this->createPollRecord($message->id, $data, ChatPollType::ADS->value);

        $this->createPollOptions($poll, $data['options'] ?? null);

        return $poll;
    }

    public function createPoll($data, $type)
    {
        $chat = $this->getChatByGroupId($data['group_id']);

        $message = $this->createPollMessage($chat->id);

        $poll = $this->createPollRecord($message->id, $data, $type);

        $this->createPollOptions($poll);

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
            'user_id' => auth('sanctum')->id(),
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
            $options = [
                ['option' => 'yes'],
                ['option' => 'no'],
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

        return $option->votes()->create([
            'user_id' => $userId,
        ]);
    }

    /**
     * Settle every poll whose 24h window has passed but is still open, applying
     * the winning option (create / update / delete law when "yes" wins). This
     * is the SAME rule as the `ProcessExpiredPolls` job, extracted here so it
     * can also run lazily on read (group laws / group messages fetch) — a
     * non-owner's law proposal actually resolves without a scheduler, which is
     * otherwise optional infrastructure.
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
            ->chunk(50, function ($polls) {
                foreach ($polls as $poll) {
                    // Always close it so it is never re-evaluated, THEN apply.
                    $poll->update(['is_closed' => true]);
                    $this->applyPollResult($poll);
                }
            });
    }

    protected function applyPollResult($poll): void
    {
        $yes = $poll->options->firstWhere('option', 'yes')?->votes->count() ?? 0;
        $no = $poll->options->firstWhere('option', 'no')?->votes->count() ?? 0;

        // Enact ONLY on a carried affirmative majority. A no-quorum poll (0-0)
        // or a tie must NOT pass — otherwise an unvoted "delete_law" proposal
        // would silently delete the law the moment anyone opens the laws screen.
        if ($yes === 0 || $yes <= $no) {
            return;
        }

        switch ($poll->type) {
            case ChatPollType::DELETE_LAW->value:
                GroupLaw::find($poll->group_law_id)?->delete();
                break;

            case ChatPollType::UPDATE_LAW->value:
                GroupLaw::find($poll->group_law_id)?->update([
                    'description' => $poll->data['description'] ?? null,
                    'reason' => $poll->data['reason'] ?? null,
                ]);
                break;

            case ChatPollType::CREATE_LAW->value:
                GroupLaw::create([
                    'group_id' => $poll->chatMessage?->chat?->group_id,
                    'description' => $poll->data['description'] ?? null,
                    'reason' => $poll->data['reason'] ?? null,
                ]);
                break;
        }
    }
}
