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
       $tokenData = $this->agoraSerives->generateToken($room);
       return \responder::success($tokenData);
    }
}
