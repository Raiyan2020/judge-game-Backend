<?php

namespace App\Services;

use App\Models\Group;
use App\Models\GroupLaw;
use App\Models\GroupLawRequest;
use Illuminate\Validation\ValidationException;

class GroupLawRequestService
{
    public function listPendingForOwner($user)
    {
        return GroupLawRequest::whereHas('group', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->where('status', 'pending')->get();
    }

    public function approve(GroupLawRequest $request)
    {
        $this->ensureOwner($request->group);

        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([__('This request is not pending')]);
        }

        if ($request->action === 'create') {
            GroupLaw::create([
                'group_id' => $request->group_id,
                'description' => $request->description,
                'reason' => $request->reason,
            ]);
        } elseif ($request->action === 'update') {
            if (!$request->groupLaw) {
                throw ValidationException::withMessages([__('Related law not found')]);
            }
            $request->groupLaw->update([
                'description' => $request->description,
                'reason' => $request->reason,
            ]);
        } elseif ($request->action === 'delete') {
            if (!$request->groupLaw) {
                throw ValidationException::withMessages([__('Related law not found')]);
            }
            $request->groupLaw->delete();
        }

        $request->update(['status' => 'approved']);

        return $request;
    }

    public function reject(GroupLawRequest $request)
    {
        $this->ensureOwner($request->group);

        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([__('This request is not pending')]);
        }

        $request->update(['status' => 'rejected']);

        return $request;
    }

    protected function ensureOwner(Group $group)
    {
        $user = auth('sanctum')->user();
        if (!$user || $group->user_id !== $user->id) {
            throw ValidationException::withMessages([__('You are not the creator of this group')]);
        }
    }
}
