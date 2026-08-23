<?php

namespace App\Repositories;

use App\Enums\BannerType;
use App\Models\Banner;

class BannerRepository extends BaseRepository
{
    /**
     * BannerRepository constructor.
     * @param Banner $model
     */
    public function __construct(Banner $model)
    {
        parent::__construct($model);
    }

    /**
     * Active banners for one placement. Defaults to HOME so existing callers
     * (GET /banners, GET /home) keep the behaviour they had before banners
     * gained a type.
     */
    public function index(BannerType $type = BannerType::HOME)
    {
       return $this->model->active()->ofType($type)->latest()->get();
    }
}
