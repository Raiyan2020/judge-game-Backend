<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\CaseRole;
use App\Enums\LegalCaseJudgmentStage;
use App\Enums\LegalCaseStatus;
use App\Http\Resources\Api\V1\LegalCaseJudgmentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LegalCaseResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'my_role' => $this->myRole(),
            // True for a group consultant not yet on this case → the app shows
            // the "give a consultation" entry (initiating self-assigns them).
            'can_consult' => $this->canConsult(),
            'status' => $this->status,
            'status_text' => __($this->status),
            'damages' => $this->damages,
            'final_judgment' => $this->relationLoaded('finalJudgment') && $this->finalJudgment ? $this->finalJudgment?->judgment_type : null,

            // True once any hearing has been scheduled for this case, so the app
            // can hide/disable the "تحديد جلسة" action instead of letting it be
            // re-tapped after a successful schedule.
            'has_scheduled_hearing' => $this->relationLoaded('hearings')
                ? $this->hearings->where('status', 'scheduled')->isNotEmpty()
                : false,

            'group' =>  $this->relationLoaded('group') ? [
                'id' => $this->group?->id,
                'name' => $this->group?->name,
            ] : null,

            'plaintiff' => $this->relationLoaded('plaintiff') && $this->plaintiff ? [
                    'id' => $this->plaintiff?->user?->id,
                    'name' => $this->plaintiff?->user?->name,
                    'image' => $this->plaintiff?->user?->image,
                ] : null,


            'defendant' => $this->relationLoaded('defendant') && $this->defendant ? [
                'id' => $this->defendant?->user?->id,
                'name' => $this->defendant?->user?->name,
                'image' => $this->defendant?->user?->image,
            ] : null,

            'judge' => $this->relationLoaded('judge') && $this->judge ? [
                'id' => $this->judge?->user?->id,
                'name' => $this->judge?->user?->name,
                'image' => $this->judge?->user?->image,
            ] : null,

            'plaintiff_lawyer' => $this->relationLoaded('plaintiffLawyer') && $this->plaintiffLawyer ? [
                'id' => $this->plaintiffLawyer?->user?->id,
                'name' => $this->plaintiffLawyer?->user?->name,
                'image' => $this->plaintiffLawyer?->user?->image,
            ] : null,

            'defendant_lawyer' => $this->relationLoaded('defendantLawyer') && $this->defendantLawyer ? [
                'id' => $this->defendantLawyer?->user?->id,
                'name' => $this->defendantLawyer?->user?->name,
                'image' => $this->defendantLawyer?->user?->image,
            ] : null,
           
            'witnesses' => $this->relationLoaded('witnesses') ? 
                $this->witnesses->map(function ($witness) {
                    return [
                        'id' => $witness?->user?->id,
                        'name' => $witness?->user?->name,
                        'image' => $witness?->user?->image,
                    ];
                })
            : [],

            'laws' => $this->relationLoaded('groupLaws') ? GroupLawResource::collection($this->groupLaws) : [],
            'opinions' => [
                'plaintiff_lawyer' => [
                    'new' => $this->opinionByRoleAndStage(CaseRole::PLAINTIFF_LAWYER->value, LegalCaseStatus::NEW->value),
                    'appeal' => $this->opinionByRoleAndStage(CaseRole::PLAINTIFF_LAWYER->value, LegalCaseStatus::APPEAL->value),
                ],
                'defendant_lawyer' => [
                    'new' => $this->opinionByRoleAndStage(CaseRole::DEFENDANT_LAWYER->value, LegalCaseStatus::NEW->value),
                    'appeal' => $this->opinionByRoleAndStage(CaseRole::DEFENDANT_LAWYER->value, LegalCaseStatus::APPEAL->value),
                ],
                'consultant' => [
                    'new' => $this->opinionByRoleAndStage(CaseRole::CONSULTANT->value, LegalCaseStatus::NEW->value),
                    'appeal' => $this->opinionByRoleAndStage(CaseRole::CONSULTANT->value, LegalCaseStatus::APPEAL->value),
                ],
                'witnesses' => LegalCaseOpinionResource::collection($this->opinionsByRole(CaseRole::WITNESS->value)),
            ],
            'judgment' => $this->relationLoaded('judgments') ? LegalCaseJudgmentResource::collection($this->judgments) : [],
            'images' =>  $this->relationLoaded('media') ? MediaResource::collection($this->getMedia('images')) :[],
            'videos' =>  $this->relationLoaded('media') ? MediaResource::collection($this->getMedia('videos')) :[],
            'audios' =>  $this->relationLoaded('media') ? MediaResource::collection($this->getMedia('audios')) :[],
            'created_at' => $this->created_at->format('d/m/Y'),
        ];
    }

    private function opinionsByRole(string $role)
    {
        if (! $this->relationLoaded('opinions')) {
            return collect();
        }

        return $this->opinions->where('role', $role);
    }

    private function opinionsByRoleAndStage(string $role, string $stage)
    {
        if (! $this->relationLoaded('opinions')) {
            return null;
        }

        return $this->opinionsByRole($role)->where('stage', $stage)->first();
    }

    private function opinionByRoleAndStage(string $role, string $stage)
    {
        $opinion = $this->opinionsByRoleAndStage($role, $stage);

        return $opinion ? new LegalCaseOpinionResource($opinion) : null;
    }
}
