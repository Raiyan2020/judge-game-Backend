<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['chat_poll_option_id', 'user_id'])]
class ChatPollVote extends Model
{
    //
}
