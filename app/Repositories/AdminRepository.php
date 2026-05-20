<?php

namespace App\Repositories;
use App\Models\Admin;
use Illuminate\Support\Facades\DB;

class AdminRepository extends BaseRepository
{
/**
     * AdminRepository constructor.
     * @param Admin $model
     */
    public function __construct(Admin $model)
    {
        parent::__construct($model);
    }



}
