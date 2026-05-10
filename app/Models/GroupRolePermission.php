<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['group_id', 'permission_id','role'])]

class GroupRolePermission extends Model
{
    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
