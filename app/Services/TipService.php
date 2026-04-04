<?php

namespace App\Services;

use App\Repositories\TipRepository;

class TipService
{
    public function __construct(protected TipRepository $repo)
    {
    }

    public function index()
    {
        return $this->repo->index();
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
