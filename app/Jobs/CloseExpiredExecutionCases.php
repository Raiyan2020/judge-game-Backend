<?php

namespace App\Jobs;

use App\Repositories\LegalCaseRepository;
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
     * Execute the job. Delegates to the repository so the close rule lives in
     * ONE place — the same method is also called lazily on read
     * (`LegalCaseService`), which is what actually settles cases while the
     * scheduler is optional infrastructure.
     */
    public function handle(LegalCaseRepository $repo)
    {
        $repo->closeExpiredExecutionCases();
    }
}