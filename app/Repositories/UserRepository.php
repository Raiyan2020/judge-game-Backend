<?php

namespace App\Repositories;


use App\Models\User;

class UserRepository extends BaseRepository
{
    /**
     * UserRepository constructor.
     * @param User $model
     */
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function checkUser($request)
    {
        return $this->model
            ->where('phone', $request['phone'])
            ->where('country_code', $request['country_code'])
            ->where('code', $request['code'])
            ->first();
    }


    public function getUserByPhone($phone, $country_code)
    {
        return $this->model->where('phone', $phone)->where('country_code', $country_code)->first();
    }

   
}
