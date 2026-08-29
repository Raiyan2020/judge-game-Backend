<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\AgoraService;

class AgoraController extends Controller
{
    public function __construct(protected AgoraService $agoraSerives)
    {
    }

    public function generateToken(Room $room)
    {
        // SECURITY: only a CURRENT participant of the room may mint a publisher
        // token. Otherwise any authed user could stream into ANY room — including
        // a private room whose password they never passed. Membership mirrors the
        // exact source RoomService::join/leave use (the `users` belongsToMany
        // pivot). The owner stays allowed even after leaving the pivot.
        if (
            $room->user_id !== auth()->id() &&
            !$room->users()->where('user_id', auth()->id())->exists()
        ) {
            return response()->json([
                'status' => false,
                'msg' => __('You must join the room first'),
            ], 403);
        }

        $tokenData = $this->agoraSerives->generateToken($room);
        return \responder::success($tokenData);
    }
}
