<?php

namespace App\Models;

use App\Enums\CaseRole;
use App\Models\LegalCaseJudgment;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['title', 'group_id', 'description', 'user_id', 'status', 'damages','winner_id'])]
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

    public function judgments()
    {
        return $this->hasMany(LegalCaseJudgment::class);
    }

    public function firstInstanceJudgment()
    {
        return $this->hasOne(LegalCaseJudgment::class)
            ->where('stage', \App\Enums\LegalCaseJudgmentStage::FIRST_INSTANCE->value);
    }

    public function finalJudgment()
    {
        return $this->hasOne(LegalCaseJudgment::class)
            ->where('is_final', true);
    }

    public function plaintiff()
    {
        return $this->hasOne(LegalCaseParty::class)->where('role', CaseRole::PLAINTIFF->value);
    }

    public function defendant()
    {
        return $this->hasOne(LegalCaseParty::class)->where('role', CaseRole::DEFENDANT->value);
    }

    public function plaintiffLawyer()
    {
        return $this->hasOne(LegalCaseParty::class)->where('role', CaseRole::PLAINTIFF_LAWYER->value);
    }
    public function defendantLawyer()
    {
        return $this->hasOne(LegalCaseParty::class)->where('role', CaseRole::DEFENDANT_LAWYER->value);
    }

    public function judge()
    {
        return $this->hasOne(LegalCaseParty::class)->where('role', CaseRole::JUDGE->value);
    }

    public function consultant()
    {
        return $this->hasOne(LegalCaseParty::class)->where('role', CaseRole::CONSULTANT->value);
    }

    public function myRole()
    {
        if (!auth()->check()) {
            return null;
        }

        $participant = $this->participants->firstWhere('user_id', auth()->id());
        return $participant ? $participant->role : null;
    }

    public function witnesses()
    {
        return $this->hasMany(LegalCaseParty::class)->where('role', CaseRole::WITNESS->value);
    }

    public function news()
    {
        return $this->hasMany(LegalCaseNews::class);
    }

    public function hearings()
    {
        return $this->hasMany(Hearing::class)->orderBy('scheduled_at');
    }
}
