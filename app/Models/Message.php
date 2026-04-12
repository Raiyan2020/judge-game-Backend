<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['group_id', 'user_id', 'message', 'type', 'file'])]
class Message extends Model
{
  #relations
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

   # Accessors & Mutators
     public function getFileAttribute($value)
    {
        if ($value)
            return getimg($value);
        elseif (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        } 
    }


    public function setFileAttribute($value)
    {
        if (is_file($value))
            $this->attributes['file'] = uploader($value);
        else
            $this->attributes['file'] = $value;

    }
}
