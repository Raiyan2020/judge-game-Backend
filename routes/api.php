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

    Route::group(['prefix' => 'auth'], function () {
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
        });

        // Notifications (Laravel database notifications). The controller already
        // existed but was never routed, so the app fell back to a static mock.
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::post('notifications/mark-read', [NotificationController::class, 'markAsRead']);

        Route::apiResource('groups', GroupController::class)->only(['index', 'store', 'update']);
        Route::get('my-groups', [GroupController::class, 'myGroups']);
        Route::get('my-invitations', [GroupController::class, 'myInvitations']);
        Route::delete('groups/{group}/members/{userId}', [GroupController::class, 'removeMember']);
        Route::patch('groups/{group}/members/{userId}/role', [GroupController::class, 'changeRole']);
        Route::get('groups/{group}/members', [GroupMemberController::class, 'index']);
        Route::post('groups/{group}/invite', [GroupMemberController::class, 'inviteMember']);
        Route::post('groups/{group}/accept', [GroupMemberController::class, 'acceptInvitation']);
        Route::post('groups/{group}/reject', [GroupMemberController::class, 'rejectInvitation']);
        Route::post('groups/{group}/users/{user}/remove-member', [GroupMemberController::class, 'removeMember']);
        Route::post('groups/{group}/change-role', [GroupMemberController::class, 'changeRole']);
        Route::post('groups/{group}/leave', [GroupMemberController::class, 'leaveGroup']);

        Route::apiResource('group-laws', GroupLawController::class)->only(['store', 'update', 'destroy']);
        Route::get('group-laws/{group}', [GroupLawController::class, 'index']);
        Route::get('groups/{group}/messages', [MessageController::class, 'getGroupMessages']);
        Route::get('groups/{group}/members-by-role', [GroupMemberController::class, 'getMemberByRole']);
        Route::get('private-messages', [MessageController::class, 'getPrivateMessages']);
        Route::post('messages', [MessageController::class, 'store']);
        Route::get('chats', [MessageController::class, 'getChats']);
        Route::post('messages/{messageId}/vote', [MessageController::class, 'votePoll']);
        Route::post('ads', [AdsController::class, 'store']);
        Route::get('legal-cases/{legalCase}', [LegalCaseController::class, 'show']);
        Route::get('legal-cases/groups/{group}', [LegalCaseController::class, 'index']);
        Route::get('legal-cases-status', [LegalCaseController::class, 'getCaseStatus']);
        Route::PUT('legal-case-opinions/{opinion}/review',[LegalCaseOpinionController::class, 'reviewOpinion']);
        Route::get('news', [LegalCaseNewsController::class, 'index']);
        Route::get('packages', [PackageController::class, 'index']);
        Route::post('packages/subscribe', [PackageSubscriptionController::class, 'subscribe']);
        Route::get('groups/{group}/users/{user}/statistics', [UserStatisticsController::class, 'show']);
        // The signed-in user's achievement ladder in this group (real progress).
        Route::get('groups/{group}/achievements', [\App\Http\Controllers\Api\V1\AchievementController::class, 'index']);
        Route::post('coupons', [CouponController::class, 'store']);
        Route::get('permissions', [PermissionController::class, 'index']);
        Route::post('permissions', [PermissionController::class, 'togglePermission']);
        Route::get('rooms/{room}/token', [AgoraController::class, 'generateToken']);
        Route::get('rooms', [RoomController::class, 'index']);
        Route::post('rooms', [RoomController::class, 'store']);
        Route::get('rooms/{room}', [RoomController::class, 'show']);
        Route::post('rooms/{room}/join', [RoomController::class, 'join']);
        Route::post('rooms/{room}/leave', [RoomController::class, 'leave']);
        Route::post('rooms/{room}/toggle-mute', [RoomController::class, 'toggleMute']);
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
});
