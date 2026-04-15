<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['chat_message_id', 'type','data', 'expires_at'])]
class ChatPoll extends Model
{
        protected $casts = [
            'data' => 'array',
            'expires_at' => 'datetime',
        ];

    public function chatMessage()
    {
        return $this->belongsTo(ChatMessage::class);
    }

    public function options()
    {
        return $this->hasMany(ChatPollOption::class);
    }
}
