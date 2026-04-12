<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupLawRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'group_law_id',
        'user_id',
        'action',
        'description',
        'reason',
        'status',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function groupLaw()
    {
        return $this->belongsTo(GroupLaw::class, 'group_law_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
