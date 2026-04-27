<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['legal_case_id', 'opinion', 'role', 'final_requests', 'is_final','user_id'])]
class LegalCaseOpinion extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
