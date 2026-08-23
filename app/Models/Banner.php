<?php

namespace App\Models;

use App\Enums\BannerType;
use App\Http\Traits\IsActive;
use App\Http\Traits\ImageOperations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['type','title','image','url','is_active'])]

class Banner extends Model
{

    use HasFactory,ImageOperations,HasTranslations,IsActive;
    public $translatable = ['title'];

    protected function casts(): array
    {
        return [
            'type' => BannerType::class,
        ];
    }

    /**
     * Scope to one placement (home screen vs news screen).
     */
    public function scopeOfType(Builder $builder, BannerType $type): void
    {
        $builder->where('banners.type', $type->value);
    }
}
