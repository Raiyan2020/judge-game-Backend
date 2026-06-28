<?php

namespace App\Services;

use App\Enums\GroupRole;
use App\Repositories\RoleTitleRepository;

class RoleTitleService
{


    public function __construct(protected RoleTitleRepository $repo) {}

    public function getRoles(): array
    {
        return collect(GroupRole::cases())
            ->mapWithKeys(fn($role) => [
                $role->value => __($role->value)
            ])
            ->toArray();
    }
    public function create($request)
    {
        $title =  $this->repo->create($request);
        $title->requirements()->createMany($request['actions']);
        return $title;
    }

    public function update($model, $request)
    {
       
        $title = $this->repo->update($model, $request);
        $model->requirements()->delete();
        $model->requirements()->createMany($request['actions']);
        return $model;

    }

    public function delete($model)
    {
        return $this->repo->delete($model);
    }
}
