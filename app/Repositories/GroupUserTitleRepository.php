<?php

namespace App\Repositories;

use App\Models\GroupUserTitle;

class GroupUserTitleRepository extends BaseRepository
{
    public function __construct(GroupUserTitle $model)
    {
        parent::__construct($model);
    }
}
