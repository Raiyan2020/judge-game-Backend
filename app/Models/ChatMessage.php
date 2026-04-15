<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['chat_id', 'user_id', 'message', 'type', 'attachment'])]
class ChatMessage extends Model
{
  #relations
    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function poll()
    {
        return $this->hasOne(ChatPoll::class);
    }

   # Accessors & Mutators
     public function getAttachmentAttribute($value)
    {
        if ($value)
            return getimg($value);
        elseif (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        } 
    }


    public function setAttachmentAttribute($value)
    {
        if (is_file($value))
            $this->attributes['attachment'] = uploader($value);
        else
            $this->attributes['attachment'] = $value;

    }
}
