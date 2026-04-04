<?php

namespace App\Repositories;

use App\Models\LastUpdate;

class LastUpdateRepository extends BaseRepository
{
    /**
     * LastUpdateRepository constructor.
     * @param LastUpdate $model
     */
    public function __construct(LastUpdate $model)
    {
        parent::__construct($model);
    }

    public function index($limit = null)
    {
        $query = $this->model->active()->latest();
        return $limit ? $query->limit($limit)->get() : $query->get();
    }
}