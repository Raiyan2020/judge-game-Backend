<?php

namespace App\Models;

use App\Http\Traits\ImageOperations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'image', 'user_id','description'])]

class Group extends Model
{
    use ImageOperations;

  # Relations
    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role', 'status', 'title')
            ->withTimestamps();
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}


