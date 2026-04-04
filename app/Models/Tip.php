<?php

namespace App\Models;

use App\Http\Traits\ImageOperations;
use App\Http\Traits\IsActive;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['description','image','is_active'])]

class Tip extends Model
{
    use HasFactory, ImageOperations, IsActive , HasTranslations;
    public $translatable = ['description'];

}
