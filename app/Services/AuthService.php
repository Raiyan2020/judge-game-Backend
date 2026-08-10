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


    /**
     * Step 1 of a phone change: stage the NEW number and issue a code for it.
     *
     * The live `users.phone` is deliberately untouched here. Writing it before
     * verification is what made a typo unrecoverable — login is by phone, so a
     * wrong digit locked the owner out of their own account with no way back —
     * and let anyone claim an unregistered number belonging to someone else.
     *
     * @return string the code, so the caller can deliver it to the NEW number
     */
    public function requestPhoneChange($user, array $request): string
    {
        $phone = $request['phone'];
        $countryCode = $request['country_code'];

        if ($user->phone === $phone && (string) $user->country_code === (string) $countryCode) {
            throw ValidationException::withMessages([
                'phone' => __('This is already your phone number.'),
            ]);
        }

        // Re-checked here as well as in the form request: the number could have
        // been registered by someone else between the two calls.
        if ($this->repo->getUserByPhone($phone, $countryCode)) {
            throw ValidationException::withMessages([
                'phone' => __('This phone number is already registered.'),
            ]);
        }

        $code = $this->createCode();

        $this->repo->update($user, [
            'pending_phone' => $phone,
            'pending_country_code' => $countryCode,
            'pending_phone_code' => $code,
            'pending_phone_expires_at' => now()->addMinutes(15),
        ]);

        # todo: send the code to the NEW number once SMS delivery is wired.
        // whatsapp($countryCode . $phone, $code . ' Is Your verification code.');

        return $code;
    }

    /**
     * Step 2: verify the staged number and make it the account's phone.
     *
     * Only this method writes `users.phone`, and only after matching a code
     * that was sent to the number being claimed.
     */
    public function confirmPhoneChange($user, array $request)
    {
        $pendingPhone = $user->pending_phone;
        $expiresAt = $user->pending_phone_expires_at;

        if (!$pendingPhone || !$user->pending_phone_code) {
            throw ValidationException::withMessages([
                'phone' => __('No pending phone change request.'),
            ]);
        }

        if ($expiresAt && $expiresAt->isPast()) {
            $this->clearPendingPhone($user);

            throw ValidationException::withMessages([
                'code' => __('The verification code has expired, please try again.'),
            ]);
        }

        if (!hash_equals((string) $user->pending_phone_code, (string) $request['code'])) {
            throw ValidationException::withMessages([
                'code' => __('Error verification code try again'),
            ]);
        }

        // Last-moment guard: someone may have registered this number while the
        // request was pending.
        if ($this->repo->getUserByPhone($pendingPhone, $user->pending_country_code)) {
            $this->clearPendingPhone($user);

            throw ValidationException::withMessages([
                'phone' => __('This phone number is already registered.'),
            ]);
        }

        $this->repo->update($user, [
            'phone' => $pendingPhone,
            'country_code' => $user->pending_country_code,
            // The code is burned with the change, exactly as the login code is:
            // a code that stays valid is replayable.
            'pending_phone' => null,
            'pending_country_code' => null,
            'pending_phone_code' => null,
            'pending_phone_expires_at' => null,
        ]);

        return $user->refresh();
    }

    private function clearPendingPhone($user): void
    {
        $this->repo->update($user, [
            'pending_phone' => null,
            'pending_country_code' => null,
            'pending_phone_code' => null,
            'pending_phone_expires_at' => null,
        ]);
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
