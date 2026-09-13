<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

#[Fillable(['type', 'content', 'legal_case_id', 'actor_id', 'subject_id', 'group_id'])]

class LegalCaseNews extends Model
{
    use HasTranslations;
    public $translatable = ['content'];
    protected $table = 'legal_case_news';


    #relationships
    public function legalCase()
    {
        return $this->belongsTo(LegalCase::class, 'legal_case_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * The event's SUBJECT — a USER, not a case: every writer stores a user id
     * here (the defendant for `case_created`, the joining member for
     * `member_joined`, the earner for `achievement_earned`), and the table's own
     * foreign key is `subject_id → users.id`.
     *
     * This was declared `belongsTo(LegalCase::class)`, so eager-loading it (the
     * news list does) resolved a user id against `legal_cases` — usually null,
     * occasionally an unrelated case. Nothing could render a subject name.
     */
    public function subject()
    {
        return $this->belongsTo(User::class, 'subject_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }


    /**
     * Renders the news sentence for [$viewer] (the signed-in reader, passed by
     * [LegalCaseNewsResource]; null renders the neutral third-person form).
     *
     * A `case_created` row stores `actor_id` = the FILER and `subject_id` = the
     * DEFENDANT, but the sentence was built as `content + "against" + ACTOR`, so
     * it named the filer as the target — the person who filed a case read that a
     * case had been filed against them (M-04), and no reader ever saw the real
     * defendant. The sentence is now derived from the two ids + the viewer, so
     * EXISTING ROWS are corrected retroactively: nothing about the stored
     * content/ids changes, only how they are read.
     *
     * @param  \App\Models\User|int|null  $viewer
     */
    public function generateContent($viewer = null)
    {
        switch ($this->type) {
            case 'case_created':
                return $this->caseCreatedContent($this->viewerId($viewer));
            case 'opinion_added':
                return "تم إضافة رأي جديد";
            case 'case_first_judgment':
                return $this->content . " " . __('by') . " " . $this->actor?->name;    

            case 'case_final_judgment':
                return $this->content . " " . __('by') . " " . $this->actor?->name;
            case 'case_appeal_request':
                return $this->content . " " . __('by') . " " . $this->actor?->name;

            case 'case_appeal_accepted':
                return $this->content . " " . __('by') . " " . $this->actor?->name;
            case 'case_acceptance_ruling':
                return $this->content . " " . __('by') . " " . $this->actor?->name;

            default:
                return $this->content;
        }
    }

    /** Normalize a viewer (model, id, or null) to an int id. */
    private function viewerId($viewer): ?int
    {
        if ($viewer instanceof User) {
            return (int) $viewer->id;
        }

        return is_numeric($viewer) ? (int) $viewer : null;
    }

    /**
     * The filing sentence, written from the READER's side:
     *   the filer  → «رفعت قضية ضد فلان»      (I filed)
     *   the defendant → «رُفعت قضية ضدك من فلان» (it was filed against me)
     *   anyone else → «رفع فلان قضية ضد فلان»   (neutral third person)
     *
     * Falls back to the stored, role-neutral content whenever a name is missing
     * (a deleted user, or a row written without a subject) — never renders a
     * dangling «ضد » with nothing after it.
     */
    private function caseCreatedContent(?int $viewerId): string
    {
        $ar = app()->getLocale() === 'ar';
        $actorName = $this->actor?->name;
        $subjectName = $this->subject?->name;

        if ($viewerId !== null && (int) $this->actor_id === $viewerId) {
            return $subjectName
                ? ($ar ? "رفعت قضية ضد {$subjectName}" : "You filed a case against {$subjectName}")
                : ($ar ? 'رفعت قضية' : 'You filed a case');
        }

        if ($viewerId !== null && (int) $this->subject_id === $viewerId) {
            return $actorName
                ? ($ar ? "رُفعت قضية ضدك من {$actorName}" : "A case was filed against you by {$actorName}")
                : ($ar ? 'رُفعت قضية ضدك' : 'A case was filed against you');
        }

        if ($actorName && $subjectName) {
            return $ar
                ? "رفع {$actorName} قضية ضد {$subjectName}"
                : "{$actorName} filed a case against {$subjectName}";
        }

        if ($actorName) {
            return $ar ? "رفع {$actorName} قضية" : "{$actorName} filed a case";
        }

        return (string) $this->content;
    }
}
