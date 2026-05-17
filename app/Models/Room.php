<?php

namespace App\Models;

use App\Http\Traits\SetPassword;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'group_id', 'user_id', 'type', 'password'])]
class Room extends Model
{
    use SetPassword;

    
    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('is_admin', 'is_muted')->withTimestamps();
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
