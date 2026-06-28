<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['role_title_id', 'role_action_id', 'required_count'])]

class RoleTitleRequirement extends Model
{
    public function title()
    {
        return $this->belongsTo(RoleTitle::class,'role_title_id');
    }

    public function action()
    {
        return $this->belongsTo(RoleAction::class,'role_action_id');
    }
}
