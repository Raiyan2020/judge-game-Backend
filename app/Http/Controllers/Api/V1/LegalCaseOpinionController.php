<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreLegalCaseAppealRequest;
use App\Http\Requests\Api\V1\StoreLegalCaseOpinionRequest;
use App\Http\Resources\Api\V1\LegalCaseResource;
use App\Models\LegalCaseOpinion;
use App\Services\LegalCaseOpinionServices;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LegalCaseOpinionController extends Controller
{
    public function __construct(protected  LegalCaseOpinionServices $legalCaseOpinionService) {}

    public function addOpinion(StoreLegalCaseOpinionRequest $request)
    {
        $legalCase = $this->legalCaseOpinionService->createOpinion($request->validated());
        $legalCase->load($this->relations());
        return \responder::success(new LegalCaseResource($legalCase));
    }

    public function requestAppeal(StoreLegalCaseAppealRequest $request)
    {
        $legalCase = $this->legalCaseOpinionService->requestAppeal($request->validated());
        $legalCase->load($this->relations());
        return \responder::success(new LegalCaseResource($legalCase));
    }

    public function reviewOpinion(Request $request, LegalCaseOpinion $opinion)
    {
        $request->validate([
            'is_correct' => ['required', 'boolean'],
        ]);

        // Only the presiding judge (the case's group owner) may rule an opinion
        // correct/incorrect — this endpoint was previously open to any user.
        $group = $opinion->legalCase?->group;
        if (! $group || $group->user_id !== auth()->id()) {
            throw ValidationException::withMessages([
                __('You are not authorized to perform this action'),
            ]);
        }

        $this->legalCaseOpinionService->reviewOpinion(
            $opinion,
            $request->is_correct
        );

        return \responder::success(__('opinion reviewed successfully'));
    }



    private function relations(): array
    {
        return [
            'group',
            'groupLaws',
            'plaintiff.user',
            'defendant.user',
            'defendantLawyer.user',
            'plaintiffLawyer.user',
            'judge.user',
            'witnesses.user',
            'opinions.user',
            'opinions.media',
            'media',
            'judgments',
            'finalJudgment',
            'firstInstanceJudgment',
            // Keep the hearing loaded on the post-opinion echo so
            // `has_scheduled_hearing` stays true and the app doesn't re-enable
            // "تحديد جلسة" after an opinion (BUG4).
            'hearings',
        ];
    }
}
