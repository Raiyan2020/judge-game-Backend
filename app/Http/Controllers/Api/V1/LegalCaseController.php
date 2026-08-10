<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AssignDefendantLawyerRequest;
use App\Http\Requests\Api\V1\LegalCaseRequest;
use App\Http\Resources\Api\V1\BaseCollection;
use App\Http\Resources\Api\V1\LegalCaseListResource;
use App\Http\Resources\Api\V1\LegalCaseResource;
use App\Models\LegalCase;
use App\Services\LegalCaseService;
use Illuminate\Http\Request;

class LegalCaseController extends Controller
{
    public function __construct(protected  LegalCaseService $legalCaseService) {}

    public function index(Request $request, $group)
    {
        $legalCases = $this->legalCaseService->index($request->all(), $group);
        return \responder::success((new BaseCollection($legalCases, LegalCaseListResource::class))->toArray(request()));

    }

    public function getCaseStatus(Request $request)
    {
        // `group_id` is REQUIRED: the repository treats a null one as "no
        // filter", so omitting it returned case counters for the entire
        // database. And it must be a group the caller belongs to, or these
        // counters report on groups they cannot otherwise see.
        $request->validate(['group_id' => 'required|exists:groups,id']);
        $this->ensureGroupMember((int) $request->group_id);

        $legalCases = $this->legalCaseService->getCasesStatus($request->group_id);
        return \responder::success($legalCases);
    }

    /**
     * Refuses a caller who is not an accepted member (or the owner) of [$groupId].
     *
     * The `groupMember` middleware cannot be used on these routes: it reads the
     * `{group}` route parameter, which `show` (`{legalCase}`) and
     * `getCaseStatus` (query string) do not have — it would deny everyone.
     */
    private function ensureGroupMember(int $groupId): void
    {
        $userId = auth('sanctum')->id();

        $allowed = \App\Models\Group::whereKey($groupId)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('users', function ($q) use ($userId) {
                        $q->where('users.id', $userId)
                            ->where('group_user.status', 'accepted');
                    });
            })
            ->exists();

        if (! $allowed) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'group' => __('You are not a member of this group'),
            ]);
        }
    }

    public function store(LegalCaseRequest $request)
    {
        $legalCase = $this->legalCaseService->create($request->validated());
        $legalCase->load($this->relations());

        return \responder::success(new LegalCaseResource($legalCase));
    }

    public function show(LegalCase $legalCase)
    {
        // Authenticated-IDOR guard: this route takes a case id, not a group id,
        // so it carried no membership check at all — any signed-in user could
        // read ANY case by number, including every party, the full opinions
        // with their `legal_arguments`, and the judgment texts. Membership in
        // the case's own group is the minimum bar.
        $this->ensureGroupMember((int) $legalCase->group_id);

        // A case that finished its enforcement window should read as `closed`.
        $this->legalCaseService->settleIfExecutionExpired($legalCase);
        $legalCase->load($this->relations());
        return \responder::success(new LegalCaseResource($legalCase));
    }

    public function assignDefendantLawyer(AssignDefendantLawyerRequest $request)
    {
        $legalCase = $this->legalCaseService->assignDefendantLawyer($request->validated());
        $legalCase->load($this->relations());
        return \responder::success(new LegalCaseResource($legalCase));
    }

    public function close(LegalCase $legalCase)
    {
        $legalCase = $this->legalCaseService->closeCase($legalCase);
        $legalCase->load($this->relations());
        return \responder::success(new LegalCaseResource($legalCase));
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
        ];
    }
}
