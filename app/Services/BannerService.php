<?php

namespace App\Services;

use App\Enums\BannerType;
use App\Repositories\BannerRepository;

class BannerService {


    public function __construct(protected BannerRepository $repo)
    {
    }

    public function index(BannerType $type = BannerType::HOME){
        return $this->repo->index($type);
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


    public function activation($model)
    {
        return $this->repo->activation($model);
    }



}
