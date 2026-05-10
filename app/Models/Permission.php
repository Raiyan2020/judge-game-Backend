<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

#[Fillable(['group', 'name', 'key'])]

class Permission extends Model
{
     use HasTranslations;
    public $translatable = ['name'];

    public function groupRoles()
    {
        return $this->hasMany(GroupRolePermission::class, 'permission_id');
    }

    public function groupUsers()
    {
        return $this->hasMany(GroupUserPermission::class, 'permission_id');
    }
}
