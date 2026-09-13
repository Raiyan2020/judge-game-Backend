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


    public function __construct(
        protected RoomRepository $repo,
        protected GroupEventService $events,
    ) {}

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


            // Capture the opener now — afterCommit runs synchronously in-request
            // (no queue worker), so auth() is still available, but reading it
            // once keeps the announcement's actor stable.
            $opener = auth()->user();
            DB::afterCommit(function () use ($invitedUsers, $room, $request, $opener) {
                $isPrivate = $room->type === 'private';

                if (!empty($invitedUsers) and $isPrivate) {
                    $this->notifyUsers($invitedUsers, $room, $request['password'] ?? null);
                }

                // EVERY room opening is announced in the group's news feed +
                // timeline. Previously only a public room announced, so opening a
                // private stream produced an invite bell and nothing else — the
                // group never learned a broadcast had started (QA: «فتح بث مباشر»
                // appeared in notifications but not in الأخبار).
                $this->announceRoom($room, $opener, $isPrivate);
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

    /**
     * Announce that a live room opened in the group, with the opener as the
     * actor. Group-guarded and fail-soft (notifyGroupEvent is per-channel
     * fail-soft).
     *
     * PUBLIC: news + bell + chat to the whole group.
     * PRIVATE: news + chat only — the invitees already got the targeted
     * [NewCallNotification] (which alone carries the password), so a second
     * group-wide bell would both double their alert and ping members who were
     * not invited. An EMPTY notifiables collection is how notifyGroupEvent is
     * told to skip the bell; the news row still tells the group a stream is on
     * air. The room password is never part of the copy.
     */
    private function announceRoom($room, $opener, bool $isPrivate): void
    {
        try {
            $this->fireRoomEvent($room, $opener, $isPrivate);
        } catch (\Throwable $e) {
            // Runs in an afterCommit callback: throwing here would reach
            // create()'s catch, which calls rollBack() on an ALREADY-committed
            // transaction — turning a created room into a 500.
            \Log::warning('Room announcement failed: ' . $e->getMessage());
        }
    }

    private function fireRoomEvent($room, $opener, bool $isPrivate): void
    {
        $group = $room->group;
        if (! $group) {
            \Log::warning("Room {$room->id} has no group — announcement skipped.");
            return;
        }

        $body = $isPrivate
            ? [
                'ar' => 'بدأت غرفة صوتية خاصة في مجموعة ' . $group->name . ': ' . $room->name,
                'en' => 'A private voice room has started in ' . $group->name . ': ' . $room->name,
            ]
            : [
                'ar' => 'بدأ بث مباشر في مجموعة ' . $group->name . ': ' . $room->name,
                'en' => 'A live stream has started in ' . $group->name . ': ' . $room->name,
            ];

        $this->events->notifyGroupEvent(
            $group,
            'live_stream_started',
            title: [
                'ar' => 'بث مباشر في ' . $group->name,
                'en' => 'Live stream in ' . $group->name,
            ],
            body: $body,
            actor: $opener,
            notifiables: $isPrivate ? collect() : null,
        );
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
