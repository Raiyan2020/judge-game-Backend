<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['group_id', 'user_id', 'role_title_id'])]
class GroupUserTitle extends Model
{
    protected $table = 'group_user_titles';

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function roleTitle()
    {
        return $this->belongsTo(RoleTitle::class);
    }
}
