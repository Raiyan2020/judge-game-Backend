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
        return $this->belongsToMany(User::class)->withTimestamps()->withPivot('read_at');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
