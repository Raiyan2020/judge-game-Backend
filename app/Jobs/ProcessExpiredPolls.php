<?php

namespace App\Jobs;

use App\Services\MessageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessExpiredPolls implements ShouldQueue
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
     * Execute the job. Delegates to the service so the poll-settlement rule
     * lives in ONE place — the same method is also called lazily on read
     * (group laws / messages fetch), which is what actually settles polls
     * while the scheduler is optional infrastructure.
     */
    public function handle(MessageService $messageService)
    {
        $messageService->resolveExpiredPolls();
    }
}
