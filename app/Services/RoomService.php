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
            $request['user_id'] = auth()->id();

            $room = $this->repo->create($request);
            // Attach ONLY the creator (as admin) — invitees are notified, not
            // pre-joined. Pre-attaching everyone made the whole group show as
            // "present" before anyone entered AND let invitees skip the private
            // room's password (their pivot already existed). They now join
            // explicitly via `join`.
            $room->users()->attach(auth()->id(), ['is_admin' => true]);


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
            // The room's group, so tapping the push deep-links to that group's
            // live screen (FcmChannel prefers group_id for related_data).
            'group_id' => $room->group_id,
            'title' => [
                'ar' => 'دعوة إلى غرفة صوتية',
                'en' => 'New call invitation',
            ],
            'body' => [
                'ar' => 'تمت دعوتك للانضمام إلى غرفة ' . $room->name,
                'en' => 'You have been invited to join room ' . $room->name,
            ],
            'type' => 'call',
            // A private room's join requires the (hashed) password, so an invitee
            // could never enter without it. Deliver the plaintext in the in-app
            // notification data only — FcmChannel forwards just title/body/type/id
            // to the push, so it is NOT exposed on a lock-screen push or in system
            // logs. Null for a room created without one.
            'room_password' => $password,
        ];

        Notification::send($notifiables, new NewCallNotification($data));
    }



public function join($room, $validatedData)
{
    // The owner never needs the password for their own room.
    if (
        $room->type === 'private' &&
        $room->user_id !== auth()->id() &&
        !Hash::check($validatedData['password'] ?? '', $room->password)
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
    /**
     * Leave the call. The room STAYS open (even for the owner) so anyone —
     * including the owner — can come back. Ending the room for good is a
     * separate, owner-only action ([endRoom]).
     */
    public function leave($room)
    {
        $room->users()->detach(auth()->id());
    }

    /**
     * Permanently end (delete) the room — owner only.
     */
    public function endRoom($room)
    {
        if ($room->user_id !== auth()->id()) {
            throw ValidationException::withMessages([
                'user' => [__('Only the room owner can end the room.')],
            ]);
        }
        $room->delete();
    }

    public function toggleMute($room)
    {
        $room->users()->updateExistingPivot(auth()->id(), ['is_muted' => !$room->users()->find(auth()->id())->is_muted]);
    }
}
