<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

#[Fillable(['role', 'key', 'title', 'points'])]

class RoleAction extends Model
{
    use HasTranslations;
    public $translatable = ['title'];
 
    
}
