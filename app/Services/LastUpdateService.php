<?php

namespace App\Services;

use App\Repositories\LastUpdateRepository;

class LastUpdateService
{
    public function __construct(protected LastUpdateRepository $repo)
    {
    }

    public function index($limit = null)
    {
        return $this->repo->index($limit);
    }


    public function create($request)
    {
        return $this->repo->create($request);
    }

    public function update($model, $request)
    {
        return $this->repo->update($model, $request);
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