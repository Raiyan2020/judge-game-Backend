<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = auth('sanctum')->user();
        $notifications = $user->notifications()->latest()->paginate(20);

        return \responder::success($notifications);
    }

    public function markAsRead(Request $request)
    {
        /** @var User $user */
        $user = auth('sanctum')->user();

        if ($request->has('id')) {
            $notification = $user->notifications()->findOrFail($request->id);
            $notification->markAsRead();
        } else {
            $user->unreadNotifications->markAsRead();
        }

        return \responder::success(__('Notifications marked as read'));
    }
}
