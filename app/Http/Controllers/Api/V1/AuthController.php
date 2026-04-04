<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\CheckCodeRequest;

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
    if ($user)  return \responder::success(new UserResource($user));

    return \responder::error(__('Error verification code try again'));
  }

   
  /**
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */

  public function logout(Request $request)
  {
    $request->user()->currentAccessToken()->delete();

    return \responder::success(__('logout successfully'));
  }


}
