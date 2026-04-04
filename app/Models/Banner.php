<?php

namespace App\Models;

use App\Http\Traits\IsActive;
use App\Http\Traits\ImageOperations;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['title','image','url','is_active'])]

class Banner extends Model
{
    
    use HasFactory,ImageOperations,HasTranslations,IsActive;
    public $translatable = ['title'];
   
   
}
