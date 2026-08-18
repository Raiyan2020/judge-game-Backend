<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Room\CreateRoomRequest;
use App\Http\Requests\Api\V1\Room\JoinRoomRequest;
use App\Http\Resources\Api\V1\RoomResource;
use App\Models\Room;
use App\Services\RoomService;
use Illuminate\Http\Request;
use TaylanUnutmaz\AgoraTokenBuilder\RtcTokenBuilder;

class RoomController extends Controller
{

    public function __construct(protected RoomService $roomService)
    {
    }

    public function index(Request $request)
    {
        // Scope rooms to the group the app asked for. The app sends
        // `?group_id=`, and RoomRepository::index already filters on it — but
        // this used to call index() with no args, so the filter never applied
        // and every group saw every group's rooms.
        $rooms = $this->roomService->index($request->only('group_id'));
        return \responder::success(RoomResource::collection($rooms));
    }


    public function show(Room $room)
    {
        return \responder::success( new RoomResource($room->load('users', 'group')->loadCount('users')));
    }

    public function store(CreateRoomRequest $request)
    {
         $this->roomService->create($request->validated());
        return \responder::success(__('Room created successfully'));
    }

    public function join(Room $room , JoinRoomRequest $request)
    {
        $this->roomService->join($room, $request->validated());
        return \responder::success( new RoomResource($room->load('users', 'group')->loadCount('users')));

    }

    public function leave(Room $room)
    {
        $this->roomService->leave($room);
        return \responder::success(__('Left room successfully'));
    }

    public function endRoom(Room $room)
    {
        $this->roomService->endRoom($room);
        return \responder::success(__('Room ended successfully'));
    }

    public function toggleMute(Room $room)
    {
        $this->roomService->toggleMute($room);
        return \responder::success(__('User mute status toggled successfully'));
    }




  

  
}
