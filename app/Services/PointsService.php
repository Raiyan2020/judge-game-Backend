<?php

namespace App\Services;

use App\Enums\LegalCaseJudgmentType;
use App\Models\LegalCase;
use App\Models\PointTransaction;

/**
 * Awards role-based points on judicial events, per the business scoring spec
 * (mirrors the app's `points_rules_data.dart`). Rows land in `point_transactions`
 * and the `points` SQL view aggregates them into total/judge/lawyer/consultant/
 * citizen_points — which the ranking board, best-groups board and the
 * achievement ladder already consume. Until this ran, that table was empty, so
 * every rank/score showed 0.
 *
 * Every award is IDEMPOTENT (keyed by a stable `notes` reason), so re-emitting
 * an event never double-pays.
 */
class PointsService
{
    // Judge.
    private const JUDGE_FIRST = 2;      // rules a first-instance case
    private const JUDGE_APPEAL = 1;     // rules an appeal case
    private const JUDGE_INNOCENCE = 3;  // rules innocence (acquittal)

    // Plaintiff's lawyer ("lawyer 1").
    private const PLAINTIFF_LAWYER_WIN_FIRST = 3;
    private const PLAINTIFF_LAWYER_WIN_APPEAL = 2;
    private const PLAINTIFF_LAWYER_ACQUITTAL_CLOSED = 1;

    // Defendant's lawyer ("lawyer 2").
    private const DEFENDANT_LAWYER_WIN_FIRST = 2;
    private const DEFENDANT_LAWYER_WIN_APPEAL = 3;
    private const DEFENDANT_LAWYER_WIN_ACQUITTAL = 5;

    // Consultant.
    private const CONSULTANT_PARTICIPATE = 1;

    // Citizen (plaintiff) — filing a lawsuit is the citizen's participation act,
    // mirroring the consultant's participation tier.
    private const CITIZEN_FILE_CASE = 1;

    /**
     * Insert one point row for [$userId] under [$role] ('judge'|'lawyer'|
     * 'consultant'|'citizen' — the values the `points` view groups by). No-op if
     * a row with the same [$reason] already exists (idempotent).
     */
    public function award(int $userId, string $role, int $points, string $reason): void
    {
        PointTransaction::firstOrCreate(
            ['user_id' => $userId, 'notes' => $reason],
            ['role' => $role, 'points' => $points],
        );
    }

    /**
     * First-instance ruling: the judge earns (innocence pays more than a plain
     * ruling); an ACQUITTAL is final and also pays the winning defendant side.
     */
    public function onFirstJudgment(LegalCase $case, string $judgmentType): void
    {
        $isAcquittal = $judgmentType === LegalCaseJudgmentType::ACQUITTAL->value;

        if ($case->judge?->user_id) {
            $this->award(
                $case->judge->user_id,
                'judge',
                $isAcquittal ? self::JUDGE_INNOCENCE : self::JUDGE_FIRST,
                "first_judgment:{$case->id}",
            );
        }

        if ($isAcquittal) {
            if ($case->defendantLawyer?->user_id) {
                $this->award($case->defendantLawyer->user_id, 'lawyer', self::DEFENDANT_LAWYER_WIN_ACQUITTAL, "win_acquittal:{$case->id}");
            }
            if ($case->plaintiffLawyer?->user_id) {
                $this->award($case->plaintiffLawyer->user_id, 'lawyer', self::PLAINTIFF_LAWYER_ACQUITTAL_CLOSED, "acquittal_closed:{$case->id}");
            }
        }
    }

    /**
     * The defendant lawyer accepted the conviction → the plaintiff side won at
     * first instance.
     */
    public function onJudgmentAccepted(LegalCase $case): void
    {
        if ($case->plaintiffLawyer?->user_id) {
            $this->award($case->plaintiffLawyer->user_id, 'lawyer', self::PLAINTIFF_LAWYER_WIN_FIRST, "win_first:{$case->id}");
        }
    }

    /**
     * Appeal (final) ruling: the judge earns, plus the winning side's lawyer
     * (conviction → plaintiff side; otherwise → defendant side).
     */
    public function onFinalJudgment(LegalCase $case, string $judgmentType): void
    {
        if ($case->judge?->user_id) {
            $this->award($case->judge->user_id, 'judge', self::JUDGE_APPEAL, "final_judgment:{$case->id}");
        }

        if ($judgmentType === LegalCaseJudgmentType::CONVICTION->value) {
            if ($case->plaintiffLawyer?->user_id) {
                $this->award($case->plaintiffLawyer->user_id, 'lawyer', self::PLAINTIFF_LAWYER_WIN_APPEAL, "win_appeal:{$case->id}");
            }
        } else {
            if ($case->defendantLawyer?->user_id) {
                $this->award($case->defendantLawyer->user_id, 'lawyer', self::DEFENDANT_LAWYER_WIN_APPEAL, "win_appeal:{$case->id}");
            }
        }
    }

    /**
     * A consultant was assigned as a party to the case.
     */
    public function onConsultantParticipation(LegalCase $case, int $consultantUserId): void
    {
        $this->award($consultantUserId, 'consultant', self::CONSULTANT_PARTICIPATE, "consultant_participate:{$case->id}:{$consultantUserId}");
    }

    /**
     * The plaintiff filed the lawsuit. Awarded once per case (idempotent), so
     * the filer's profile reflects citizen points the moment the case is filed
     * — the "reward" the post-filing achievement popup promises.
     */
    public function onCaseFiled(LegalCase $case, int $filerUserId): void
    {
        $this->award($filerUserId, 'citizen', self::CITIZEN_FILE_CASE, "file_case:{$case->id}:{$filerUserId}");
    }
}
