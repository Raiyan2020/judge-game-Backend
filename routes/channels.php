<?php

use Illuminate\Support\Facades\Broadcast;


// Only members of a chat may subscribe to its private channel. Leaving this
// open (return true) let ANY authenticated user listen to ANY chat's messages.
Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    return \App\Models\Chat::where('id', $chatId)
        ->whereHas('users', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->exists();
});

// A case's private realtime channel (LegalCaseUpdated). Authorized to a member
// of the case's group — the SAME bar LegalCaseController::ensureGroupMember /
// the REST `show` enforce: the group owner OR an accepted member. A missing
// case (or non-member) is denied. Note the `pending_lawyer` plaintiff-side-only
// restriction is intentionally NOT re-applied here — the gate this event
// signals only ever exists on already-filed (new/in_progress) cases, so a held
// case never broadcasts.
Broadcast::channel('legal-case.{legalCaseId}', function ($user, $legalCaseId) {
    $case = \App\Models\LegalCase::find($legalCaseId);
    if (! $case) {
        return false;
    }

    return \App\Models\Group::whereKey($case->group_id)
        ->where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhereHas('users', function ($q) use ($user) {
                    $q->where('users.id', $user->id)
                        ->where('group_user.status', 'accepted');
                });
        })
        ->exists();
});