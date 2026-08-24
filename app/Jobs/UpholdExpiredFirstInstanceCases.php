<?php

namespace App\Jobs;

use App\Services\LegalCaseJudgmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpholdExpiredFirstInstanceCases implements ShouldQueue
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
     * Execute the job. Delegates to the service so the uphold rule (BUG9) lives
     * in ONE place — the same method is also called lazily on read
     * (`LegalCaseService`), which is what actually settles cases while this
     * scheduled job is optional infrastructure. Mirrors
     * `CloseExpiredExecutionCases`.
     */
    public function handle(LegalCaseJudgmentService $service): void
    {
        $service->upholdExpiredFirstInstanceCases();
    }
}
