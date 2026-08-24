<?php

namespace App\Services;

use App\Enums\LegalCaseJudgmentStage;
use App\Enums\LegalCaseJudgmentType;
use App\Enums\LegalCaseStatus;
use App\Models\LegalCase;
use App\Models\User;
use App\Notifications\LegalCaseNotification;
use App\Repositories\LegalCaseJudgmentRepository;
use App\Repositories\LegalCaseRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class LegalCaseJudgmentService
{
    public function __construct(
        protected LegalCaseJudgmentRepository $judgmentRepo,
        protected LegalCaseRepository $legalCaseRepo,
        protected PointsService $points,
        protected GroupEventService $events
    ) {}

    /** Mirrors a case event into the group chat (bell + news already sent). */
    private function postCaseChat(LegalCase $legalCase, string $ar): void
    {
        $group = $legalCase->group;
        if ($group) {
            $this->events->postChat($group, $ar . ': ' . $legalCase->title);
        }
    }

    public function storeFirstJudgment(array $data)
    {
        try {
            DB::beginTransaction();

            $legalCase = $this->legalCaseRepo->find($data['legal_case_id']);

            if (! $legalCase) {
                throw ValidationException::withMessages([
                    'legal_case_id' => __('Legal case not found'),
                ]);
            }

            $legalCase->load(['plaintiff', 'defendant', 'plaintiffLawyer', 'defendantLawyer', 'consultant']);

            $this->ensureUserIsJudge($legalCase);
            $this->ensureCaseIsNotClosed($legalCase);
            $isFinal = in_array($data['judgment_type'], [LegalCaseJudgmentType::DISMISSED->value, LegalCaseJudgmentType::ACQUITTAL->value]);

            if ($this->judgmentRepo->hasStageForCase($legalCase->id, LegalCaseJudgmentStage::FIRST_INSTANCE->value)) {
                throw ValidationException::withMessages([
                    'legal_case_id' => __('A first instance judgment already exists for this case'),
                ]);
            }


            $judgment = $this->judgmentRepo->create([
                'legal_case_id' => $legalCase->id,
                'judgment_type' => $data['judgment_type'],
                'stage' => LegalCaseJudgmentStage::FIRST_INSTANCE->value,
                'judgment_text' => $data['judgment_text'],
                'judged_by' => auth()->id(),
                'is_final' => $isFinal,
            ]);

            $this->createCaseNews($legalCase, $judgment);
            $this->postCaseChat($legalCase, 'صدر حكم أول درجة في قضية');
            $winnerId = null;

            if ($data['judgment_type'] === LegalCaseJudgmentType::ACQUITTAL->value) {
                $winnerId = $legalCase->defendant?->user_id;
            }

            if ($isFinal) {
                $legalCase->update(['status' => LegalCaseStatus::CLOSED->value, 'winner_id' => $winnerId]);
            } else {
                $legalCase->update(['status' => LegalCaseStatus::ONGOING->value]);
            }

            // Award role points for the first-instance ruling (judge; acquittal
            // also pays the winning defendant side).
            $this->points->onFirstJudgment($legalCase, $data['judgment_type']);

            DB::commit();

            return $legalCase;
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function storeFinalJudgment(array $data)
    {
        try {
            DB::beginTransaction();

            $legalCase = $this->legalCaseRepo->find($data['legal_case_id']);

            if (! $legalCase) {
                throw ValidationException::withMessages([
                    'legal_case_id' => __('Legal case not found'),
                ]);
            }

            $this->ensureUserIsJudge($legalCase);
            $this->ensureCaseIsNotClosed($legalCase);

            // A final judgment is the APPEAL-stage ruling — it may only be
            // issued for a case actually under appeal. Without this a judge
            // could jump `ongoing → execution`, skipping the appeal entirely
            // AND recording a `stage = appeal` judgment for an appeal that
            // never happened.
            if ($legalCase->status !== LegalCaseStatus::APPEAL->value) {
                throw ValidationException::withMessages([
                    'legal_case_id' => __('A final judgment can only be issued for a case under appeal'),
                ]);
            }

            if ($legalCase->finalJudgment) {
                throw ValidationException::withMessages([
                    'legal_case_id' => __('A final judgment already exists for this case'),
                ]);
            }

            $judgment = $this->judgmentRepo->create([
                'legal_case_id' => $legalCase->id,
                'judgment_type' => $data['judgment_type'],
                'stage' => LegalCaseJudgmentStage::APPEAL->value,
                'judgment_text' => $data['judgment_text'],
                'judged_by' => auth()->id(),
                'is_final' => true,
            ]);

            $legalCase->update(['status' => LegalCaseStatus::EXECUTION->value, 'winner_id' => $data['judgment_type'] === LegalCaseJudgmentType::CONVICTION->value ? $legalCase->plaintiff?->user_id : $legalCase->defendant?->user_id]);

            $this->createFinalJudgmentNews($legalCase, $judgment);
            $this->postCaseChat($legalCase, 'صدر الحكم النهائي في قضية');

            // Award role points for the appeal (final) ruling.
            $this->points->onFinalJudgment($legalCase, $data['judgment_type']);

            DB::commit();

            return $legalCase;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function createFinalJudgmentNews(LegalCase $legalCase, $judgment): void
    {
        $message = $this->getFinalJudgmentNotificationData($judgment, $legalCase);

        $this->legalCaseRepo->createCaseNews(
            $legalCase,
            'case_final_judgment',
            $message,
            auth()->id(),
            $legalCase->defendant?->user_id
        );

        $notificationData = [
            'model_id' => $legalCase->id,
            'title' => [
                'ar' => 'حكم نهائي',
                'en' => 'Final Judgment',
            ],
            'body' => $message,
            'type' => 'final_judgment',
        ];

        $users = User::whereIn('id', $this->getJudgmentRecipients($legalCase))->get();
        Notification::send($users, new LegalCaseNotification($legalCase, $notificationData));
    }

    private function getFinalJudgmentNotificationData($judgment, $legalCase): array
    {
        return match ($judgment->judgment_type) {
            LegalCaseJudgmentType::CONVICTION->value => [
                'ar' => 'صدر حكم نهائي بالإدانة للقضيه رقم ' . $legalCase->id,
                'en' => 'A final judgment of conviction was issued for case number ' . $legalCase->id,
            ],
            LegalCaseJudgmentType::ACQUITTAL->value => [
                'ar' => 'صدر حكم نهائي بالبراءة  للقضيه رقم ' . $legalCase->id,
                'en' => 'A final judgment of acquittal was issued for case number ' . $legalCase->id,
            ],
            LegalCaseJudgmentType::DISMISSED->value => [
                'ar' => 'صدر حكم نهائي بالرفض للقضيه رقم ' . $legalCase->id,
                'en' => 'A final judgment of dismissal was issued for case number ' . $legalCase->id,
            ],
            default => [
                'ar' => 'صدر حكم نهائي في القضية رقم ' . $legalCase->id,
                'en' => 'A final judgment was issued for case number ' . $legalCase->id,
            ],
        };
    }

    private function ensureUserIsJudge(LegalCase $legalCase): void
    {
        $judge = $legalCase->judge;

        if (! $judge || $judge->user_id !== auth()->id()) {
            throw ValidationException::withMessages([
                'user' => __('Only the assigned judge can add the case judgment'),
            ]);
        }
    }

    private function ensureCaseIsNotClosed(LegalCase $legalCase): void
    {
        if ($legalCase->status === LegalCaseStatus::CLOSED->value) {
            throw ValidationException::withMessages([
                'legal_case_id' => __('Cannot add judgment to a closed case'),
            ]);
        }
    }

    private function createCaseNews(LegalCase $legalCase, $judgment): void
    {
        $message = $this->getJudgmentNotificationData($judgment, $legalCase);
        $recipients = $this->getJudgmentRecipients($legalCase);

        $this->legalCaseRepo->createCaseNews(
            $legalCase,
            'case_first_judgment',
            $message,
            auth()->id(),
            $legalCase->defendant?->user_id
        );


        $notificationData = [
            'model_id' => $legalCase->id,
            'title' => [
                'ar' => 'حكم قضائي جديد',
                'en' => 'New Judicial Judgment',
            ],
            'body' => $message,
            'type' => 'case_judgment',
        ];

        $users = User::whereIn('id', $recipients)->get();
        Notification::send($users, new LegalCaseNotification($legalCase, $notificationData));
    }

    private function getJudgmentNotificationData($judgment, $legalCase): array
    {
        return match ($judgment->judgment_type) {
            LegalCaseJudgmentType::CONVICTION->value => [
                'ar' => 'صدر حكم اولي بالإدانة للقضيه رقم ' . $legalCase->id,
                'en' => 'The case was judged as conviction number ' . $legalCase->id,
            ],
            LegalCaseJudgmentType::ACQUITTAL->value => [
                'ar' => 'صدر حكم اولي بالبراءة  للقضيه رقم ' . $legalCase->id,
                'en' => 'The case was judged as acquittal number ' . $legalCase->id,
            ],
            LegalCaseJudgmentType::DISMISSED->value => [
                'ar' => 'صدر حكم اولي بالرفض للقضيه رقم ' . $legalCase->id,
                'en' => 'The case was judged as dismissed number ' . $legalCase->id,
            ],
            default => [
                'ar' => 'صدر حكم اولي في القضية رقم ' . $legalCase->id,
                'en' => 'The case judgment was recorded number ' . $legalCase->id,
            ],
        };
    }

    private function getJudgmentRecipients(LegalCase $legalCase): array
    {
        $recipients = [];

        $recipients[] = auth()->id();

        if ($legalCase->plaintiff) {
            $recipients[] = $legalCase->plaintiff->user_id;
        }

        if ($legalCase->defendant) {
            $recipients[] = $legalCase->defendant->user_id;
        }

        if ($legalCase->plaintiffLawyer) {
            $recipients[] = $legalCase->plaintiffLawyer->user_id;
        }

        if ($legalCase->defendantLawyer) {
            $recipients[] = $legalCase->defendantLawyer->user_id;
        }

        if ($legalCase->consultant) {
            $recipients[] = $legalCase->consultant->user_id;
        }

        if ($legalCase->judge) {
            $recipients[] = $legalCase->judge->user_id;
        }

        return array_unique($recipients);
    }

    public function acceptJudgment(array $data)
    {
        try {
            DB::beginTransaction();

            $legalCase = $this->legalCaseRepo->find($data['legal_case_id']);

            if ($legalCase->status !== LegalCaseStatus::ONGOING->value) {
                throw ValidationException::withMessages([
                    'legal_case_id' => __('Only cases in ongoing status can accept the ruling'),
                ]);
            }
            $this->ensureUserIsDefendantLawyer($legalCase);
            $legalCase->update(['status' => LegalCaseStatus::CLOSED->value, 'winner_id' => $legalCase->plaintiff?->user_id]);
            $judgment = $legalCase->firstInstanceJudgment;
            if ($judgment) {
                $judgment->update(['is_final' => true]);
            }

            $this->createAcceptanceNews($legalCase, $judgment);
            $this->postCaseChat($legalCase, 'تم قبول الحكم وإغلاق قضية');

            // The defendant lawyer accepted the conviction → plaintiff side won.
            $this->points->onJudgmentAccepted($legalCase);

            DB::commit();

            return $legalCase;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    /**
     * Auto-uphold every first-instance verdict that has stood past the appeal
     * window with NO appeal (BUG9). Product rule: 24h after a first-instance
     * ruling with no appeal, the verdict is UPHELD and the case CLOSES. This is
     * the same terminal transition as a defendant lawyer ACCEPTING the ruling
     * (`acceptJudgment`): the plaintiff side wins, the first-instance judgment
     * becomes final, and the win points are paid.
     *
     * Lazy-triggered on read (like the 7-day execution close — see
     * `LegalCaseService`), and also runnable from the scheduled
     * `UpholdExpiredFirstInstanceCases` job. Idempotent: the case leaves
     * `ongoing` and points are keyed by a stable reason, so re-running never
     * double-settles.
     *
     * @return int number of cases upheld
     */
    public function upholdExpiredFirstInstanceCases($groupId = null): int
    {
        $cases = $this->legalCaseRepo->expiredUnappealedFirstInstanceCases($groupId);

        $count = 0;
        foreach ($cases as $legalCase) {
            $this->upholdFirstInstanceVerdict($legalCase);
            $count++;
        }

        return $count;
    }

    /**
     * Settle ONE case by upholding its first-instance verdict — a faithful
     * mirror of `acceptJudgment`'s state transition (status → closed, winner_id
     * = plaintiff, first-instance judgment → final, win points paid), wrapped in
     * its own transaction so a failure on one case can't poison the batch.
     */
    private function upholdFirstInstanceVerdict(LegalCase $legalCase): void
    {
        try {
            DB::beginTransaction();

            // Re-check under the transaction: only settle a case still ongoing
            // (guards against a concurrent accept/appeal between query and here).
            if ($legalCase->status !== LegalCaseStatus::ONGOING->value) {
                DB::rollBack();

                return;
            }

            // Only ongoing cases carrying a NON-final first-instance verdict
            // reach here (acquittal/dismissed already closed as final), so the
            // upheld verdict is a conviction → the plaintiff side wins, exactly
            // as when the defendant lawyer accepts the ruling.
            $legalCase->update([
                'status' => LegalCaseStatus::CLOSED->value,
                'winner_id' => $legalCase->plaintiff?->user_id,
            ]);

            $judgment = $legalCase->firstInstanceJudgment;
            if ($judgment) {
                $judgment->update(['is_final' => true]);
            }

            $this->createAutoUpholdNews($legalCase, $judgment);
            $this->postCaseChat($legalCase, 'تم تأييد الحكم تلقائيًا بعد انتهاء مهلة الاستئناف وإغلاق قضية');

            // Same points as an accepted judgment: the plaintiff side won.
            $this->points->onJudgmentAccepted($legalCase);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Case news + defendant notification for an automatic uphold. Kept separate
     * from `createAcceptanceNews` so the copy is truthful ("upheld after the
     * appeal window", not "accepted by the defendant lawyer") and so it carries
     * no actor (system action, `auth()->id()` may be null on the scheduler).
     */
    private function createAutoUpholdNews(LegalCase $legalCase, $judgment): void
    {
        $message = [
            'ar' => 'تم تأييد الحكم للقضية رقم ' . ($judgment?->id ?? $legalCase->id) . ' تلقائيًا بعد انتهاء مهلة الاستئناف',
            'en' => 'The judgment for case number ' . ($judgment?->id ?? $legalCase->id) . ' was upheld automatically after the appeal window expired',
        ];

        $defendant = $legalCase->defendant;
        if (! $defendant) {
            return;
        }

        $this->legalCaseRepo->createCaseNews(
            $legalCase,
            'case_acceptance_ruling',
            $message,
            null,
            $defendant->user_id
        );

        if ($defendant->user) {
            Notification::send($defendant->user, new LegalCaseNotification($legalCase, [
                'model_id' => $legalCase->id,
                'title' => [
                    'ar' => 'تأييد الحكم',
                    'en' => 'Judgment Upheld',
                ],
                'body' => $message,
                'type' => 'acceptance_ruling',
            ]));
        }
    }

    private function ensureUserIsDefendantLawyer(LegalCase $legalCase): void
    {
        $defendantLawyer = $legalCase->defendantLawyer;

        if (!$defendantLawyer || $defendantLawyer->user_id !== auth()->id()) {
            throw ValidationException::withMessages([
                'user' => __('Only the defendant lawyer can accept the ruling'),
            ]);
        }
    }

    private function createAcceptanceNews(LegalCase $legalCase, $judgment): void
    {
        $message = $this->getAcceptanceRulingMessage($judgment);

        $defendant = $legalCase->defendant;
        if (!$defendant) {
            return;
        }

        $this->legalCaseRepo->createCaseNews(
            $legalCase,
            'case_acceptance_ruling',
            $message,
            auth()->id(),
            $legalCase->defendant?->user_id
        );

        $notificationData = [
            'model_id' => $legalCase->id,
            'title' => [
                'ar' => 'قبول الحكم',
                'en' => 'Ruling Acceptance',
            ],
            'body' => $message,
            'type' => 'acceptance_ruling',
        ];

        if ($defendant->user) {
            Notification::send($defendant->user, new LegalCaseNotification($legalCase, $notificationData));
        }
    }

    private function getAcceptanceRulingMessage($judgment): array
    {
        return [
            'ar' => 'تم قبول حكم الإدانة  للقضية رقم ' . $judgment->id . ' من قبل محامي الدفاع',
            'en' => 'The conviction judgment for case number ' . $judgment->id . ' has been accepted by the defendant lawyer',
        ];
    }
}
