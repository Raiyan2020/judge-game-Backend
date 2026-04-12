<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GroupLawDestroyRequest;
use App\Http\Requests\Api\V1\GroupLawStoreRequest;
use App\Http\Requests\Api\V1\GroupLawUpdateRequest;
use App\Http\Resources\Api\V1\GroupLawResource;
use App\Models\Group;
use App\Models\GroupLaw;
use App\Models\GroupLawRequest;
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

        if ($result instanceof GroupLawRequest) {
            return \responder::success(__('Law creation request submitted and pending approval'));
        }

        return \responder::success(new GroupLawResource($result));
    }

    public function update(GroupLaw $groupLaw, GroupLawUpdateRequest $request)
    {
        $result = $this->groupLawService->update($groupLaw, $request->validated());

        if ($result instanceof GroupLawRequest) {
            return \responder::success(__('Law update request submitted and pending approval'));
        }

        return \responder::success(new GroupLawResource($result));
    }

    public function destroy(GroupLawDestroyRequest $request, GroupLaw $groupLaw)
    {
        $result = $this->groupLawService->destroy($groupLaw, $request->validated());

        if ($result instanceof GroupLawRequest) {
            return \responder::success(__('Law deletion request submitted and pending approval'));
        }

        return \responder::success(__('Law deleted successfully'));
    }
}
