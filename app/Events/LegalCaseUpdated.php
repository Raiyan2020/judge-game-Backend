<?php

namespace App\Events;

use App\Models\LegalCase;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Lightweight "the case changed, re-fetch it" realtime signal. Mirrors
 * MessageSent: ShouldBroadcastNow (no queue worker on this deployment), a
 * PRIVATE channel, and a stable `broadcastAs` alias the app binds by name.
 *
 * Broadcast on `legal-case.{id}` (the app subscribes to `private-legal-case.{id}`
 * — Pusher prefixes private channels — and authorizes via routes/channels.php).
 * The payload is intentionally minimal: the open case screen uses it as a nudge
 * to re-GET the full case, not as the source of truth, so only the three keys
 * that gate the UI travel here (id + status + awaiting_opinion).
 */
class LegalCaseUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $legalCase;

    public function __construct(LegalCase $legalCase)
    {
        $this->legalCase = $legalCase;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('legal-case.' . $this->legalCase->id);
    }

    public function broadcastAs()
    {
        return 'legal-case.updated';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->legalCase->id,
            'status' => $this->legalCase->status,
            'awaiting_opinion' => $this->legalCase->awaiting_opinion,
        ];
    }
}
