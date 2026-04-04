<?php

namespace App\Services;

use App\Repositories\ContactRepository;

class ContactService
{
    public function __construct(protected ContactRepository $repo)
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
}