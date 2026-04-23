<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['legal_case_id', 'user_id', 'role'])]
class LegalCaseParty extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
