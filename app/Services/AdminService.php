<?php

namespace App\Services;

use App\Repositories\AdminRepository;

class AdminService {

    public function __construct(protected AdminRepository $repo)
    {
        $this->repo = $repo;
    }

    public function create($request)
    {
        return $this->repo->create($request);

    }

    public function update($model ,$request)
    {
        return $this->repo->update($model , $request);
    }


    public function delete($model)
    {
        return $this->repo->delete($model);
    }




}
