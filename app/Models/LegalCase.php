<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['title', 'group_id', 'group_law_id', 'description','user_id', 'status', 'point_value', 'final_judgment', 'judged_by', 'judged_at', 'is_final'])]
class LegalCase extends Model implements HasMedia
{
    use InteractsWithMedia;

    #relationships
    public function participants()
    {
        return $this->hasMany(LegalCaseParty::class);
    }
    
}
