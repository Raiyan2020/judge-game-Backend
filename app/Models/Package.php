<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

#[Fillable(['name', 'description', 'price', 'duration_days','is_active','most_sale'])]

class Package extends Model
{ 
    use HasTranslations;
    public $translatable = ['name','description'];
    
    public function subscriptions()
    {
        return $this->hasMany(PackageSubscription::class);
    }
}
