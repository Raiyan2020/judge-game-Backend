<?php

namespace App\Services;

use App\Enums\CaseRole;
use App\Models\LegalCaseParty;
use App\Repositories\LegalCaseRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LegalCaseService
{


    public function __construct(protected LegalCaseRepository $repo) {}

    public function create($request)
    {
        try {
            DB::beginTransaction();
            $userId = auth()->id();
            $participants = $request['participants'];
            $request['user_id'] = $userId;
            $legalCase = $this->repo->create($request);
            $attachments = $this->collectAttachments($request);
            $this->uploadAttachments($legalCase, $attachments);
            $participants[] = [
                'user_id' => $userId,
                'role' => 'plaintiff',
            ];
            $legalCase->participants()->createMany($participants);
            $legalCase->groupLaws()->attach($request['group_law_ids']);
            DB::commit();

            return $legalCase;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(__('Failed to create legal case. Please try again later.'));
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
}
