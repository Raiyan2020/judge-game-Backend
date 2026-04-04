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
        $this->repo->update($user, ['code' => $code, 'fcm_token' => $request['fcm_token']]);
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
