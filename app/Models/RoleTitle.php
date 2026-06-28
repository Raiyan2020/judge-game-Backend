<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

#[Fillable(['role', 'title'])]

class RoleTitle extends Model
{
    use HasTranslations;

    public $translatable = ['title'];


    public function requirements()
    {
        return $this->hasMany(RoleTitleRequirement::class,'role_title_id');
    }
}
