<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['title', 'group_id', 'description', 'user_id', 'status', 'point_value', 'final_judgment', 'judged_by', 'judged_at', 'is_final'])]
class LegalCase extends Model implements HasMedia
{
    use InteractsWithMedia;

    #relationships
    public function participants()
    {
        return $this->hasMany(LegalCaseParty::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function groupLaws()
    {
        return $this->belongsToMany(GroupLaw::class, 'legal_case_group_law');
    }
    public function opinions()
    {
        return $this->hasMany(LegalCaseOpinion::class);
    }

    public function plaintiff()
    {
        return $this->hasOne(LegalCaseParty::class)->where('role', 'plaintiff');
    }

    public function defendant()
    {
        return $this->hasOne(LegalCaseParty::class)->where('role', 'defendant');
    }

    public function witnesses()
    {
        return $this->hasMany(LegalCaseParty::class)->where('role', 'witness');
    }
}
