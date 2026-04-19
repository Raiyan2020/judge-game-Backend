<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'type', 'group_id'])]
class Chat extends Model
{
    #relations
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function lastMessage()
    {
        return $this->hasOne(ChatMessage::class)->latestOfMany();
    }

    public function otherUser()
    {
        $authId = auth('sanctum')->id();
        return $this->belongsToMany(User::class, 'chat_user')
            ->where('users.id', '!=', $authId)
            ->select('users.id', 'name', 'status', 'image');
    }
}
