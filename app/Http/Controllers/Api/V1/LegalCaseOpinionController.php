<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CaseRole;
use App\Enums\LegalCaseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreLegalCaseAppealRequest;
use App\Http\Requests\Api\V1\StoreLegalCaseOpinionRequest;
use App\Http\Resources\Api\V1\LegalCaseResource;
use App\Models\LegalCase;
use App\Models\LegalCaseOpinion;
use App\Services\LegalCaseOpinionServices;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LegalCaseOpinionController extends Controller
{
    public function __construct(protected  LegalCaseOpinionServices $legalCaseOpinionService) {}

    public function addOpinion(StoreLegalCaseOpinionRequest $request)
    {
        // `add-opinion` is deliberately NOT in the `checkActiveSubscription`
        // route group, because the plaintiff lawyer's OFFICIATING opinion (the
        // only act that files a `pending_lawyer` case) must go through even for a
        // lapsed lawyer. Every OTHER opinion stays subscription-gated here, with
        // the same 402 shape the middleware returns.
        if ($subscriptionError = $this->subscriptionGate($request->validated())) {
            return $subscriptionError;
        }

        $legalCase = $this->legalCaseOpinionService->createOpinion($request->validated());
        $legalCase->load($this->relations());
        return \responder::success(new LegalCaseResource($legalCase));
    }

    /**
     * Enforce the active-subscription requirement for every add-opinion EXCEPT
     * the official filing (case is `pending_lawyer` AND the author is its
     * assigned plaintiff lawyer). Returns the 402 response to short-circuit with,
     * or null when the request may proceed. Mirrors `CheckActiveSubscription`.
     */
    private function subscriptionGate(array $data)
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        $legalCase = LegalCase::find($data['legal_case_id'] ?? null);

        // A null / non-pending case is not an official filing, so the gate
        // applies. (A subscribed user then falls through to the service's
        // localized "Legal case not found"; a lapsed one gets 402 first.)
        $isOfficialFiling = $legalCase
            && $legalCase->status === LegalCaseStatus::PENDING_LAWYER->value
            && $legalCase->participantRoleFor((int) $user->id) === CaseRole::PLAINTIFF_LAWYER->value;

        if ($isOfficialFiling) {
            return null;
        }

        // Same aggregate-subquery relation the middleware uses — check via
        // `first()`, never `exists()`.
        if ($user->activeSubscription()->with('package')->first()) {
            return null;
        }

        return response()->json([
            'status' => false,
            'msg' => __('You do not have an active subscription. Please subscribe to a package first'),
            'error_code' => 'no_active_subscription',
        ], 402);
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
