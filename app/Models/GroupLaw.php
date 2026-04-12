<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupLaw extends Model
{
    use HasFactory;

    protected $fillable = ['group_id', 'description', 'reason'];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}