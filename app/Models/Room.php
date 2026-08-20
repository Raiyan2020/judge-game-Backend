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

    /**
     * The room owner/host (creator). Kept independent of the `users` pivot so
     * the host name always resolves for the rooms list even when the admin is
     * not currently present in `users`.
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
