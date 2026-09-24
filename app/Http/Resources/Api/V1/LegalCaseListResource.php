<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class LegalCaseListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            // The real status string (pending_lawyer / new / in_progress / … ),
            // so an unfiltered list can distinguish items instead of assuming
            // the filtered status. Matches LegalCaseResource's `status`.
            'status' => $this->status,
            // The cover is deliberately NOT an evidence image (JG-038): evidence
            // is filing-time attachments, not a case photo, so using it as the
            // cover was wrong. The card falls back to its default artwork; the
            // evidence stays only in the attachments section.
            'image' => null,
            'plaintiff' => $this->whenLoaded('plaintiff', function () {
                return [
                    'id' => $this->plaintiff?->user?->id,
                    'name' => $this->plaintiff?->user?->name,
                    'image' => $this->plaintiff?->user?->image,
                ];
            }),

            'defendant' => $this->relationLoaded('defendant') ? [
                'id' => $this->defendant?->user?->id,
                'name' => $this->defendant?->user?->name,
                'image' => $this->defendant?->user?->image,
            ] : null,

            'plaintiff_lawyer' => $this->relationLoaded('plaintiffLawyer') ? [
                'id' => $this->plaintiffLawyer?->user?->id,
                'name' => $this->plaintiffLawyer?->user?->name,
                'image' => $this->plaintiffLawyer?->user?->image,
            ] : null,
            'defendant_lawyer' => $this->relationLoaded('defendantLawyer') ? [
                'id' => $this->defendantLawyer?->user?->id,
                'name' => $this->defendantLawyer?->user?->name,
                'image' => $this->defendantLawyer?->user?->image,
            ] : null,
            'remaining_execution_days' => $this->status === 'execution'
                ? $this->calculateRemainingExecutionDays()
                : null,
            'created_at' => $this->created_at->format('d/m/Y'),
        ];
    }

    private function calculateRemainingExecutionDays(): int
    {
        $finalJudgment = $this->finalJudgment;

        if (! $finalJudgment || ! $finalJudgment->created_at) {
            return 0;
        }

        $deadline = $finalJudgment->created_at->copy()->addDays(7);

        return max(0, now()->diffInDays($deadline, false));
    }
}

