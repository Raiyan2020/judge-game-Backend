<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GroupLawStoreRequest;
use App\Http\Requests\Api\V1\GroupLawUpdateRequest;
use App\Models\Group;
use App\Models\GroupLaw;
use App\Services\GroupLawService;

class GroupLawController extends Controller
{
    public function __construct(protected GroupLawService $groupLawService) {}

    public function index(Group $group)
    {
        $laws = $this->groupLawService->index($group);
        return \responder::success($laws);
    }

    public function store( GroupLawStoreRequest $request)
    {
        $law = $this->groupLawService->store($request->validated());
        return \responder::success($law);
    }

    public function update(GroupLaw $groupLaw, GroupLawUpdateRequest $request)
    {
        $law = $this->groupLawService->update($groupLaw, $request->validated());
        return \responder::success($law);
    }

    public function destroy(GroupLaw $groupLaw)
    {
        $this->groupLawService->destroy($groupLaw);
        return \responder::success(__('Law deleted successfully'));
    }
}
