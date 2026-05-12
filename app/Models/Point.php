<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

class Point extends Model
{
     public function user()
    {
        return $this->belongsTo(User::class);
    }
}
