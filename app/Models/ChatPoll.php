<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

// group_law_id was set in createPollRecord but missing here, so mass-assignment
// silently dropped it — an edit/delete-law poll saved a NULL group_law_id and
// applyPollResult could never find the law to change (JG-019).
// `user_id` is intentionally NOT fillable: chat_polls has no user_id column, so
// including it made every poll INSERT throw "column not found" → 500 on any
// non-owner law proposal. The proposer is already carried by the owning
// chat_message.user_id, so the poll needs none of its own.
#[Fillable(['chat_message_id', 'type', 'data', 'group_law_id', 'expires_at'])]
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

    /** The law an edit/delete proposal targets (null for a create/ads poll). */
    public function groupLaw()
    {
        return $this->belongsTo(GroupLaw::class, 'group_law_id');
    }
}
