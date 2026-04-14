<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\Chat;
use App\Models\Group;
use App\Models\Message;
use Illuminate\Validation\ValidationException;

class MessageService
{
    public function index(Group $group, $type = 'group')
    {
        $this->checkMembership($group);

        $userId = auth('sanctum')->id();

        $query = Message::query()->with('user','chat');

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

   

    protected function checkMembership(Group $group)
    {
        $user = auth('sanctum')->user();

        if ($user->id === $group->user_id) {
            return;
        }

        $isMember = $group->chat->users()
            ->where('user_id', $user->id)
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

        // create new chat
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

        $message = Message::create([
            'user_id' => $userId,
            'chat_id' => $chat->id,
            'message' => $data['message'] ?? null,
            'type' => $data['type'] ?? 'text',
            'attachment' => $data['attachment'] ?? null,
        ]);

           broadcast(new MessageSent($message))->toOthers();

           return $message;

    }
}
