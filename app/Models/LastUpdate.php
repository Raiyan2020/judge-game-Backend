<?php

namespace App\Models;

use App\Http\Traits\ImageOperations;
use App\Http\Traits\IsActive;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['title', 'description', 'image', 'version', 'display_speed', 'is_active'])]

class LastUpdate extends Model
{
    use HasFactory, ImageOperations, HasTranslations, IsActive;

    public $translatable = ['title', 'description'];
}