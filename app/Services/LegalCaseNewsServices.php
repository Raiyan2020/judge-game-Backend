<?php

namespace App\Services;

use App\Repositories\LegalCaseNewsRepository;


class LegalCaseNewsServices
{
    public function __construct(protected LegalCaseNewsRepository $repo) {}

    public function index($filters)
    {
        return $this->repo->index($filters);
    }


   
}
