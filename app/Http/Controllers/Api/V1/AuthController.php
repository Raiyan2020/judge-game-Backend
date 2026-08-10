<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\CheckCodeRequest;
use App\Http\Requests\Api\V1\Auth\ConfirmPhoneChangeRequest;
use App\Http\Requests\Api\V1\Auth\RequestPhoneChangeRequest;

class AuthController extends Controller
{
  public function __construct(protected AuthService $authService) {}

  /**
   * @param RegisterRequest $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function register(RegisterRequest $request)
  {
    $user = $this->authService->register($request->validated());
    return \responder::success(__('registered successfully'));
  }
 
  /**
   * @param LoginRequest $request
   * @return \Illuminate\Http\JsonResponse
   */

  public function login(LoginRequest $request)
  {
    $user = $this->authService->login($request->validated());
    return \responder::success(__('login successfully'));

  }

  /**
   * @param CheckCodeRequest $request
   * @return \Illuminate\Http\JsonResponse
   */

  public function verifyCode(CheckCodeRequest $request)
  {
    $user = $this->authService->checkCode($request->validated());

    // The counts load must come AFTER the guard: `checkCode` returns `false`
    // for a wrong code, and `false->loadCount()` threw a TypeError, so a user
    // who mistyped their code got HTTP 500 "Server Error" and the correct
    // message below was unreachable.
    if ($user) {
        $this->legalCaseWinLossCounts($user);

        return \responder::success(new UserResource($user->load('activeSubscription.package','points')));
    }

    return \responder::error(__('Error verification code try again'));
  }

   
  /**
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */

  /**
   * Step 1 of changing the account's phone: stage the new number and send it a
   * code. The live number is untouched until `confirmPhoneChange` succeeds.
   */
  public function requestPhoneChange(RequestPhoneChangeRequest $request)
  {
    $this->authService->requestPhoneChange($request->user(), $request->validated());

    return \responder::success(__('A verification code has been sent to the new number.'));
  }

  /**
   * Step 2: verify the code and adopt the staged number.
   */
  public function confirmPhoneChange(ConfirmPhoneChangeRequest $request)
  {
    $user = $this->authService->confirmPhoneChange($request->user(), $request->validated());

    return \responder::success(new UserResource($user->load('activeSubscription.package', 'points')));
  }

  public function logout(Request $request)
  {
    $request->user()->currentAccessToken()->delete();

    return \responder::success(__('logout successfully'));
  }

    private function legalCaseWinLossCounts($user)
    {
        return $user->loadCount([
            'plaintiffCases',

            'defendantCases',

            'plaintiffCases as plaintiff_wins_count' => function ($q) use ($user) {
                $q->where('winner_id', $user->id);
            },

            'plaintiffCases as plaintiff_losses_count' => function ($q) use ($user) {
                $q->whereNot('winner_id', $user->id)
                    ->whereNotNull('winner_id');
            },

            'defendantCases as defendant_wins_count' => function ($q) use ($user) {
                $q->where('winner_id', $user->id);
            },

            'defendantCases as defendant_losses_count' => function ($q) use ($user) {
                $q->whereNot('winner_id', $user->id)
                    ->whereNotNull('winner_id');
            },
        ]);
    }


}
