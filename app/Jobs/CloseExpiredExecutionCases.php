<?php

namespace App\Jobs;

use App\Enums\LegalCaseStatus;
use App\Models\LegalCase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CloseExpiredExecutionCases implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        LegalCase::query()
            ->where('status', LegalCaseStatus::EXECUTION->value)
            ->whereHas('finalJudgment', function ($query) {
                $query->where('created_at', '<=', now()->subDays(7));
            })
            ->chunk(50, function ($cases) {
                foreach ($cases as $case) {
                    $case->update(['status' => LegalCaseStatus::CLOSED->value]);
                }
            });
    }
}