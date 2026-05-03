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
        protected LegalCaseRepository $legalCaseRepo
    ) {}

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

            if (in_array($judgment->judgment_type, [LegalCaseJudgmentType::DISMISSED->value, LegalCaseJudgmentType::ACQUITTAL->value])) {
                $legalCase->update(['status' => LegalCaseStatus::CLOSED->value]);
            } else {
                $legalCase->update(['status' => LegalCaseStatus::ONGOING->value]);
            }

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

            $legalCase->update(['status' => LegalCaseStatus::EXECUTION->value]);

            $this->createFinalJudgmentNews($legalCase, $judgment);

            DB::commit();

            return $legalCase;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function createFinalJudgmentNews(LegalCase $legalCase, $judgment): void
    {
        $message = $this->getFinalJudgmentNotificationData($judgment , $legalCase);

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

    private function getFinalJudgmentNotificationData($judgment , $legalCase): array
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

    private function getJudgmentNotificationData($judgment , $legalCase): array
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
            $legalCase->update(['status' => LegalCaseStatus::CLOSED->value]);
            $judgment = $legalCase->firstInstanceJudgment;
            if ($judgment) {
                $judgment->update(['is_final' => true]);
            }

            $this->createAcceptanceNews($legalCase, $judgment);
            DB::commit();

            return $legalCase;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
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
