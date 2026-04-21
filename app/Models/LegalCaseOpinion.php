<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['legal_case_id', 'opinion', 'closing_statements', 'is_final'])]
class LegalCaseOpinion extends Model implements HasMedia
{
    use InteractsWithMedia;

    
}
