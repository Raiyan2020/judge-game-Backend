<?php

namespace App\Services;

use App\Enums\CaseRole;
use App\Enums\GroupRole;
use App\Enums\LegalCaseStatus;
use App\Events\LegalCaseUpdated;
use App\Models\User;
use App\Notifications\LegalCaseNotification;
use App\Repositories\GroupRepository;
use App\Repositories\LegalCaseRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class LegalCaseService
{
    public function __construct(protected LegalCaseRepository $repo, protected GroupRepository $groupRepo, protected GroupPermissionService $groupPermissionService, protected GroupEventService $events, protected PointsService $points, protected LegalCaseJudgmentService $judgmentService) {}

    public function index($filters, $groupId)
    {
        // Lazily settle any full-distance cases before listing, so `execution`
        // cases that finished their enforcement window show as `closed`, and
        // un-appealed first-instance verdicts past the 24h window read as upheld
        // and closed (BUG9). The execution close also fires a `case_closed`
        // group event per closed case (news + bell to parties + chat).
        $this->closeExpiredExecutionCasesWithEvents($groupId);
        $this->judgmentService->upholdExpiredFirstInstanceCases($groupId);
        $filters['group_id'] = $groupId;
        // Pass the caller's id so the repository can scope `pending_lawyer` cases
        // to the assigned plaintiff lawyer only (they are invisible to everyone
        // else until officially filed).
        $filters['current_user_id'] = auth()->id();
        return $this->repo->index($filters);
    }

    /**
     * Close a single case if it has finished its execution window. Called on
     * detail read so a case opened after the window shows as `closed`.
     */
    public function settleIfExecutionExpired($legalCase): void
    {
        // Auto-uphold an un-appealed first-instance verdict past its 24h window
        // (BUG9), scoped to this case's group, so an `ongoing` case opened after
        // the window reads as upheld and closed.
        $this->judgmentService->upholdExpiredFirstInstanceCases($legalCase->group_id);

        // A case that finished its execution window should read as `closed` —
        // and each such closure fires a `case_closed` group event.
        if ($legalCase->status === \App\Enums\LegalCaseStatus::EXECUTION->value) {
            $this->closeExpiredExecutionCasesWithEvents($legalCase->group_id);
        }

        $legalCase->refresh();
    }

    /**
     * Close every execution case past its 7-day enforcement window AND announce
     * each closure with a `case_closed` group event (news group-wide + bell to
     * the case parties + chat). Enumerates the eligible set FIRST because the
     * repo's bulk UPDATE would close siblings silently. Fail-soft per case.
     *
     * NOTE: `getCasesStatus()` (pure counters) and the scheduled
     * `CloseExpiredExecutionCases` job still bulk-close without events — the
     * user-facing read paths (list + detail) and the manual close carry the
     * announcement, which is where it matters.
     */
    private function closeExpiredExecutionCasesWithEvents($groupId = null): void
    {
        $cases = $this->repo->expiredExecutionCases($groupId);
        foreach ($cases as $case) {
            $case->update(['status' => LegalCaseStatus::CLOSED->value]);
            $this->fireCaseClosedEvent($case);
        }
    }

    /**
     * Announce a case closure on all three channels: the news row stays
     * group-wide, the bell is scoped to the case parties (not the whole group).
     * Fail-soft — notifyGroupEvent is itself per-channel fail-soft, and a null
     * group short-circuits.
     */
    private function fireCaseClosedEvent($legalCase): void
    {
        $group = $legalCase->group;
        if (! $group) {
            return;
        }

        $parties = User::whereIn(
            'id',
            $legalCase->participants()->pluck('user_id')->unique()->filter()
        )->get();

        $this->events->notifyGroupEvent(
            $group,
            'case_closed',
            title: ['ar' => 'إغلاق القضية', 'en' => 'Case closed'],
            body: [
                'ar' => 'تم إغلاق القضية: ' . $legalCase->title,
                'en' => 'The case has been closed: ' . $legalCase->title,
            ],
            actor: null,
            caseId: $legalCase->id,
            // Parties-only bell; an empty collection means no bell, while the
            // news row is still written group-wide (do NOT fall back to null,
            // which would fan the bell to the entire group).
            notifiables: $parties,
        );
    }

    /**
     * Manually close a case in execution — the "إغلاق الحكم نهائيًا" action.
     * Previously there was no endpoint at all: the app button only opened a
     * "Coming Soon" placeholder, and closure happened solely on the lazy 7-day
     * timer. This lets the judge (or a party) close it immediately once the
     * final judgment is in.
     */
    public function closeCase($legalCase)
    {
        $userId = auth()->id();

        // Only the presiding judge (group owner) or a party to the case.
        $isJudge = $legalCase->group && (int) $legalCase->group->user_id === (int) $userId;
        $isParticipant = $legalCase->participants()->where('user_id', $userId)->exists();
        if (! $isJudge && ! $isParticipant) {
            throw ValidationException::withMessages([__('You are not authorized to perform this action')]);
        }

        // Closable only in execution AND once a final judgment exists — there is
        // nothing to close before enforcement begins.
        if ($legalCase->status !== LegalCaseStatus::EXECUTION->value) {
            throw ValidationException::withMessages([__('The case cannot be closed at this stage')]);
        }
        if (! $legalCase->finalJudgment()->exists()) {
            throw ValidationException::withMessages([__('The case cannot be closed before a final judgment is issued')]);
        }

        $legalCase->update(['status' => LegalCaseStatus::CLOSED->value]);
        $legalCase->refresh();

        // Announce the closure (news group-wide + bell to the parties + chat).
        $this->fireCaseClosedEvent($legalCase);

        return $legalCase;
    }

    /**
     * The presiding judge asks a case participant (defence lawyer or consultant)
     * to file their opinion, and records the case as awaiting that party's reply
     * so every further judge action is gated until they answer.
     *
     * Concurrency-safe: the mutating portion runs inside a transaction and
     * re-fetches the case with `findForUpdate` (lockForUpdate) FIRST, so it
     * serialises on the SAME row lock LegalCaseOpinionServices::createOpinion
     * holds. This closes the race where the judge reads `alreadyOpined = false`
     * while the awaited lawyer concurrently commits their opinion — which would
     * otherwise set an `awaiting_opinion` gate that can never clear (a retry is
     * 422'd by the one-per-stage rule, so the case wedges permanently).
     *
     * Judge-only. Status-gated to `new` / `in_progress` (mirrors
     * scheduleHearing). Under the lock it re-checks `alreadyOpined`, promotes
     * `new → in_progress`, and sets the gate atomically; the bell + news fire
     * AFTER commit so a send failure can neither roll back the gate nor leave the
     * case promoted-but-ungated.
     *
     * @return string a localized success message
     */
    public function requestOpinion($legalCase, array $data): string
    {
        $userId = auth()->id();

        // JUDGE-ONLY. The presiding judge is the group owner, attached as the
        // `judge` participant. Unlike closeCase/scheduleHearing this is NOT open
        // to any party — a lawyer must never be able to nudge himself. Cast both
        // sides of every comparison (the older ensureUserIsJudge compares
        // without casts; the newer methods here cast for a reason). Auth is a
        // stable ownership read, so it stays outside the transaction.
        $isOwner = $legalCase->group && (int) $legalCase->group->user_id === (int) $userId;
        $isJudgeParticipant = $legalCase->judge && (int) $legalCase->judge->user_id === (int) $userId;
        if (! $isOwner && ! $isJudgeParticipant) {
            throw ValidationException::withMessages([__('You are not authorized to perform this action')]);
        }

        // Map the app-facing role to the stored participant role: the app sends
        // `defense_lawyer`, but the participant row is `defendant_lawyer`.
        $isConsultant = $data['role'] === 'consultant';
        $participantRole = $isConsultant
            ? CaseRole::CONSULTANT->value
            : CaseRole::DEFENDANT_LAWYER->value;

        $missingMessage = $isConsultant
            ? __('No consultant has been assigned to this case yet.')
            : __('No defense lawyer has been assigned to this case yet.');

        try {
            DB::beginTransaction();

            // Lock the case row BEFORE any other read in this transaction, so the
            // `alreadyOpined` check and the `awaiting_opinion` write commit
            // atomically against the SAME lock createOpinion holds. Must be the
            // first SQL here: under REPEATABLE READ the snapshot is pinned at the
            // first plain SELECT, so taking the locking read first is what lets
            // the later opinions()->exists() see a concurrently-committed opinion.
            $legalCase = $this->repo->findForUpdate($legalCase->id);
            if (! $legalCase) {
                throw ValidationException::withMessages([
                    'legal_case_id' => __('Legal case not found'),
                ]);
            }

            // Status guard (mirrors scheduleHearing): an opinion may only be
            // requested while the case is actually being heard (`new` /
            // `in_progress`). Blocks setting the gate on a not-yet-filed
            // `pending_lawyer` case — hidden from the group until officially
            // filed, so the notification + news would LEAK it — and on already-
            // ruled `ongoing`/`appeal`/`execution`/`closed` cases. Because the
            // gate can now only be set pre-first-ruling, it is always cleared
            // before appeal, so the final judgment needs no await gate of its own.
            // Checked BEFORE the await gate so a wrong-status call returns the
            // clearer stage message.
            if (! in_array($legalCase->status, [
                LegalCaseStatus::NEW->value,
                LegalCaseStatus::IN_PROGRESS->value,
            ], true)) {
                throw ValidationException::withMessages([
                    __('An opinion can only be requested for a case that is being heard'),
                ]);
            }

            // Part 3 gate: while the case is already awaiting a previously-
            // requested opinion, the judge may take NO further action until that
            // party responds. Re-read under the lock so it reflects a concurrent
            // set/clear.
            self::ensureNotAwaitingOpinion($legalCase);

            $participant = $legalCase->participants()
                ->where('role', $participantRole)
                ->first();

            if (! $participant) {
                throw ValidationException::withMessages([$missingMessage]);
            }

            $user = User::find($participant->user_id);
            if (! $user) {
                throw ValidationException::withMessages([$missingMessage]);
            }

            // Don't open a gate that can never close: if the requested party has
            // ALREADY filed their opinion at the current stage, the one-per-stage
            // rule (LegalCaseOpinionServices::createOpinion) would forbid them
            // from responding, wedging `awaiting_opinion` forever. Re-checked
            // UNDER THE LOCK, so a concurrent createOpinion (which also
            // lockForUpdate's this row) is serialised: either its opinion is
            // already visible here (reject) or it waits behind this transaction
            // and then sees our gate. The status guard above guarantees
            // new/in_progress, so the stage is always `new` (resolveStage
            // collapses pending/new/in_progress → new).
            $alreadyOpined = $legalCase->opinions()
                ->where('user_id', $participant->user_id)
                ->where('stage', LegalCaseStatus::NEW->value)
                ->exists();

            if ($alreadyOpined) {
                throw ValidationException::withMessages([
                    $isConsultant
                        ? __('The consultant has already submitted their opinion for this case')
                        : __('The defense lawyer has already submitted their opinion for this case'),
                ]);
            }

            // The judge acting on the case moves it from `new` to `in_progress`
            // ("being heard, pre-ruling"). Idempotent: a no-op once past `new`,
            // never touches later stages.
            $this->promoteToInProgressIfNew($legalCase);

            // Record which party the judge is now awaiting, INSIDE the lock so it
            // commits atomically with the promotion and the guarded reads. Stores
            // the PARTICIPANT role value (`defendant_lawyer` | `consultant`);
            // createOpinion clears it once that party files (and re-notifies the
            // judge).
            $legalCase->update(['awaiting_opinion' => $participantRole]);

            $body = $isConsultant
                ? [
                    'ar' => 'يطلب القاضي تقديم رأيك الاستشاري في القضية رقم ' . $legalCase->id,
                    'en' => 'The judge requests your consultant opinion on case number ' . $legalCase->id,
                ]
                : [
                    'ar' => 'يطلب القاضي تقديم مرافعتك في القضية رقم ' . $legalCase->id,
                    'en' => 'The judge requests your defense opinion on case number ' . $legalCase->id,
                ];

            // Fire the nudge (bell) and the group news row AFTER COMMIT: the gate
            // is already durably set, so a push/notification/news failure can
            // neither roll it back nor leave the case promoted-but-ungated (which
            // would otherwise let the judge rule while the await rule should still
            // hold). afterCommit runs inline inside DB::commit() here (no queue
            // worker), so each leg is wrapped — an uncaught throw would reach the
            // catch below and rollBack an already-committed transaction. The
            // database notification is still the durable record; on failure it
            // logs rather than 500-ing an already-gated request, and the lawyer
            // can still clear the gate by filing.
            DB::afterCommit(function () use ($legalCase, $user, $isConsultant, $body, $userId) {
                try {
                    Notification::send($user, new LegalCaseNotification($legalCase, [
                        'model_id' => $legalCase->id,
                        'title' => $isConsultant
                            ? ['ar' => 'طلب رأي استشاري', 'en' => 'Consultant opinion requested']
                            : ['ar' => 'طلب مرافعة الدفاع', 'en' => 'Defense opinion requested'],
                        'body' => $body,
                        'type' => $isConsultant ? 'opinion_requested_consultant' : 'opinion_requested_defense',
                    ]));
                } catch (\Throwable $e) {
                    logger()->warning('Opinion-request notification failed: ' . $e->getMessage());
                }

                // Group news row (bell above stays targeted at the nudged party).
                // Complete sentence → LegalCaseNews::generateContent `default`.
                try {
                    $this->repo->createCaseNews($legalCase, 'opinion_requested', $body, $userId, null);
                } catch (\Throwable $e) {
                    logger()->warning('Opinion-request news failed: ' . $e->getMessage());
                }

                // Realtime nudge: the gate is now SET (and the case may have
                // promoted new → in_progress), so any open case screen re-fetches
                // and disables the judge's actions. Fail-soft inside the helper.
                self::broadcastCaseUpdated($legalCase);
            });

            DB::commit();

            return __('The opinion request has been sent');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Schedule a hearing for a case — the "تحديد جلسة" action, which previously
     * had no backend at all. Only the presiding judge or a party may schedule.
     * All parties are notified.
     */
    public function scheduleHearing($legalCase, array $data)
    {
        $userId = auth()->id();

        $isJudge = $legalCase->group && (int) $legalCase->group->user_id === (int) $userId;
        $isParticipant = $legalCase->participants()->where('user_id', $userId)->exists();
        if (! $isJudge && ! $isParticipant) {
            throw ValidationException::withMessages([__('You are not authorized to perform this action')]);
        }

        // A hearing may only be scheduled while the case is actually being heard
        // (`new` / `in_progress`). This blocks a hearing on a `pending_lawyer`
        // case — hidden from the group until officially filed, so a hearing +
        // group-chat post + notifications would LEAK the held case — and on
        // already-ruled `ongoing`/`appeal`/`execution`/`closed` cases (matching
        // the app hiding the button post-ruling). Appeal-stage hearings are
        // intentionally out of scope.
        if (! in_array($legalCase->status, [
            LegalCaseStatus::NEW->value,
            LegalCaseStatus::IN_PROGRESS->value,
        ], true)) {
            throw ValidationException::withMessages([
                __('A hearing can only be scheduled for a case that is being heard'),
            ]);
        }

        // Part 3 gate: if the JUDGE has an opinion request outstanding, they may
        // not schedule a hearing until that party responds. Scoped to the judge
        // (this endpoint is open to any party) — the awaited lawyer scheduling
        // their own hearing is not the action being gated.
        if ($this->isCaseJudge($legalCase, (int) $userId)) {
            self::ensureNotAwaitingOpinion($legalCase);
        }

        $hearing = $legalCase->hearings()->create([
            'room_id' => $data['room_id'] ?? null,
            'created_by' => $userId,
            'scheduled_at' => $data['scheduled_at'],
            'status' => 'scheduled',
        ]);

        // Hearing creation stays open to any party (above), but the case only
        // advances `new → in_progress` when the JUDGE schedules it — a lawyer
        // scheduling a hearing creates the hearing without moving the case.
        // Idempotent and never touches later stages.
        if ($this->isCaseJudge($legalCase, (int) $userId)) {
            $this->promoteToInProgressIfNew($legalCase);
        }

        // No transaction here, so notify inline (try/catch so a failed FCM/DB
        // notification never fails the scheduling itself).
        try {
            $this->notifyPartiesOnHearing($legalCase, $hearing);
            // Mirror into the group chat so the whole group sees the session.
            if ($legalCase->group) {
                $this->events->postChat(
                    $legalCase->group,
                    'تم تحديد جلسة في قضية: ' . $legalCase->title,
                );
            }
            // Also write a group news row (the bell above stays targeted at the
            // parties). Complete-sentence content → generateContent `default`.
            $this->repo->createCaseNews($legalCase, 'hearing_scheduled', [
                'ar' => 'تم تحديد موعد جلسة للقضية رقم ' . $legalCase->id,
                'en' => 'A hearing has been scheduled for case #' . $legalCase->id,
            ], $userId, null);
        } catch (\Throwable $e) {
            logger()->warning('Hearing notification failed: ' . $e->getMessage());
        }

        return $hearing;
    }

    public function listHearings($legalCase)
    {
        return $legalCase->hearings()->get();
    }

    private function notifyPartiesOnHearing($legalCase, $hearing): void
    {
        $userIds = $legalCase->participants()->pluck('user_id')->unique()->filter();
        if ($userIds->isEmpty()) {
            return;
        }
        $users = User::whereIn('id', $userIds)->get();
        Notification::send($users, new LegalCaseNotification($legalCase, [
            'model_id' => $legalCase->id,
            'title' => [
                'ar' => 'موعد جلسة جديد',
                'en' => 'New hearing scheduled',
            ],
            'body' => [
                'ar' => 'تم تحديد موعد جلسة للقضية رقم ' . $legalCase->id,
                'en' => 'A hearing has been scheduled for case #' . $legalCase->id,
            ],
            'type' => 'hearing_scheduled',
        ]));
    }

    /**
     * Is [$userId] the presiding judge of this case — the group owner OR the
     * attached `judge` participant? Mirrors the check requestOpinion makes
     * inline; used to gate the judge-only status write in scheduleHearing.
     */
    private function isCaseJudge($legalCase, int $userId): bool
    {
        $isOwner = $legalCase->group && (int) $legalCase->group->user_id === $userId;
        $isJudgeParticipant = $legalCase->judge && (int) $legalCase->judge->user_id === $userId;

        return $isOwner || $isJudgeParticipant;
    }

    /**
     * Advance a case from `new` to `in_progress` ("being heard, pre-ruling").
     * Idempotent: only ever acts when the status is literally `new`, so it is a
     * no-op once the case has moved on and NEVER pulls a later stage
     * (`ongoing`/`appeal`/`execution`/`closed`) backwards.
     */
    private function promoteToInProgressIfNew($legalCase): void
    {
        if ($legalCase->status === LegalCaseStatus::NEW->value) {
            $legalCase->update(['status' => LegalCaseStatus::IN_PROGRESS->value]);
            $legalCase->refresh();
        }
    }

    /**
     * Part 3 gate — while a case is awaiting a previously-requested opinion
     * (`awaiting_opinion` = `defendant_lawyer` | `consultant`), the judge may
     * take no further action until that party responds. Shared by
     * requestOpinion / scheduleHearing (here) and storeFirstJudgment
     * (LegalCaseJudgmentService). STATIC so the judgment service can call it
     * without injecting LegalCaseService — LegalCaseService already depends on
     * LegalCaseJudgmentService, so the reverse injection would close a DI cycle.
     */
    public static function ensureNotAwaitingOpinion($legalCase): void
    {
        if (! empty($legalCase->awaiting_opinion)) {
            throw ValidationException::withMessages([
                'legal_case_id' => __('Waiting for the requested opinion before taking another action'),
            ]);
        }
    }

    /**
     * Fire the lightweight `LegalCaseUpdated` realtime signal so an open case
     * screen re-fetches when the case's gate/status changes. STATIC for the same
     * reason as ensureNotAwaitingOpinion: the opinion + judgment services fire it
     * without injecting LegalCaseService (which would close a DI cycle). ALWAYS
     * call it from `DB::afterCommit` (or outside any transaction) so the model
     * reflects committed state. Fail-soft — a broadcast failure must never bubble
     * out and undo the committed mutation it is merely announcing. Mirrors how
     * MessageService wraps `broadcast(new MessageSent(...))`.
     */
    public static function broadcastCaseUpdated($legalCase): void
    {
        try {
            broadcast(new LegalCaseUpdated($legalCase));
        } catch (\Throwable $e) {
            logger()->warning('Broadcast LegalCaseUpdated failed: ' . $e->getMessage());
        }
    }

    /**
     * The presiding judge WITHDRAWS an outstanding opinion request, lifting the
     * `awaiting_opinion` gate so the judge is not blocked forever if the awaited
     * party never files. Judge-only (mirrors requestOpinion's auth). Same lock
     * discipline as requestOpinion / createOpinion: the row is re-fetched with
     * `findForUpdate` FIRST, so the read-and-clear commits atomically against a
     * concurrent createOpinion (which also locks the row).
     *
     * Does NOT revert the status: the case is legitimately being heard
     * (`in_progress`) — only the await block is withdrawn. No news/chat row (a
     * withdrawal is not group-worthy). After commit it optionally tells the
     * ex-awaited party and broadcasts the cleared gate; both fail-soft. Returns
     * the updated case so the app re-renders with the gate lifted.
     */
    public function cancelOpinionRequest($legalCase)
    {
        $userId = auth()->id();

        // JUDGE-ONLY (mirror requestOpinion): only the presiding judge who set
        // the gate may withdraw it. Ownership read stays outside the transaction.
        $isOwner = $legalCase->group && (int) $legalCase->group->user_id === (int) $userId;
        $isJudgeParticipant = $legalCase->judge && (int) $legalCase->judge->user_id === (int) $userId;
        if (! $isOwner && ! $isJudgeParticipant) {
            throw ValidationException::withMessages([__('You are not authorized to perform this action')]);
        }

        try {
            DB::beginTransaction();

            // Lock the row FIRST (same rationale as requestOpinion): the
            // awaiting_opinion read + clear then commit atomically against a
            // concurrent createOpinion that is filing the awaited opinion.
            $legalCase = $this->repo->findForUpdate($legalCase->id);
            if (! $legalCase) {
                throw ValidationException::withMessages([
                    'legal_case_id' => __('Legal case not found'),
                ]);
            }

            // Nothing to cancel — no request is outstanding. A clear localized
            // 422 (not a silent success) so the judge knows the state, e.g. when
            // the awaited party filed a moment earlier and cleared it themselves.
            if (empty($legalCase->awaiting_opinion)) {
                throw ValidationException::withMessages([
                    'legal_case_id' => __('There is no pending opinion request to cancel'),
                ]);
            }

            // Which party was awaited — captured before the clear so the optional
            // withdrawal notice can target them.
            $awaitedRole = $legalCase->awaiting_opinion;
            $awaitedParticipant = $legalCase->participants()
                ->where('role', $awaitedRole)
                ->first();
            $awaitedUser = $awaitedParticipant ? User::find($awaitedParticipant->user_id) : null;

            // Lift the gate. Status is deliberately untouched — the case is still
            // being heard; only the await block is removed.
            $legalCase->update(['awaiting_opinion' => null]);

            // After commit: tell the ex-awaited party the request was withdrawn,
            // and broadcast the cleared gate so an open case screen re-renders.
            // Both fail-soft — the gate is already durably lifted, so neither may
            // roll it back. afterCommit runs inline here (no queue worker), so an
            // uncaught throw would reach the catch below and rollBack an
            // already-committed transaction — hence the per-leg try/catch.
            DB::afterCommit(function () use ($legalCase, $awaitedUser) {
                if ($awaitedUser) {
                    try {
                        Notification::send($awaitedUser, new LegalCaseNotification($legalCase, [
                            'model_id' => $legalCase->id,
                            'title' => [
                                'ar' => 'تم سحب طلب الرأي',
                                'en' => 'Opinion request withdrawn',
                            ],
                            'body' => [
                                'ar' => 'سحب القاضي طلب الرأي في القضية رقم ' . $legalCase->id,
                                'en' => 'The judge withdrew the opinion request on case number ' . $legalCase->id,
                            ],
                            'type' => 'opinion_request_cancelled',
                        ]));
                    } catch (\Throwable $e) {
                        logger()->warning('Opinion-request-cancel notification failed: ' . $e->getMessage());
                    }
                }

                self::broadcastCaseUpdated($legalCase);
            });

            DB::commit();

            $legalCase->refresh();

            return $legalCase;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    public function create($request)
    {
        try {
            DB::beginTransaction();

            $userId = auth()->id();
            $participants = $request['participants'];
            $request['user_id'] = $userId;
            $group = $this->groupRepo->find($request['group_id']);
            if (!$group) {
                throw ValidationException::withMessages([__('Group not found')]);
            }
            $this->validateUserCanCreateCase($group);
            foreach ($participants as $participant) {
                if ($participant['role'] == 'defendant') {
                    // A defendant may be ANY group member — citizen, lawyer or
                    // consultant — EXCEPT the judge (group owner), who is inherently
                    // un-suable. The request only validates `exists:users,id`, so this
                    // is the real gate: membership (fail-closed), then the judge, then
                    // anyone granted `lawsuit_immunity`. Status-agnostic on purpose: a
                    // non-`accepted` status value must not turn a legitimate defendant
                    // into a false rejection.
                    $defendantRole = $group->users()
                        ->where('user_id', $participant['user_id'])
                        ->first()?->pivot?->role;
                    // Must be a member of the group (fail-closed).
                    if ($defendantRole === null) {
                        throw ValidationException::withMessages([__('The defendant must be a member of the group')]);
                    }
                    // Only the JUDGE (group owner) is inherently un-suable — compare by
                    // user_id so it holds even if the owner's stored pivot role has drifted.
                    if ((int) $participant['user_id'] === (int) $group->user_id || $defendantRole === GroupRole::JUDGE->value) {
                        throw ValidationException::withMessages([__('The judge cannot be sued')]);
                    }
                    // Granted immunity blocks any remaining role (citizen / lawyer / consultant).
                    if ($this->groupPermissionService->hasPermission($participant['user_id'], $group, 'lawsuit_immunity')) {
                        throw ValidationException::withMessages([__('The defendant has immunity against lawsuits')]);
                    }
                }
            }

            // The case is HELD with the plaintiff lawyer until they officially
            // file it (= write their opinion). Set `pending_lawyer` EXPLICITLY —
            // the DB default is `new`, which would leak the case straight into
            // the group before the lawyer files. The transition to `new`, and
            // the deferred case-filed effects (news + chat + judge/defendant
            // notifications), happen in LegalCaseOpinionServices::createOpinion.
            $request['status'] = LegalCaseStatus::PENDING_LAWYER->value;

            $legalCase = $this->repo->create($request);
            $attachments = $this->collectAttachments($request);
            $this->uploadAttachments($legalCase, $attachments);
            $participants[] = [
                'user_id' => $userId,
                'role' => 'plaintiff',
            ];
            $participants[] = [
                'user_id' => $group->user_id,
                'role' => 'judge',
            ];

            $legalCase->participants()->createMany($participants);
            $legalCase->groupLaws()->attach($request['group_law_ids']);

            // Credit the plaintiff for filing — the citizen points the profile
            // shows and the post-filing "reward" popup promises. Idempotent per
            // case; kept in the transaction so it commits atomically with the case.
            $this->points->onCaseFiled($legalCase, $userId);

            // Register BEFORE committing so the callback fires AFTER the real
            // commit. Called after `DB::commit()` (no active transaction) it
            // runs synchronously (no queue worker here), so any throw would
            // propagate out of create() and 500 an already-committed case. The
            // try/catch keeps a failed notification from doing that.
            // Only the plaintiff-lawyer assignment fires at creation now (type
            // `plaintiff_lawyer_assign`, routing them to the intake screen). The
            // judge/defendant "case filed" notifications, the news entry and the
            // group-chat post are DEFERRED to official filing — see
            // fireCaseFiledEffects, called from
            // LegalCaseOpinionServices::createOpinion once the plaintiff lawyer
            // files. Until then the case is invisible to everyone but the
            // plaintiff side.
            DB::afterCommit(function () use ($legalCase) {
                try {
                    $this->sendNotificationToPlaintiffLawyer($legalCase);
                } catch (\Throwable $e) {
                    logger()->warning('Plaintiff-lawyer notification failed: ' . $e->getMessage());
                }
            });

            DB::commit();

            return $legalCase;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * The "case officially filed" side effects, DEFERRED out of create() until
     * the plaintiff lawyer files their opinion (Part 2). Called from
     * LegalCaseOpinionServices::createOpinion on the `pending_lawyer → new`
     * transition, inside its DB::afterCommit. Fires the news feed entry, the
     * judge/defendant "case filed" bell notifications, and the group-chat post.
     *
     * Fail-soft (self-contained try/catch): this runs after the opinion has
     * already committed, so a failed notification must never bubble out and 500
     * an already-filed case.
     */
    public function fireCaseFiledEffects($legalCase): void
    {
        try {
            $group = $legalCase->group;
            if (! $group) {
                return;
            }

            // News feed entry (actor = filer, subject = defendant) — was created
            // at creation time; the filer is stored on the case as `user_id`.
            $defendantUserId = $legalCase->defendant?->user_id;
            if ($defendantUserId) {
                $this->repo->createCaseNews($legalCase, 'case_created', [
                    'ar' => 'تم إنشاء القضية',
                    'en' => 'Legal case created',
                ], $legalCase->user_id, $defendantUserId);
            }

            // Bell notifications: defendant (filed against you) + judge (filed in
            // your group).
            $this->sendCaseFiledNotifications($legalCase, $group);

            // Group-chat mirror.
            $this->events->postChat(
                $group,
                'تم رفع قضية جديدة: ' . $legalCase->title,
            );
        } catch (\Throwable $e) {
            logger()->warning('Case-filed effects failed: ' . $e->getMessage());
        }
    }

    private function validateUserCanCreateCase($group): void
    {
        $userId = auth()->id();

        // Membership + creator-role are checked status-agnostically, exactly as
        // before — narrowing them to `accepted` here could newly BREAK filing
        // for a real creator if any live membership row carries a status other
        // than the literal `accepted`. (Verify with the group_user status query
        // in the deploy checklist.)
        $membersQuery = $group->users();

        $creator = (clone $membersQuery)
            ->where('user_id', $userId)
            ->first();

        if (!$creator) {
            throw ValidationException::withMessages([
                __('You must be a member of the group to create a legal case')
            ]);
        }

        // Any role may file now (client request): a judge, lawyer, consultant or
        // citizen can be a plaintiff. The old citizen-only creator gate was
        // removed here and in the app. Immunity still protects the DEFENDANT
        // side (see create()), and the head-count minimum below still applies.

        // EXCEPT the group's own judge (its owner) — they preside over every case
        // in the group, so filing one would be judging their own lawsuit
        // (conflict of interest). JG-033.
        if ((int) $userId === (int) $group->user_id) {
            throw ValidationException::withMessages([
                __('The group judge cannot file a case in their own group')
            ]);
        }

        // The minimum head-count counts ACCEPTED members only — the actual row-62
        // fix: a still-pending invitee is not yet a warm body for a fair case.
        // (`assignDefendantLawyer` already scopes counts the same way.)
        $acceptedQuery = (clone $membersQuery)->wherePivot('status', 'accepted');

        // Count lawyers OTHER than the filer: the filer is the plaintiff, never a
        // case lawyer, so a two-lawyer group where the filer is one of them still
        // has only ONE assignable lawyer and must be blocked (mirrors the app gate
        // `_lawyers.length < 2`, whose list already excludes the filer).
        $lawyersCount = (clone $acceptedQuery)
            ->wherePivot('role', GroupRole::LAWYER->value)
            ->wherePivot('user_id', '!=', $userId)
            ->count();

        // A case needs a plaintiff lawyer AND a distinct defendant lawyer, so the
        // group must hold at least TWO lawyers — the old "2 lawyers OR 2 citizens"
        // rule let a case be filed with a single lawyer (JG-032).
        if ($lawyersCount < 2) {
            throw ValidationException::withMessages([
                __('At least 2 lawyers are required in the group to create a legal case')
            ]);
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
        $userId = auth()->id();

        // Only the DEFENDANT of this case may appoint their defence lawyer —
        // the endpoint was previously open to any subscribed user on any case.
        $isDefendant = $case->participants()
            ->where('user_id', $userId)
            ->where('role', CaseRole::DEFENDANT->value)
            ->exists();

        if (! $isDefendant) {
            throw ValidationException::withMessages([__('You are not authorized to perform this action')]);
        }

        // Assignable while the case is still open at first instance (new /
        // in_progress / ongoing) OR under APPEAL — a convicted defendant who had
        // no lawyer must be able to appoint one to defend the appeal (C1).
        // `in_progress` is first-instance too (a hearing was scheduled), so the
        // defendant can still appoint counsel there. Only execution / closed are
        // too late. (`pending_lawyer` never reaches here: the defendant cannot
        // yet see a case that has not been officially filed.)
        if (! in_array(
            $case->status,
            [
                LegalCaseStatus::NEW->value,
                LegalCaseStatus::IN_PROGRESS->value,
                LegalCaseStatus::ONGOING->value,
                LegalCaseStatus::APPEAL->value,
            ],
            true
        )) {
            throw ValidationException::withMessages([__('The case is no longer open for assigning a lawyer')]);
        }

        // One defence lawyer per case: if one is already assigned, block a
        // re-assignment (in any stage, appeal included). The appeal path exists
        // for the defendant who reached appeal WITHOUT a lawyer — so the guard
        // permits assignment precisely when none exists yet.
        $existingLawyer = $case->participants()
            ->where('role', CaseRole::DEFENDANT_LAWYER->value)
            ->exists();

        if ($existingLawyer) {
            throw ValidationException::withMessages([__('A defendant lawyer is already assigned to this case')]);
        }

        // The chosen lawyer must actually be a `lawyer` member of the case's
        // group — not an arbitrary user id.
        $isGroupLawyer = $case->group
            ->users()
            ->where('user_id', $request['lawyer_id'])
            ->wherePivot('role', GroupRole::LAWYER->value)
            ->wherePivot('status', 'accepted')
            ->exists();

        if (! $isGroupLawyer) {
            throw ValidationException::withMessages([__('The selected lawyer is not a lawyer in this group')]);
        }

        // The same lawyer cannot represent both sides — a conflict of interest.
        // The app hides the plaintiff's lawyer from the picker, but the request
        // only validates `exists`, so this is the enforcing gate.
        $plaintiffLawyerId = $case->participants()
            ->where('role', CaseRole::PLAINTIFF_LAWYER->value)
            ->value('user_id');

        if ($plaintiffLawyerId !== null && (int) $plaintiffLawyerId === (int) $request['lawyer_id']) {
            throw ValidationException::withMessages([__('The plaintiff lawyer cannot also defend the defendant')]);
        }

        // You can't hire the very person suing you: reject when the chosen
        // lawyer is the case's plaintiff (the opposing party). Self-defense —
        // lawyer_id == the defendant — stays allowed, so only the plaintiff is
        // blocked here.
        $plaintiffId = $case->participants()
            ->where('role', CaseRole::PLAINTIFF->value)
            ->value('user_id');

        if ($plaintiffId !== null && (int) $plaintiffId === (int) $request['lawyer_id']) {
            throw ValidationException::withMessages([__('The plaintiff cannot be assigned as the defence lawyer')]);
        }

        $case->participants()->create([
            'user_id' => $request['lawyer_id'],
            'role' => CaseRole::DEFENDANT_LAWYER->value,
        ]);

        return $case;
    }

    private function sendNotificationToPlaintiffLawyer($legalCase)
    {
        $plaintiffLawyer = $legalCase->plaintiffLawyer;
        if ($plaintiffLawyer) {
            $data = [
                'model_id' => $legalCase->id,
                'title' => [
                    'ar' => 'قضية قانونية جديدة',
                    'en' => 'New Legal Case',
                ],
                'body' => [
                    'ar' => 'تم تعيينك كمحامي للمدعي في القضية رقم ' . $legalCase->id,
                    'en' => 'You have been assigned as the plaintiff lawyer in case number ' . $legalCase->id,
                ],
                // Routes the app to the purpose-built PlaintiffLawyerCaseView
                // (the dedicated intake screen), not generic case details. This
                // notification is sent ONLY to the plaintiff lawyer below, so the
                // type change is scoped to them alone.
                'type' => 'plaintiff_lawyer_assign',
            ];
            Notification::send($plaintiffLawyer->user, new LegalCaseNotification($legalCase, $data));
        }
    }

    /**
     * Notify the two parties a filing must reach but previously did not: the
     * DEFENDANT (a case was filed against them) and the group's JUDGE (a case
     * was filed in their court). Only the plaintiff lawyer was ever notified.
     * The defendant is read from the case's `defendant` party relation; the
     * judge is the group owner (attached as the `judge` participant via
     * `$group->user_id`).
     */
    private function sendCaseFiledNotifications($legalCase, $group): void
    {
        // Read the defendant from the case's `defendant` party relation (this
        // now runs at official-filing time, not creation, so there is no
        // in-memory participants array to walk).
        $defendant = $legalCase->defendant?->user;
        if ($defendant) {
            Notification::send($defendant, new LegalCaseNotification($legalCase, [
                'model_id' => $legalCase->id,
                'title' => [
                    'ar' => 'قضية جديدة مرفوعة ضدك',
                    'en' => 'A new case filed against you',
                ],
                'body' => [
                    'ar' => 'تم رفع قضية جديدة ضدك برقم ' . $legalCase->id,
                    'en' => 'A new case (#' . $legalCase->id . ') has been filed against you',
                ],
                'type' => 'case_filed_against_you',
            ]));
        }

        $judge = User::find($group->user_id);
        if ($judge) {
            Notification::send($judge, new LegalCaseNotification($legalCase, [
                'model_id' => $legalCase->id,
                'title' => [
                    'ar' => 'قضية جديدة في مجموعتك',
                    'en' => 'New case in your group',
                ],
                'body' => [
                    'ar' => 'تم رفع قضية جديدة برقم ' . $legalCase->id . ' في مجموعتك',
                    'en' => 'A new case (#' . $legalCase->id . ') was filed in your group',
                ],
                'type' => 'case_filed_in_group',
            ]));
        }

        // The PLAINTIFF filer (`legal_cases.user_id`) — until now the only party
        // never told when their case actually reaches the judge. Official filing
        // is the plaintiff lawyer's act, not the citizen's, so the filer has no
        // way to know their case moved from "held with my lawyer" to "before the
        // judge" without this. New type `case_officially_filed` routes them to
        // the live case (distinct from `plaintiff_lawyer_assign`, which routes
        // the lawyer to intake at creation time).
        $plaintiff = User::find($legalCase->user_id);
        if ($plaintiff) {
            Notification::send($plaintiff, new LegalCaseNotification($legalCase, [
                'model_id' => $legalCase->id,
                'title' => [
                    'ar' => 'تم رفع قضيتك رسميًا',
                    'en' => 'Your case is now before the judge',
                ],
                'body' => [
                    'ar' => 'راجع محاميك قضيتك رقم ' . $legalCase->id . ' وأصبحت الآن أمام القاضي',
                    'en' => 'Your lawyer reviewed your case #' . $legalCase->id . ' and it is now before the judge',
                ],
                'type' => 'case_officially_filed',
            ]));
        }
    }

    public function getCasesStatus($groupId = null)
    {
        // Settle finished execution cases first so the counters are honest.
        $this->repo->closeExpiredExecutionCases($groupId);
        // Pass the caller's id through: the `pending_lawyer` counter is
        // user-scoped (only the assigned plaintiff lawyer's «بانتظار رفعي»
        // cases), unlike the group-wide status tallies.
        return $this->repo->getCasesStatus($groupId, auth()->id());
    }
}
