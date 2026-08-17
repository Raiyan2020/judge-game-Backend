<?php

namespace App\Services;

use App\Enums\CaseRole;
use App\Enums\LegalCaseStatus;
use App\Models\LegalCaseOpinion;
use App\Notifications\LegalCaseNotification;
use App\Repositories\LegalCaseRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;


class LegalCaseOpinionServices
{
    public function __construct(
        protected LegalCaseRepository $repo,
        protected PointsService $points,
        protected GroupEventService $events,
    ) {}


    public function createOpinion($request)
    {
        try {
            DB::beginTransaction();
            $legalCase = $this->repo->find($request['legal_case_id']);

            if (!$legalCase) {
                throw ValidationException::withMessages([
                    'legal_case_id' => __('Legal case not found')
                ]);
            }

            $stage = $this->resolveStage($legalCase);

            $role = $this->resolveRole($legalCase);

            // One opinion per user per stage — a lawyer / consultant cannot file
            // twice for the same case at the same stage. There was no guard, so
            // the same opinion could be submitted repeatedly. (A later stage —
            // e.g. an appeal — is a different `stage`, so it stays allowed.)
            $alreadySubmitted = $legalCase->opinions()
                ->where('user_id', auth()->id())
                ->where('stage', $stage)
                ->exists();

            if ($alreadySubmitted) {
                throw ValidationException::withMessages([
                    __('You have already submitted your opinion for this case')
                ]);
            }

            $opinionData = [
                'user_id' => auth()->id(),
                'opinion' => $request['opinion'] ?? null,
                'final_requests' => $request['final_requests'] ?? null,
                'role' => $role,
                'legal_arguments' => $request['legal_arguments'] ?? null,
                'stage' => $stage,
            ];

            $legalCaseOpinion = $legalCase->opinions()->create($opinionData);

            $attachments = $this->collectAttachments($request);
            $this->uploadAttachments($legalCaseOpinion, $attachments);

            // Register BEFORE committing so the notification fires after the
            // real commit (matching requestAppeal, which already orders it
            // correctly) — otherwise it runs synchronously and a throw rolls
            // back an already-committed opinion.
            DB::afterCommit(function () use ($legalCase, $legalCaseOpinion) {
                $this->notifyDefendantOnNewOpinion($legalCase, $legalCaseOpinion);
            });

            DB::commit();

            return $legalCase;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }



    private function notifyDefendantOnNewOpinion($legalCase, $legalCaseOpinion)
    {
        if ($legalCase->status !== LegalCaseStatus::NEW->value) {
            return;
        }

        if ($legalCaseOpinion->role !== CaseRole::PLAINTIFF_LAWYER->value) {
            return;
        }

        $defendant = $legalCase->defendant;
        if (! $defendant || ! $defendant->user) {
            return;
        }

        $data = [
            'model_id' => $legalCase->id,
            'title' => [
                'ar' => 'تم رفع قضيه ضدك',
                'en' => 'A case has been filed against you',
            ],
            'body' => [
                'ar' => 'تم رفع قضيه ضدك رقم ' . $legalCase->id,
                'en' => 'A case has been filed against you with number ' . $legalCase->id,
            ],
            'type' => 'new_legal_case',
        ];

        Notification::send($defendant->user, new LegalCaseNotification($legalCase, $data));
    }

    private function collectAttachments($request)
    {
        return [
            'images' => $request['images'] ?? [],
            'videos' => $request['videos'] ?? [],
            'audios' => $request['audios'] ?? [],
        ];
    }

    private function resolveStage($legalCase): string
    {
        return $legalCase->status === LegalCaseStatus::NEW->value
            ? LegalCaseStatus::NEW->value
            : LegalCaseStatus::APPEAL->value;
    }

    public function requestAppeal(array $request)
    {
        try {
            DB::beginTransaction();

            $legalCase = $this->repo->find($request['legal_case_id']);

            if (!$legalCase) {
                throw ValidationException::withMessages([
                    'legal_case_id' => __('Legal case not found'),
                ]);
            }

            if ($legalCase->status === LegalCaseStatus::CLOSED->value) {
                throw ValidationException::withMessages([
                    'legal_case_id' => __('Cannot request appeal for a closed case'),
                ]);
            }

            if ($legalCase->status !== LegalCaseStatus::ONGOING->value) {
                throw ValidationException::withMessages([
                    'legal_case_id' => __('Appeal can only be requested for ongoing cases'),
                ]);
            }

            if ($legalCase->status === LegalCaseStatus::APPEAL->value) {
                throw ValidationException::withMessages([
                    'legal_case_id' => __('This case is already under appeal'),
                ]);
            }

            $role = $this->getAppealRequesterRole($legalCase);

            $legalCase->update(['status' => LegalCaseStatus::APPEAL->value]);

            $opinionData = [
                'user_id' => auth()->id(),
                'opinion' => $request['opinion'] ?? null,
                'role' => $role,
                'stage' => LegalCaseStatus::APPEAL->value,
            ];

            $legalCaseOpinion = $legalCase->opinions()->create($opinionData);



            $this->createAppealNews($legalCase, $role);

            DB::afterCommit(function () use ($legalCase) {
                $this->notifyJudgeOnAppealRequest($legalCase);
            });

            DB::commit();

            return $legalCase;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function getAppealRequesterRole($legalCase): string
    {
        $user = auth()->user();

        $participant = $legalCase->participants()
            ->where('user_id', $user->id)
            ->first();

        if ($participant) {
            $allowedRoles = [
                CaseRole::PLAINTIFF_LAWYER->value,
                CaseRole::DEFENDANT_LAWYER->value,
                CaseRole::CONSULTANT->value,
            ];

            if (in_array($participant->role, $allowedRoles)) {
                return $participant->role;
            }

            throw ValidationException::withMessages([
                'user' => __('Only plaintiff lawyer, defendant lawyer, or consultant may request appeal'),
            ]);
        }

        if ($legalCase->group?->users()
            ->where('user_id', $user->id)
            ->wherePivot('role', CaseRole::CONSULTANT->value)
            ->exists()
        ) {
            return CaseRole::CONSULTANT->value;
        }

        throw ValidationException::withMessages([
            'user' => __('Only plaintiff lawyer, defendant lawyer, or consultant may request appeal'),
        ]);
    }

    private function createAppealNews($legalCase, string $role): void
    {
        $this->repo->createCaseNews(
            $legalCase,
            'case_appeal_request',
            [
                'ar' => 'تم طلب استئناف للقضية رقم ' . $legalCase->id,
                'en' => 'An appeal request was made for the case number ' . $legalCase->id,
            ],
            auth()->id(),
            $legalCase->judge?->user_id
        );

        // Mirror into the group chat (bell already sent to the judge).
        if ($legalCase->group) {
            $this->events->postChat(
                $legalCase->group,
                'تم طلب استئناف في قضية: ' . $legalCase->title,
            );
        }
    }

    private function notifyJudgeOnAppealRequest($legalCase): void
    {
        $judge = $legalCase->judge;
        if (! $judge || ! $judge->user) {
            return;
        }

        $data = [
            'model_id' => $legalCase->id,
            'title' => [
                'ar' => 'طلب استئناف',
                'en' => 'Appeal Request',
            ],
            'body' => [
                'ar' => 'تم طلب الاستئناف للقضية رقم ' . $legalCase->id,
                'en' => 'An appeal has been requested for case number ' . $legalCase->id,
            ],
            'type' => 'appeal_request',
        ];

        Notification::send($judge->user, new LegalCaseNotification($legalCase, $data));
    }

    private function resolveRole($legalCase): string
    {
        $user = auth()->user();

        $participant = $legalCase->participants()
            ->where('user_id', $user->id)
            ->first();

        if ($participant) {
            return $participant->role;
        }

        return $this->assignConsultantIfEligible($legalCase, $user);
    }

    private function assignConsultantIfEligible($legalCase, $user): string
    {
        $isConsultantInGroup = $legalCase->group->users()
            ->where('user_id', $user->id)
            ->wherePivot('role', CaseRole::CONSULTANT->value)
            ->exists();

        $hasConsultant = $legalCase->participants()
            ->where('role', CaseRole::CONSULTANT->value)
            ->exists();

        if (!$hasConsultant) {

            if (!$isConsultantInGroup) {
                throw ValidationException::withMessages([
                    'user' => __('Only a consultant can initiate the case opinion'),
                ]);
            }

            $legalCase->participants()->create([
                'user_id' => $user->id,
                'role' => CaseRole::CONSULTANT->value,
            ]);

            // Consultant is now a party to the case → award participation points.
            $this->points->onConsultantParticipation($legalCase, $user->id);

            return CaseRole::CONSULTANT->value;
        }

        throw ValidationException::withMessages([
            'user' => __('You are not assigned to this case'),
        ]);
    }

    private function uploadAttachments($model, $attachments)
    {
        foreach ($attachments as $collection => $files) {
            foreach ($files as $file) {
                $model
                    ->addMedia($file)
                    ->toMediaCollection($collection);
            }
        }
    }

    public function reviewOpinion(LegalCaseOpinion $opinion, bool $isCorrect)
    {

        $opinion->update([
            'is_correct' => $isCorrect,
        ]);

        return $opinion;
    }
}
