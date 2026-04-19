<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['chat_poll_option_id', 'user_id'])]
class ChatPollVote extends Model
{
    public function option()
    {
        return $this->belongsTo(ChatPollOption::class, 'chat_poll_option_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
