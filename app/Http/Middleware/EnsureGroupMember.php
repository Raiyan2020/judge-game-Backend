<?php

namespace App\Http\Middleware;

use App\Models\Group;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Refuses a group-scoped request from someone who is not an ACCEPTED member of
 * that group.
 *
 * These routes used to carry `auth:sanctum` and nothing else, so any signed-in
 * user could read any group's members (including every user's phone number),
 * its laws, its cases and its statistics just by guessing the id. The message
 * endpoint already gated itself in the service layer; this makes the guarantee
 * uniform and route-level instead of relying on each service remembering.
 *
 * Deliberately NOT applied to `accept` / `reject`: the caller there is a
 * *pending* invitee, so requiring accepted membership would make an invitation
 * impossible to answer.
 */
class EnsureGroupMember
{
    public function handle(Request $request, Closure $next)
    {
        $routeGroup = $request->route('group');
        $groupId = $routeGroup instanceof Group
            ? $routeGroup->id
            : (int) $routeGroup;

        $user = auth('sanctum')->user();

        if (! $user || ! $groupId) {
            $this->deny();
        }

        // The owner is a member by definition, even if the pivot row is ever
        // missing (an owner who left is exactly that case).
        $isOwner = Group::whereKey($groupId)
            ->where('user_id', $user->id)
            ->exists();

        if ($isOwner) {
            return $next($request);
        }

        $isMember = DB::table('group_user')
            ->where('group_id', $groupId)
            ->where('user_id', $user->id)
            ->where('status', 'accepted')
            ->exists();

        if (! $isMember) {
            $this->deny();
        }

        return $next($request);
    }

    /**
     * Same message the chat endpoint already returns, so the app shows one
     * consistent (and already-translated) sentence for every group it cannot
     * see. `lang/ar.json` carries this key.
     */
    private function deny(): never
    {
        throw ValidationException::withMessages([
            'group' => __('You are not a member of this group'),
        ]);
    }
}
