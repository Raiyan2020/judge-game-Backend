<?php

namespace App\Models;

use App\Enums\CaseRole;
use App\Enums\LegalCaseStatus;
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

        return $this->participantRoleFor(auth()->id());
    }

    /**
     * Resolve a user's effective role in this case.
     *
     * A user may legitimately hold BOTH a party row (defendant/plaintiff) AND a
     * lawyer row (defendant_lawyer/plaintiff_lawyer) for the same case when they
     * defend themselves. In that case the lawyer role wins so the footer shows,
     * the opinion is stored under the lawyer role, and appeals are permitted.
     * A user with a single role gets exactly that role back.
     */
    public function participantRoleFor(int $userId): ?string
    {
        $rows = $this->participants->where('user_id', $userId);

        if ($rows->isEmpty()) {
            return null;
        }

        // Prefer a lawyer role when the user holds multiple party rows
        // (self-defense: a defendant who is also their own lawyer).
        $lawyer = $rows->first(fn ($p) => in_array(
            $p->role,
            [CaseRole::DEFENDANT_LAWYER->value, CaseRole::PLAINTIFF_LAWYER->value],
            true
        ));

        return $lawyer?->role ?? $rows->first()->role;
    }

    /**
     * Whether the signed-in user may INITIATE a consultation on this case:
     * they're a consultant in the case's group, the case has no consultant yet,
     * it isn't already closed/in execution, and they aren't already a party.
     *
     * The app uses this to surface the "give a consultation" entry — otherwise a
     * group consultant who never joined the case has my_role null and no action
     * (the tester's "consultant has no action"). Initiating self-assigns them
     * (see LegalCaseOpinionServices::assignConsultantIfEligible).
     */
    public function canConsult(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        if (in_array($this->status, [
            LegalCaseStatus::CLOSED->value,
            LegalCaseStatus::EXECUTION->value,
            // Not yet officially filed — held with the plaintiff lawyer. No
            // consultant may self-initiate on it (defence-in-depth beside the
            // createOpinion pending-case guard and the `show` 403).
            LegalCaseStatus::PENDING_LAWYER->value,
        ], true)) {
            return false;
        }

        // Already a party (any role) → the normal footer handles them.
        if ($this->participants->firstWhere('user_id', auth()->id())) {
            return false;
        }

        // The case must not already have a consultant.
        $hasConsultant = $this->participants
            ->firstWhere('role', CaseRole::CONSULTANT->value);
        if ($hasConsultant) {
            return false;
        }

        // The user must be a consultant in this case's group.
        return $this->group
            ? $this->group->users()
                ->where('user_id', auth()->id())
                ->wherePivot('role', CaseRole::CONSULTANT->value)
                ->exists()
            : false;
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
