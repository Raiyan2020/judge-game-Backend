<?php

namespace App\Services;

use App\Enums\CaseRole;
use App\Enums\GroupRole;
use App\Enums\LegalCaseStatus;
use App\Models\User;
use App\Notifications\LegalCaseNotification;
use App\Repositories\GroupRepository;
use App\Repositories\LegalCaseRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class LegalCaseService
{
    public function __construct(protected LegalCaseRepository $repo, protected GroupRepository $groupRepo, protected GroupPermissionService $groupPermissionService, protected GroupEventService $events) {}

    public function index($filters, $groupId)
    {
        // Lazily settle any full-distance cases before listing, so `execution`
        // cases that finished their enforcement window show as `closed`.
        $this->repo->closeExpiredExecutionCases($groupId);
        $filters['group_id'] = $groupId;
        return $this->repo->index($filters);
    }

    /**
     * Close a single case if it has finished its execution window. Called on
     * detail read so a case opened after the window shows as `closed`.
     */
    public function settleIfExecutionExpired($legalCase): void
    {
        if ($legalCase->status !== \App\Enums\LegalCaseStatus::EXECUTION->value) {
            return;
        }
        $this->repo->closeExpiredExecutionCases($legalCase->group_id);
        $legalCase->refresh();
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

        return $legalCase;
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

        $hearing = $legalCase->hearings()->create([
            'room_id' => $data['room_id'] ?? null,
            'created_by' => $userId,
            'scheduled_at' => $data['scheduled_at'],
            'status' => 'scheduled',
        ]);

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
                    // A defendant must be a CITIZEN of the group — a judge,
                    // lawyer or consultant is never the accused. The request only
                    // validates `exists:users,id`, so this is the real gate
                    // (the app filters the picker to citizens too). Status-agnostic
                    // on purpose: a non-`accepted` status value must not turn a
                    // legitimate citizen defendant into a false rejection.
                    $defendantRole = $group->users()
                        ->where('user_id', $participant['user_id'])
                        ->first()?->pivot?->role;
                    if ($defendantRole !== GroupRole::CITIZEN->value) {
                        throw ValidationException::withMessages([__('The defendant must be a citizen of the group')]);
                    }
                    if ($this->groupPermissionService->hasPermission($participant['user_id'], $group, 'lawsuit_immunity')) {
                        throw ValidationException::withMessages([__('The defendant has immunity against lawsuits')]);
                    }
                }
            }

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
            $this->createCaseNews($legalCase, $userId, $participants);

            // Register BEFORE committing so the callback fires AFTER the real
            // commit. Called after `DB::commit()` (no active transaction) it
            // runs synchronously (no queue worker here), so any throw would
            // propagate out of create() and 500 an already-committed case. The
            // try/catch keeps a failed notification from doing that.
            DB::afterCommit(function () use ($legalCase, $group, $participants) {
                try {
                    $this->sendNotificationToPlaintiffLawyer($legalCase);
                    $this->sendCaseFiledNotifications($legalCase, $group, $participants);
                    // Mirror into the group chat (bell + news already sent above).
                    $this->events->postChat(
                        $group,
                        'تم رفع قضية جديدة: ' . $legalCase->title,
                    );
                } catch (\Throwable $e) {
                    logger()->warning('Case-filed notification failed: ' . $e->getMessage());
                }
            });

            DB::commit();

            return $legalCase;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function createCaseNews($legalCase, $userId, $participants)
    {
        $defendant = null;
        foreach ($participants as $participant) {
            if ($participant['role'] == 'defendant') {
                $defendant = $participant['user_id'];
                break;
            }
        }
        if ($defendant) {
            $this->repo->createCaseNews($legalCase, 'case_created', [
                'ar' => 'تم إنشاء القضية',
                'en' => 'Legal case created',
            ], $userId, $defendant);
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

        // Assignable while the case is still open at the first-instance stage
        // (new or ongoing) — the defence lawyer accepts/appeals the first
        // ruling, so late appointment at `ongoing` must stay possible; only
        // appeal / execution / closed are too late.
        if (! in_array($case->status, [LegalCaseStatus::NEW->value, LegalCaseStatus::ONGOING->value], true)) {
            throw ValidationException::withMessages([__('The case is no longer open for assigning a lawyer')]);
        }

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
                'type' => 'new_legal_case',
            ];
            Notification::send($plaintiffLawyer->user, new LegalCaseNotification($legalCase, $data));
        }
    }

    /**
     * Notify the two parties a filing must reach but previously did not: the
     * DEFENDANT (a case was filed against them) and the group's JUDGE (a case
     * was filed in their court). Only the plaintiff lawyer was ever notified.
     * The defendant id is read from the participants array; the judge is the
     * group owner (attached as the `judge` participant via `$group->user_id`).
     */
    private function sendCaseFiledNotifications($legalCase, $group, array $participants): void
    {
        $defendantId = null;
        foreach ($participants as $participant) {
            if (($participant['role'] ?? null) === 'defendant') {
                $defendantId = $participant['user_id'];
                break;
            }
        }

        if ($defendantId) {
            $defendant = User::find($defendantId);
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
    }

    public function getCasesStatus($groupId = null)
    {
        // Settle finished execution cases first so the counters are honest.
        $this->repo->closeExpiredExecutionCases($groupId);
        return $this->repo->getCasesStatus($groupId);
    }
}
