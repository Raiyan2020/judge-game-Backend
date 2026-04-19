<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['chat_poll_id', 'option'])]

class ChatPollOption extends Model
{
    public function poll()
    {
        return $this->belongsTo(ChatPoll::class, 'chat_poll_id');
    }

    public function votes()
    {
        return $this->hasMany(ChatPollVote::class, 'chat_poll_option_id');
    }
}
