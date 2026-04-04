<?php

namespace App\Models;

use App\Http\Traits\ImageOperations;
use App\Http\Traits\IsActive;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name','image', 'country_code','is_active'])]

class Country extends Model
{
    use HasFactory,HasTranslations,ImageOperations,IsActive;
    public $translatable = ['name'];
}
