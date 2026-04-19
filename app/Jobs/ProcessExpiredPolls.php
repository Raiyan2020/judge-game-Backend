<?php

namespace App\Jobs;

use App\Models\ChatPoll;
use App\Models\GroupLaw;
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
     * Execute the job.
     */
    public function handle()
    {
        ChatPoll::query()
            ->where('expires_at', '<=', now())
            ->where('is_closed', false)
            ->with('options.votes')
            ->chunk(50, function ($polls) {

                foreach ($polls as $poll) {

                    $poll->update(['is_closed' => true]);

                    $topOption = $poll->options
                        ->map(function ($opt) {
                            $opt->votes_count = $opt->votes->count();
                            return $opt;
                        })
                        ->sortByDesc('votes_count')
                        ->first();

                    if (!$topOption) {
                        continue;
                    }

                    $this->handlePollResult($poll, $topOption);
                }
            });
    }

    protected function handlePollResult($poll, $topOption)
    {
        switch ($poll->type) {

            case 'delete_law':
                if ($topOption->option === 'yes') {
                    GroupLaw::find($poll->group_law_id)?->delete();
                }
                break;

            case 'update_law':
                if ($topOption->option === 'yes') {
                    GroupLaw::find($poll->group_law_id)?->update([
                       ['description' => $poll->data['description'] ?? null],
                       ['reason' => $poll->data['reason'] ?? null],
                    ]);
                }
                break;

            case 'create_law':
                if ($topOption->option === 'yes') {
                    GroupLaw::create([
                        'group_id' => $poll->chatMessage?->chat?->group_id,
                        'description' => $poll->data['description'] ?? null,
                        'reason' => $poll->data['reason'] ?? null,
                    ]);
                }
                break;
        }
    }
}
