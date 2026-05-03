<?php

namespace App\Services;

use App\Enums\CaseRole;
use App\Enums\GroupRole;
use App\Models\User;
use App\Notifications\LegalCaseNotification;
use App\Repositories\GroupRepository;
use App\Repositories\LegalCaseRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class LegalCaseService
{
    public function __construct(protected LegalCaseRepository $repo, protected GroupRepository $groupRepo) {}

    public function index($filters, $groupId)
     {
         $filters['group_id'] = $groupId;
         return $this->repo->index($filters);
     } 
   

    public function create($request)
    {
        try {
            DB::beginTransaction();
            $userId = auth()->id();
            $participants = $request['participants'];
            $request['user_id'] = $userId;
            $group = $this->groupRepo->find($request['group_id']);
            if (!$group) {
                throw ValidationException::withMessages([__('Group not found')]);
            }
            $this->validateUserCanCreateCase($group);
            $legalCase = $this->repo->create($request);
            $attachments = $this->collectAttachments($request);
            $this->uploadAttachments($legalCase, $attachments);
            $participants[] = [
                'user_id' => $userId,
                'role' => 'plaintiff',
            ];
            $participants[] = [
                'user_id' => $group->owner_id,
                'role' => 'judge',
            ];

            $legalCase->participants()->createMany($participants);
            $legalCase->groupLaws()->attach($request['group_law_ids']);
            $this->createCaseNews($legalCase, $userId, $participants);
            DB::commit();

            DB::afterCommit(function () use ($legalCase) {
                $this->sendNotificationToPlaintiffLawyer($legalCase);
            });

            return $legalCase;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(__('Failed to create legal case. Please try again later.'));
        }
    }

    private function createCaseNews($legalCase, $userId, $participants)
    {
        $defendant = null;
        foreach ($participants as $participant) {
            if ($participant['role'] == 'defendant') {
                $defendant = $participant['user_id'];
                break;
            }
        }
        if ($defendant) {
            $this->repo->createCaseNews($legalCase, 'case_created', [
                'ar' => 'تم إنشاء القضية',
                'en' => 'Legal case created',
            ], $userId, $defendant);
        }
    }

    private function validateUserCanCreateCase($group): void
    {
        $userId = auth()->id();

        $membersQuery = $group->users();

        $isMember = (clone $membersQuery)
            ->where('user_id', $userId)
            ->exists();

        if (!$isMember) {
            throw ValidationException::withMessages([
                __('You must be a member of the group to create a legal case')
            ]);
        }

        $isJudge = (clone $membersQuery)
            ->where('user_id', $userId)
            ->wherePivot('role', GroupRole::JUDGE->value)
            ->exists();

        if ($isJudge) {
            throw ValidationException::withMessages([
                __('Group judge cannot create legal cases')
            ]);
        }

        $lawyersCount = (clone $membersQuery)
            ->wherePivot('role', GroupRole::LAWYER->value)
            ->count();

        $citizensCount = (clone $membersQuery)
            ->wherePivot('role', GroupRole::CITIZEN->value)
            ->count();

        if ($lawyersCount < 2 && $citizensCount < 2) {
            throw ValidationException::withMessages([
                __('At least 2 lawyers or 2 citizens are required in the group to create a legal case')
            ]);
        }
    }

    private function collectAttachments($request)
    {
        return [
            'images' => $request['images'] ?? [],
            'videos' => $request['videos'] ?? [],
            'audios' => $request['audios'] ?? [],
        ];
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

    public function update($model, $request)
    {
        return $this->repo->update($model, $request);
    }

    public function delete($model)
    {
        return $this->repo->delete($model);
    }

    public function activation($model)
    {
        return $this->repo->activation($model);
    }

    public function assignDefendantLawyer($request)
    {
        $case = $this->repo->find($request['legal_case_id']);

        $existingLawyer = $case->participants()
            ->where('role', CaseRole::DEFENDANT_LAWYER->value)
            ->exists();

        if ($existingLawyer) {
            throw ValidationException::withMessages([__('A defendant lawyer is already assigned to this case')]);
        }

        $case->participants()->create([
            'user_id' => $request['lawyer_id'],
            'role' => CaseRole::DEFENDANT_LAWYER->value,
        ]);

        return $case;
    }

    private function sendNotificationToPlaintiffLawyer($legalCase)
    {
        $plaintiffLawyer = $legalCase->plaintiffLawyer;
        if ($plaintiffLawyer) {
            $data = [
                'model_id' => $legalCase->id,
                'title' => [
                    'ar' => 'قضية قانونية جديدة',
                    'en' => 'New Legal Case',
                ],
                'body' => [
                    'ar' => 'تم تعيينك كمحامي للمدعي في القضية رقم ' . $legalCase->id,
                    'en' => 'You have been assigned as the plaintiff lawyer in case number ' . $legalCase->id,
                ],
                'type' => 'new_legal_case',
            ];
            Notification::send($plaintiffLawyer->user, new LegalCaseNotification($legalCase, $data));
        }
    }

    public function getCasesStatus()
    {
        return $this->repo->getCasesStatus();
    }
}
