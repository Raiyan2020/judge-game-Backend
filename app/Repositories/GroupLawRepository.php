<?php

namespace App\Repositories;

use App\Models\GroupLaw;

class GroupLawRepository extends BaseRepository
{
    public function __construct(GroupLaw $model)
    {
        parent::__construct($model);
    }

    public function getByGroup($groupId)
    {
        return $this->model->where('group_id', $groupId)->get();
    }

   
}