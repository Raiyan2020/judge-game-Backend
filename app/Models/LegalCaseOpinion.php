<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['legal_case_id', 'opinion', 'role', 'final_requests', 'legal_arguments','stage','user_id','is_correct'])]
class LegalCaseOpinion extends Model implements HasMedia
{
    use InteractsWithMedia;
    protected $casts = [
        'legal_arguments' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function legalCase()
    {
        return $this->belongsTo(LegalCase::class);
    }
}
