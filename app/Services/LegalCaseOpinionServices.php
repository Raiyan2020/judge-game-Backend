<?php

namespace App\Services;

use App\Enums\CaseRole;
use App\Enums\LegalCaseStatus;
use App\Notifications\LegalCaseNotification;
use App\Repositories\LegalCaseRepository;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;


class LegalCaseOpinionServices
{
    public function __construct(protected LegalCaseRepository $repo) {}


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
            DB::commit();
            DB::afterCommit(function () use ($legalCase, $legalCaseOpinion) {
                $this->notifyDefendantOnNewOpinion($legalCase, $legalCaseOpinion);
            });


            return $legalCase;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(__('Failed to add opinion. Please try again later.'));
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
}
