<?php

use App\Http\Controllers\Api\V1\AdsController;
use App\Http\Controllers\Api\V1\AgoraController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BannerController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\CountryController;
use App\Http\Controllers\Api\V1\CouponController;
use App\Http\Controllers\Api\V1\GroupController;
use App\Http\Controllers\Api\V1\GroupLawController;
use App\Http\Controllers\Api\V1\GroupMemberController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\LastUpdateController;
use App\Http\Controllers\Api\V1\LegalCaseController;
use App\Http\Controllers\Api\V1\LegalCaseJudgmentController;
use App\Http\Controllers\Api\V1\LegalCaseNewsController;
use App\Http\Controllers\Api\V1\LegalCaseOpinionController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PackageController;
use App\Http\Controllers\Api\V1\PackageSubscriptionController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\RoomController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\UserRankController;
use App\Http\Controllers\Api\V1\UserStatisticsController;
use App\Http\Controllers\Payment\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'setLocale'], function () {

    // Throttled: the activation code is a 4-digit value that (today) is a
    // known constant, and these routes are unauthenticated. Without a rate
    // limit, codes and phone numbers can be enumerated at machine speed.
    Route::group(['prefix' => 'auth', 'middleware' => 'throttle:10,1'], function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('verify-code', [AuthController::class, 'verifyCode']);
    });

    Route::group(['middleware' => 'auth:sanctum'], function () {
        // Pusher private-channel authorization. Laravel's default
        // `/broadcasting/auth` route sits at the site ROOT on `web`/session
        // middleware — unreachable behind this deployment (only `/api/*` is
        // proxied to Laravel) and it 403s a Sanctum bearer anyway. Re-expose it
        // here so it lives at `/api/broadcasting/auth`, is proxied, and
        // authenticates via the bearer token. `Broadcast::auth()` runs the
        // channel callbacks in routes/channels.php and signs the response.
        Route::post('broadcasting/auth', fn (Request $request) => Broadcast::auth($request));

        Route::group(['prefix' => 'auth'], function () {
            Route::get('profile', [ProfileController::class, 'show']);
            Route::post('profile', [ProfileController::class, 'update']);
            Route::post('update-setting', [ProfileController::class, 'updateSetting']);
            Route::post('fcm-token', [ProfileController::class, 'updateFcmToken']);
            Route::post('logout', [AuthController::class, 'logout']);

            // Changing the login identity is verified, and throttled like the
            // unauthenticated auth routes: the code is 4 digits, so an
            // unlimited confirm endpoint is a brute-forceable takeover of
            // whichever number was staged.
            Route::post('phone-change/request', [AuthController::class, 'requestPhoneChange'])
                ->middleware('throttle:5,1');
            Route::post('phone-change/confirm', [AuthController::class, 'confirmPhoneChange'])
                ->middleware('throttle:5,1');
        });

        // Notifications (Laravel database notifications). The controller already
        // existed but was never routed, so the app fell back to a static mock.
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::post('notifications/mark-read', [NotificationController::class, 'markAsRead']);

        Route::apiResource('groups', GroupController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('my-groups', [GroupController::class, 'myGroups']);
        Route::get('my-invitations', [GroupController::class, 'myInvitations']);
        // Group-scoped reads are members-only: without `groupMember` any
        // signed-in user could dump any group's members (phone numbers
        // included), laws, cases and statistics by guessing the id.
        // `accept`/`reject` are intentionally NOT gated — the caller there is a
        // pending invitee, not yet a member.
        Route::get('groups/{group}/members', [GroupMemberController::class, 'index'])
            ->middleware('groupMember');
        Route::post('groups/{group}/invite', [GroupMemberController::class, 'inviteMember'])
            ->middleware('groupMember');
        Route::post('groups/{group}/accept', [GroupMemberController::class, 'acceptInvitation']);
        Route::post('groups/{group}/reject', [GroupMemberController::class, 'rejectInvitation']);
        Route::post('groups/{group}/users/{user}/remove-member', [GroupMemberController::class, 'removeMember']);
        Route::post('groups/{group}/change-role', [GroupMemberController::class, 'changeRole']);
        Route::post('groups/{group}/leave', [GroupMemberController::class, 'leaveGroup']);

        Route::apiResource('group-laws', GroupLawController::class)->only(['store', 'update', 'destroy']);
        Route::get('group-laws/{group}', [GroupLawController::class, 'index'])
            ->middleware('groupMember');
        // Members-only, like every other group read — without this any signed-in
        // user could read any group's chat by iterating the id, and a kicked
        // member kept full access (JG-024).
        Route::get('groups/{group}/messages', [MessageController::class, 'getGroupMessages'])
            ->middleware('groupMember');
        Route::get('groups/{group}/members-by-role', [GroupMemberController::class, 'getMemberByRole'])
            ->middleware('groupMember');
        Route::get('private-messages', [MessageController::class, 'getPrivateMessages']);
        Route::post('messages', [MessageController::class, 'store']);
        Route::get('chats', [MessageController::class, 'getChats']);
        Route::post('messages/{messageId}/vote', [MessageController::class, 'votePoll']);
        Route::post('ads', [AdsController::class, 'store']);
        Route::get('legal-cases/{legalCase}', [LegalCaseController::class, 'show']);
        Route::get('legal-cases/groups/{group}', [LegalCaseController::class, 'index'])
            ->middleware('groupMember');
        Route::get('legal-cases-status', [LegalCaseController::class, 'getCaseStatus']);
        Route::PUT('legal-case-opinions/{opinion}/review',[LegalCaseOpinionController::class, 'reviewOpinion']);
        Route::get('news', [LegalCaseNewsController::class, 'index']);
        Route::get('packages', [PackageController::class, 'index']);
        Route::post('packages/subscribe', [PackageSubscriptionController::class, 'subscribe']);
        Route::get('groups/{group}/users/{user}/statistics', [UserStatisticsController::class, 'show'])
            ->middleware('groupMember');
        // The signed-in user's achievement ladder in this group (real progress).
        Route::get('groups/{group}/achievements', [\App\Http\Controllers\Api\V1\AchievementController::class, 'index'])
            ->middleware('groupMember');
        Route::post('coupons', [CouponController::class, 'store']);
        Route::get('permissions', [PermissionController::class, 'index']);
        Route::post('permissions', [PermissionController::class, 'togglePermission']);
        Route::get('rooms/{room}/token', [AgoraController::class, 'generateToken']);
        Route::get('rooms', [RoomController::class, 'index']);
        Route::post('rooms', [RoomController::class, 'store']);
        Route::get('rooms/{room}', [RoomController::class, 'show']);
        Route::post('rooms/{room}/join', [RoomController::class, 'join']);
        Route::post('rooms/{room}/leave', [RoomController::class, 'leave']);
        Route::post('rooms/{room}/end', [RoomController::class, 'endRoom']);
        Route::post('rooms/{room}/toggle-mute', [RoomController::class, 'toggleMute']);
        // Hearings for a case (schedule / list). Judge or a party only — not
        // subscription-gated (coordination, not a paid judicial action).
        Route::get('legal-cases/{legalCase}/hearings', [\App\Http\Controllers\Api\V1\HearingController::class, 'index']);
        Route::post('legal-cases/{legalCase}/hearings', [\App\Http\Controllers\Api\V1\HearingController::class, 'store']);

        // Manually close a case that is in execution with a final judgment. Not
        // subscription-gated: finishing a case you are already party to should
        // never be blocked by a lapsed subscription.
        Route::post('legal-cases/{legalCase}/close', [LegalCaseController::class, 'close']);

        Route::group(['middleware' => 'checkActiveSubscription'], function () {
            Route::post('legal-cases', [LegalCaseController::class, 'store']);
            Route::post('assign-lawyer', [LegalCaseController::class, 'assignDefendantLawyer']);
            Route::post('add-opinion', [LegalCaseOpinionController::class, 'addOpinion']);
            Route::post('request-appeal', [LegalCaseOpinionController::class, 'requestAppeal']);
            Route::post('add-first-judgment', [LegalCaseJudgmentController::class, 'storeFirstJudgment']);
            Route::post('add-final-judgment', [LegalCaseJudgmentController::class, 'storeFinalJudgment']);
            Route::post('accept-judgment', [LegalCaseJudgmentController::class, 'acceptJudgment']);
            // `add-appeal-request` is the alias the app actually calls; it maps
            // to the same `requestAppeal` as `request-appeal` above.
            Route::post('add-appeal-request', [LegalCaseOpinionController::class, 'requestAppeal']);
        });
    });

    // MyFatoorah server-to-server payment webhook — unauthenticated (no bearer
    // from the gateway), CSRF-exempt (api group), verified via HMAC signature
    // (when enforced) AND always re-checked against the MyFatoorah API. Throttled
    // so a flood of forged invoice ids can't drive unbounded outbound API calls.
    Route::post('payment/webhook', [WebhookController::class, 'handle'])
        ->middleware('throttle:60,1');

    Route::get('home', HomeController::class);
    Route::get('banners', [BannerController::class, 'index']);
    Route::get('countries', CountryController::class);
    Route::get('last-updates', LastUpdateController::class);
    Route::post('contact', [ContactController::class, 'store']);
    Route::get('contact-settings', [SettingController::class, 'contactSettings']);
    Route::get('setting-pages', [SettingController::class, 'getSettingsPages']);
    Route::get('setting-laws', [SettingController::class, 'getSettingsLaws']);
    Route::get('users-ranking', [UserRankController::class, 'index']);
    // The leaderboard detail sheet. Public like the board it belongs to, and
    // registered AFTER the index so the literal path wins over the wildcard.
    Route::get('users-ranking/{user}', [UserRankController::class, 'show']);
    // The "best groups" board (JG-010).
    Route::get('groups-ranking', [GroupController::class, 'bestGroups']);
});
