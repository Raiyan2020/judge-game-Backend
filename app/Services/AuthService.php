<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Validation\ValidationException;


class AuthService
{

    public function __construct(protected UserRepository $repo) {}

  
     public function register($request)
    {
        $existing = $this->repo->getUserByPhone($request['phone'], $request['country_code']);

        if ($existing) {
            throw ValidationException::withMessages([
                'phone' => __('This phone number is already registered.'),
            ]);
        }

        $request['code'] = $this->createCode();

        $user = $this->repo->create($request);

        # todo: send sms code
        // whatsapp($user->country_code . $user->phone, $request['code'] . ' Is Your activation code.');

        return $user;
    }

    public function createUser($request)
    {
        return $this->register($request);
    }

    public function login($request)
    {
        $user = $this->repo->getUserByPhone($request['phone'],$request['country_code']);
        if (!$user) {
            throw ValidationException::withMessages([
                'phone' => __('The provided credentials are incorrect, please register first.'),
            ]);
        }
        
        $code = $this->createCode();
        // Only overwrite the FCM token when the request actually carries one —
        // a login with an empty token must NOT null out a previously-good token
        // (which would silently stop all push for that user).
        $update = ['code' => $code];
        if (!empty($request['fcm_token'])) {
            $update['fcm_token'] = $request['fcm_token'];
        }
        $this->repo->update($user, $update);
        //whatsapp($user->country_code.$user->phone , $code.' Is Your activation code.');
    }


    public function checkCode($request)
    {
        $user = $this->repo->checkUser($request);
        if ($user) {
            // BURN the code. This used to call `update($user)` with no
            // attributes, and BaseRepository::update returns false without
            // writing when the payload is empty — so the code was never
            // cleared and stayed valid forever, replayable by anyone who knew
            // the phone number. A fresh code is minted by `login()`.
            $this->repo->update($user, ['code' => null]);
            $user['token'] = $this->generateToken($user);
            return $user;
        }

        return false;
    }

      private function generateToken($user)
    {
        $apiToken = $user->createToken('api');
        $accessToken = $apiToken->accessToken;
        $apiToken->accessToken->save();

        return $apiToken->plainTextToken;
    }


    /**
     * The activation code.
     *
     * SMS/WhatsApp delivery is still commented out below, so a random code
     * would lock every user out — the fixed code is what makes the app usable
     * today. It is therefore a CONFIG value, not a literal: once delivery is
     * wired, clear `STATIC_OTP` in the environment and this starts issuing
     * random codes with no code change.
     *
     * Until then, treat the account as protected only by rate limiting (see
     * the `throttle` on the auth routes) and by burning the code on use.
     */
    private function createCode()
    {
        $static = config('auth.static_otp');

        return $static !== null && $static !== ''
            ? (string) $static
            : (string) random_int(1000, 9999);
    }



}
