<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Message;
use Illuminate\Validation\ValidationException;

class MessageService
{
    public function index(Group $group, $type = 'group')
    {
        $this->checkMembership($group);

        $userId = auth('sanctum')->id();

        $query = $group->messages()->with(['user']);

        if ($type === 'group') {
            $query->whereNotNull('group_id')->whereNull('receiver_id');
        } else{
            // Only private messages where user is sender or receiver
            $query->whereNotNull('receiver_id')
                  ->where(function ($q) use ($userId) {
                      $q->where('user_id', $userId)
                        ->orWhere('receiver_id', $userId);
                  });
        } 

        return $query->latest()->get();
    }

    public function store(Group $group, array $data)
    {
        $this->checkMembership($group);

        // if (isset($data['receiver_id'])) {
        //     $this->checkReceiverMembership($group->id, $data['receiver_id']);
        // }
        $data['user_id'] = auth('sanctum')->id();
        $data['group_id'] = $group->id;
        $message = Message::create($data);

        return $message;
    }

    protected function checkMembership(Group $group)
    {
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

    protected function checkReceiverMembership($groupId, $receiverId)
    {
        $group = Group::findOrFail($groupId);

        if ($receiverId == auth('sanctum')->id()) {
            throw ValidationException::withMessages(['Cannot send message to yourself']);
        }

        $isMember = $group->users()
            ->where('user_id', $receiverId)
            ->wherePivot('status', 'accepted')
            ->exists();

        if (!$isMember) {
            throw ValidationException::withMessages(['Receiver is not a member of this group']);
        }
    }

   
}
