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
             $this->repo->update($user);
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


       private function createCode()
    {
        // return rand(1111, 9999);
        return "1234";
    }



}
