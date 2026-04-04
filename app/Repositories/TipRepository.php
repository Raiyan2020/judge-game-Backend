<?php

namespace App\Repositories;

use App\Models\Tip;

class TipRepository extends BaseRepository
{
    /**
     * TipRepository constructor.
     * @param Tip $model
     */
    public function __construct(Tip $model)
    {
        parent::__construct($model);
    }

    public function index()
    {
        return $this->model->active()->latest()->get();
    }
}
