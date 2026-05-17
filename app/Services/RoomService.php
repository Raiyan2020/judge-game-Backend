<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\NewCallNotification;
use App\Repositories\RoomRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\Fluent\Concerns\Has;
use Illuminate\Validation\ValidationException;

class RoomService
{


    public function __construct(protected RoomRepository $repo) {}

    public function index($request = [])
    {
        return $this->repo->index($request);
    }


    public function create($request)
    {
        try {
            DB::beginTransaction();
            $invitedUsers = $request['users'] ?? [];
            $users = collect([
                [
                    'user_id' => auth()->id(),
                    'is_admin' => true,
                ]
            ])->merge(
                collect($invitedUsers)->map(fn($userId) => [
                    'user_id' => $userId,
                    'is_admin' => false,
                ])
            );
            $request['user_id'] = auth()->id();

            $room = $this->repo->create($request);
            $room->users()->attach($users);


            DB::afterCommit(function () use ($invitedUsers, $room, $request) {
                if (!empty($invitedUsers) and $request['type'] === 'private') {
                    $this->notifyUsers($invitedUsers, $room, $request['password'] ?? null);
                }
            });
            DB::commit();


            return $room;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function notifyUsers($users, $room, $password = null)
    {
        $notifiables = User::whereIn('id', $users)->get();

        $data = [
            'model_id' => $room->id,
            'title' => [
                'ar' => __('New call invitation'),
                'en' => 'New call invitation',
            ],
            'body' => [
                'ar' => __('You have been invited to join a call in room :room with password :password', ['room' => $room->name, 'password' => $password ?? null]),
                'en' => 'You have been invited to join a call in room :room with password :password',
                ['room' => $room->name, 'password' => $password ?? null],
            ],
            'type' => 'call',
        ];

        Notification::send($notifiables, new NewCallNotification($data));
    }



public function join($room, $validatedData)
{
    if (
        $room->type === 'private' &&
        !Hash::check($validatedData['password'], $room->password)
    ) {
        throw ValidationException::withMessages([
            'password' => [__('The provided password is incorrect.')]
        ]);
    }

    $alreadyJoined = $room->users()
        ->where('user_id', auth()->id())
        ->exists();


    if ($alreadyJoined) {
        throw ValidationException::withMessages([
            'user' => [__('You already joined this room.')]
        ]);
    }

    $room->users()->attach(
        auth()->id(),
        [
            'is_admin' => false
        ] + (!empty($validatedData['is_muted'])
            ? ['is_muted' => true]
            : [])
    );


    return $room;
}
    public function leave($room)
    {
        $room->users()->detach(auth()->id());
    }

    public function toggleMute($room)
    {
        $room->users()->updateExistingPivot(auth()->id(), ['is_muted' => !$room->users()->find(auth()->id())->is_muted]);
    }
}
