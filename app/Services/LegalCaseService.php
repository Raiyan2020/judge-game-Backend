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
    public function __construct(protected LegalCaseRepository $repo, protected GroupRepository $groupRepo, protected GroupPermissionService $groupPermissionService) {}

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
                    // (the app filters the picker to citizens too).
                    $defendantRole = $group->users()
                        ->wherePivot('status', 'accepted')
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

        // Only ACCEPTED members count: a still-pending invitee is neither a real
        // member for the membership check nor a warm body toward the minimum
        // head-count. (`assignDefendantLawyer` already scopes the same way.)
        $membersQuery = $group->users()->wherePivot('status', 'accepted');

        $creator = (clone $membersQuery)
            ->where('user_id', $userId)
            ->first();

        if (!$creator) {
            throw ValidationException::withMessages([
                __('You must be a member of the group to create a legal case')
            ]);
        }

        // Only citizens file cases. A judge, lawyer or consultant is a court
        // officer, never a plaintiff — this replaces the judge-only block, which
        // let lawyers and consultants through. The app gates this too.
        if ($creator->pivot->role !== GroupRole::CITIZEN->value) {
            throw ValidationException::withMessages([
                __('Only citizens can create legal cases')
            ]);
        }

        $lawyersCount = (clone $membersQuery)
            ->wherePivot('role', GroupRole::LAWYER->value)
            ->count();

        $citizensCount = (clone $membersQuery)
            ->wherePivot('role', GroupRole::CITIZEN->value)
            ->count();

        if ($lawyersCount < 2 && $citizensCount < 2) {
            throw ValidationException::withMessages([
                __('At least 2 lawyers or 2 citizens are required in the group to create a legal case')
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
