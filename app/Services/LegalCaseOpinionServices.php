<?php

namespace App\Services;

use App\Enums\CaseRole;
use App\Enums\LegalCaseStatus;
use App\Models\LegalCaseOpinion;
use App\Notifications\LegalCaseNotification;
use App\Repositories\LegalCaseRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;


class LegalCaseOpinionServices
{
    public function __construct(
        protected LegalCaseRepository $repo,
        protected PointsService $points,
        protected GroupEventService $events,
        // Injected to fire the DEFERRED "case officially filed" effects (news +
        // judge/defendant notifications + chat) at the pending_lawyer → new
        // transition. No DI cycle: LegalCaseService does not depend on this
        // service.
        protected LegalCaseService $legalCaseService,
    ) {}


    public function createOpinion($request)
    {
        try {
            DB::beginTransaction();
            // Lock the case row before the pending-status read so two concurrent
            // plaintiff-lawyer submissions can't both compute isOfficialFiling
            // and double-fire the case-filed effects (no unique constraint).
            $legalCase = $this->repo->findForUpdate($request['legal_case_id']);

            if (!$legalCase) {
                throw ValidationException::withMessages([
                    'legal_case_id' => __('Legal case not found')
                ]);
            }

            // Part 2 gate: a `pending_lawyer` case is held with the plaintiff
            // lawyer until they officially file it. ONLY the plaintiff lawyer may
            // write an opinion on it — reject everyone else BEFORE resolveRole()
            // runs, since resolveRole() would otherwise self-assign a group
            // consultant as a party (and award points) on a case that is not yet
            // officially filed. `add-opinion` validates only `exists`, so this is
            // the enforcing gate mirroring the `show` 403.
            if ($legalCase->status === LegalCaseStatus::PENDING_LAWYER->value
                && $legalCase->participantRoleFor(auth()->id()) !== CaseRole::PLAINTIFF_LAWYER->value) {
                throw ValidationException::withMessages([
                    __('This case has not been officially filed yet'),
                ]);
            }

            $stage = $this->resolveStage($legalCase);

            $role = $this->resolveRole($legalCase);

            // OFFICIAL FILING: the plaintiff lawyer writing their opinion on a
            // `pending_lawyer` case IS the act that officially files it. Captured
            // BEFORE the status flip below so the afterCommit branch is decided
            // on the pre-transition status.
            $isOfficialFiling = $legalCase->status === LegalCaseStatus::PENDING_LAWYER->value
                && $role === CaseRole::PLAINTIFF_LAWYER->value;

            // One opinion per user per stage — a lawyer / consultant cannot file
            // twice for the same case at the same stage. There was no guard, so
            // the same opinion could be submitted repeatedly. (A later stage —
            // e.g. an appeal — is a different `stage`, so it stays allowed.)
            $alreadySubmitted = $legalCase->opinions()
                ->where('user_id', auth()->id())
                ->where('stage', $stage)
                ->exists();

            if ($alreadySubmitted) {
                throw ValidationException::withMessages([
                    __('You have already submitted your opinion for this case')
                ]);
            }

            $opinionData = [
                'user_id' => auth()->id(),
                'opinion' => $request['opinion'] ?? null,
                'final_requests' => $request['final_requests'] ?? null,
                'role' => $role,
                'legal_arguments' => $request['legal_arguments'] ?? null,
                'stage' => $stage,
            ];

            $legalCaseOpinion = $legalCase->opinions()->create($opinionData);

            $attachments = $this->collectAttachments($request);
            $this->uploadAttachments($legalCaseOpinion, $attachments);

            // Official filing flips the case `pending_lawyer → new`, inside the
            // transaction so it commits atomically with the opinion. The opinion
            // was already stored at stage `new` (resolveStage maps
            // pending_lawyer → 'new'), so it lands correctly first-instance.
            if ($isOfficialFiling) {
                $legalCase->update(['status' => LegalCaseStatus::NEW->value]);
            }

            // Register BEFORE committing so the notification fires after the
            // real commit (matching requestAppeal, which already orders it
            // correctly) — otherwise it runs synchronously and a throw rolls
            // back an already-committed opinion.
            DB::afterCommit(function () use ($legalCase, $legalCaseOpinion, $isOfficialFiling) {
                // On the pending_lawyer → new transition, fire the DEFERRED
                // case-filed set (news + judge/defendant "case filed" bells +
                // group chat) and SUPPRESS the two opinion notifications below —
                // they would duplicate the case-filed notice to the same people.
                if ($isOfficialFiling) {
                    $this->legalCaseService->fireCaseFiledEffects($legalCase);
                    return;
                }

                $this->notifyDefendantOnNewOpinion($legalCase, $legalCaseOpinion);

                // N4 — an opinion filed while the case is under APPEAL is an
                // appeal opinion the judge must review, but
                // notifyDefendantOnNewOpinion returns early off the NEW stage, so
                // nobody was told. Gate on the case STATUS (not resolveStage's
                // derived stage, which collapses every non-NEW status to APPEAL
                // and would misfire on an ordinary ongoing-case opinion).
                if ($legalCase->status === LegalCaseStatus::APPEAL->value) {
                    $this->notifyJudgeOnAppealOpinion($legalCase);
                }

                // The judge must also hear about every FIRST-INSTANCE opinion
                // (new/in_progress/ongoing) — from any giver — not only the
                // appeal-stage one above. Gate on the case STATUS, not
                // resolveStage() (which collapses ongoing→appeal).
                $this->notifyJudgeOnFirstInstanceOpinion($legalCase, $legalCaseOpinion);
            });

            DB::commit();

            return $legalCase;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }



    private function notifyDefendantOnNewOpinion($legalCase, $legalCaseOpinion)
    {
        // First-instance, pre-ruling: `new` OR `in_progress` (a hearing was
        // scheduled but no ruling yet). The `pending_lawyer → new` official
        // filing suppresses this call entirely (it fires the case-filed set
        // instead), so this path serves legacy cases already at `new`.
        if (! in_array(
            $legalCase->status,
            [LegalCaseStatus::NEW->value, LegalCaseStatus::IN_PROGRESS->value],
            true
        )) {
            return;
        }

        if ($legalCaseOpinion->role !== CaseRole::PLAINTIFF_LAWYER->value) {
            return;
        }

        $defendant = $legalCase->defendant;
        if (! $defendant || ! $defendant->user) {
            return;
        }

        $data = [
            'model_id' => $legalCase->id,
            'title' => [
                'ar' => 'تم رفع قضيه ضدك',
                'en' => 'A case has been filed against you',
            ],
            'body' => [
                'ar' => 'تم رفع قضيه ضدك رقم ' . $legalCase->id,
                'en' => 'A case has been filed against you with number ' . $legalCase->id,
            ],
            'type' => 'new_legal_case',
        ];

        Notification::send($defendant->user, new LegalCaseNotification($legalCase, $data));
    }

    private function notifyJudgeOnAppealOpinion($legalCase): void
    {
        $judge = $legalCase->judge;
        if (! $judge || ! $judge->user) {
            return;
        }

        $data = [
            'model_id' => $legalCase->id,
            'title' => [
                'ar' => 'رأي استئناف جديد',
                'en' => 'New appeal opinion',
            ],
            'body' => [
                'ar' => 'تم تقديم رأي استئناف في القضية رقم ' . $legalCase->id,
                'en' => 'An appeal opinion was submitted for case number ' . $legalCase->id,
            ],
            'type' => 'appeal_opinion',
        ];

        Notification::send($judge->user, new LegalCaseNotification($legalCase, $data));
    }

    private function notifyJudgeOnFirstInstanceOpinion($legalCase, $legalCaseOpinion): void
    {
        // First-instance only (new/in_progress/ongoing). Use the raw status, NOT
        // resolveStage() (which maps every non-NEW status to APPEAL and would
        // misfire on an appeal-stage opinion already handled above). `in_progress`
        // is first-instance too — the judge must still hear about opinions filed
        // after a hearing is scheduled.
        if (! in_array(
            $legalCase->status,
            [
                LegalCaseStatus::NEW->value,
                LegalCaseStatus::IN_PROGRESS->value,
                LegalCaseStatus::ONGOING->value,
            ],
            true
        )) {
            return;
        }

        $judge = $legalCase->judge;
        if (! $judge || ! $judge->user) {
            return;
        }

        // Don't notify the judge about an opinion they submitted themselves.
        if ($judge->user_id === $legalCaseOpinion->user_id) {
            return;
        }

        $giver = $this->giverLabel($legalCaseOpinion->role);

        $data = [
            'model_id' => $legalCase->id,
            'title' => [
                'ar' => 'رأي جديد في القضية',
                'en' => 'New opinion on the case',
            ],
            'body' => [
                'ar' => $giver['ar'] . ' رأيه في القضية رقم ' . $legalCase->id,
                'en' => $giver['en'] . ' on case number ' . $legalCase->id,
            ],
            'type' => 'case_opinion',
        ];

        Notification::send($judge->user, new LegalCaseNotification($legalCase, $data));
    }

    /**
     * Maps a CaseRole value to a bilingual "the X submitted their opinion" label
     * naming the giver (plaintiff lawyer / defendant lawyer / consultant).
     *
     * @return array{ar: string, en: string}
     */
    private function giverLabel(?string $role): array
    {
        return match ($role) {
            CaseRole::PLAINTIFF_LAWYER->value => [
                'ar' => 'قدّم محامي المدعي',
                'en' => "The plaintiff's lawyer submitted their opinion",
            ],
            CaseRole::DEFENDANT_LAWYER->value => [
                'ar' => 'قدّم محامي المدعى عليه',
                'en' => "The defendant's lawyer submitted their opinion",
            ],
            CaseRole::CONSULTANT->value => [
                'ar' => 'قدّم المستشار',
                'en' => 'The consultant submitted their opinion',
            ],
            default => [
                'ar' => 'قُدّم',
                'en' => 'An opinion was submitted',
            ],
        };
    }

    private function collectAttachments($request)
    {
        return [
            'images' => $request['images'] ?? [],
            'videos' => $request['videos'] ?? [],
            'audios' => $request['audios'] ?? [],
        ];
    }

    private function resolveStage($legalCase): string
    {
        // First-instance covers the whole pre-ruling arc: `pending_lawyer` (held
        // with the plaintiff lawyer), `new`, and `in_progress` (being heard). All
        // three map to the `new` stage so a first-instance opinion — the
        // plaintiff lawyer's official filing included — lands in the `new` bucket
        // and the judge's ruling button lights. Everything from `ongoing` onward
        // is appeal-stage.
        return in_array($legalCase->status, [
            LegalCaseStatus::PENDING_LAWYER->value,
            LegalCaseStatus::NEW->value,
            LegalCaseStatus::IN_PROGRESS->value,
        ], true)
            ? LegalCaseStatus::NEW->value
            : LegalCaseStatus::APPEAL->value;
    }

    public function requestAppeal(array $request)
    {
        try {
            DB::beginTransaction();

            $legalCase = $this->repo->find($request['legal_case_id']);

            if (!$legalCase) {
                throw ValidationException::withMessages([
                    'legal_case_id' => __('Legal case not found'),
                ]);
            }

            if ($legalCase->status === LegalCaseStatus::CLOSED->value) {
                throw ValidationException::withMessages([
                    'legal_case_id' => __('Cannot request appeal for a closed case'),
                ]);
            }

            // The appeal-specific message must be reachable: it has to run BEFORE
            // the generic `!== ONGOING` guard, otherwise an already-`appeal` case
            // trips the generic branch and this clearer message is dead code.
            if ($legalCase->status === LegalCaseStatus::APPEAL->value) {
                throw ValidationException::withMessages([
                    'legal_case_id' => __('This case is already under appeal'),
                ]);
            }

            if ($legalCase->status !== LegalCaseStatus::ONGOING->value) {
                throw ValidationException::withMessages([
                    'legal_case_id' => __('Appeal can only be requested for ongoing cases'),
                ]);
            }

            $role = $this->getAppealRequesterRole($legalCase);

            $legalCase->update(['status' => LegalCaseStatus::APPEAL->value]);

            $opinionData = [
                'user_id' => auth()->id(),
                'opinion' => $request['opinion'] ?? null,
                'role' => $role,
                'stage' => LegalCaseStatus::APPEAL->value,
            ];

            $legalCaseOpinion = $legalCase->opinions()->create($opinionData);



            $this->createAppealNews($legalCase, $role);

            DB::afterCommit(function () use ($legalCase) {
                $this->notifyJudgeOnAppealRequest($legalCase);
            });

            DB::commit();

            return $legalCase;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function getAppealRequesterRole($legalCase): string
    {
        $user = auth()->user();

        // Prefer the lawyer role for a self-representing defendant so they may
        // request an appeal (their `defendant` row alone would be rejected).
        $role = $legalCase->participantRoleFor($user->id);

        if ($role !== null) {
            $allowedRoles = [
                CaseRole::PLAINTIFF_LAWYER->value,
                CaseRole::DEFENDANT_LAWYER->value,
                CaseRole::CONSULTANT->value,
            ];

            if (in_array($role, $allowedRoles, true)) {
                return $role;
            }

            throw ValidationException::withMessages([
                'user' => __('Only plaintiff lawyer, defendant lawyer, or consultant may request appeal'),
            ]);
        }

        if ($legalCase->group?->users()
            ->where('user_id', $user->id)
            ->wherePivot('role', CaseRole::CONSULTANT->value)
            ->exists()
        ) {
            return CaseRole::CONSULTANT->value;
        }

        throw ValidationException::withMessages([
            'user' => __('Only plaintiff lawyer, defendant lawyer, or consultant may request appeal'),
        ]);
    }

    private function createAppealNews($legalCase, string $role): void
    {
        $this->repo->createCaseNews(
            $legalCase,
            'case_appeal_request',
            [
                'ar' => 'تم طلب استئناف للقضية رقم ' . $legalCase->id,
                'en' => 'An appeal request was made for the case number ' . $legalCase->id,
            ],
            auth()->id(),
            $legalCase->judge?->user_id
        );

        // Mirror into the group chat (bell already sent to the judge).
        if ($legalCase->group) {
            $this->events->postChat(
                $legalCase->group,
                'تم طلب استئناف في قضية: ' . $legalCase->title,
            );
        }
    }

    private function notifyJudgeOnAppealRequest($legalCase): void
    {
        $judge = $legalCase->judge;
        if (! $judge || ! $judge->user) {
            return;
        }

        $data = [
            'model_id' => $legalCase->id,
            'title' => [
                'ar' => 'طلب استئناف',
                'en' => 'Appeal Request',
            ],
            'body' => [
                'ar' => 'تم طلب الاستئناف للقضية رقم ' . $legalCase->id,
                'en' => 'An appeal has been requested for case number ' . $legalCase->id,
            ],
            'type' => 'appeal_request',
        ];

        Notification::send($judge->user, new LegalCaseNotification($legalCase, $data));
    }

    private function resolveRole($legalCase): string
    {
        $user = auth()->user();

        // Prefer the lawyer role when the user holds both a party and a lawyer
        // row (self-defense), so the opinion is stored under `defendant_lawyer`
        // (not `defendant`) and renders in the footer.
        $role = $legalCase->participantRoleFor($user->id);

        if ($role !== null) {
            return $role;
        }

        return $this->assignConsultantIfEligible($legalCase, $user);
    }

    private function assignConsultantIfEligible($legalCase, $user): string
    {
        $isConsultantInGroup = $legalCase->group->users()
            ->where('user_id', $user->id)
            ->wherePivot('role', CaseRole::CONSULTANT->value)
            ->exists();

        $hasConsultant = $legalCase->participants()
            ->where('role', CaseRole::CONSULTANT->value)
            ->exists();

        if (!$hasConsultant) {

            if (!$isConsultantInGroup) {
                throw ValidationException::withMessages([
                    'user' => __('Only a consultant can initiate the case opinion'),
                ]);
            }

            $legalCase->participants()->create([
                'user_id' => $user->id,
                'role' => CaseRole::CONSULTANT->value,
            ]);

            // Consultant is now a party to the case → award participation points.
            $this->points->onConsultantParticipation($legalCase, $user->id);

            return CaseRole::CONSULTANT->value;
        }

        throw ValidationException::withMessages([
            'user' => __('You are not assigned to this case'),
        ]);
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

    public function reviewOpinion(LegalCaseOpinion $opinion, bool $isCorrect)
    {

        $opinion->update([
            'is_correct' => $isCorrect,
        ]);

        // N3 — tell the opinion author (consultant/lawyer) that the judge ADOPTED
        // their opinion, so they see the adoption in their notifications. Only on
        // a positive review; a rejection is not announced. Runs outside any
        // transaction here, so send directly (no DB::afterCommit). Fail-soft.
        if ($isCorrect === true) {
            $this->notifyAuthorOnOpinionAdopted($opinion);
        }

        return $opinion;
    }

    private function notifyAuthorOnOpinionAdopted(LegalCaseOpinion $opinion): void
    {
        $author = $opinion->user;
        if (! $author) {
            return;
        }

        $legalCase = $opinion->legalCase;
        if (! $legalCase) {
            return;
        }

        $data = [
            'model_id' => $legalCase->id,
            'title' => [
                'ar' => 'تم اعتماد رأيك',
                'en' => 'Your opinion was adopted',
            ],
            'body' => [
                'ar' => 'اعتمد القاضي رأيك في القضية رقم ' . $legalCase->id,
                'en' => 'The judge adopted your opinion on case number ' . $legalCase->id,
            ],
            'type' => 'opinion_adopted',
        ];

        try {
            Notification::send($author, new LegalCaseNotification($legalCase, $data));
        } catch (\Throwable $e) {
            \Log::warning('Opinion adopted notification failed: ' . $e->getMessage());
        }
    }
}
