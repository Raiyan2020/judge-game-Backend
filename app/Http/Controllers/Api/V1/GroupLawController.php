<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GroupLawDestroyRequest;
use App\Http\Requests\Api\V1\GroupLawStoreRequest;
use App\Http\Requests\Api\V1\GroupLawUpdateRequest;
use App\Http\Resources\Api\V1\GroupLawResource;
use App\Models\GroupLaw;
use App\Services\GroupLawService;

class GroupLawController extends Controller
{
    public function __construct(protected GroupLawService $groupLawService) {}

    public function index($groupId)
    {
        $laws = $this->groupLawService->index($groupId);
        return \responder::success(GroupLawResource::collection($laws));
    }

    public function store(GroupLawStoreRequest $request)
    {
        $result = $this->groupLawService->store($request->validated());
        $isCreator = $this->groupLawService->isGroupOwner($request->validated()['group_id']);
        return \responder::success($isCreator ? __('Law created successfully') : __('request submitted and pending approval'));
    }

    public function update(GroupLaw $groupLaw, GroupLawUpdateRequest $request)
    {
        $result = $this->groupLawService->update($groupLaw, $request->validated());
        $isCreator = $this->groupLawService->isGroupOwner($groupLaw->group_id);
        return \responder::success($isCreator ? __('Law updated successfully') : __('request submitted and pending approval'));
    }

    public function destroy(GroupLawDestroyRequest $request, GroupLaw $groupLaw)
    {
        $result = $this->groupLawService->destroy($groupLaw, $request->validated());
        $isCreator = $this->groupLawService->isGroupOwner($groupLaw->group_id);
        return \responder::success($isCreator ? __('Law deleted successfully') : __('request submitted and pending approval'));
    }
}
