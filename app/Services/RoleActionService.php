<?php

namespace App\Services;

use App\Repositories\RoleActionRepository;

class RoleActionService {


    public function __construct(protected RoleActionRepository $repo)
    {
    }

    public function getRoles()
    {
        return $this->repo->getRoles();
    }

    public function getActionsByRole($role)
    {
        return $this->repo->getActionsByRole($role);
    }

    public function updatePoints($request)
    {
        foreach($request['actions'] as $action)
            {
                $this->repo->updatePoint($action['id'] , $action['points']);
            }
    }

    /**
     * Role owning a points submission: the posted role when it is present,
     * otherwise the role of the first saved action.
     *
     * @param  array<string, mixed>  $data
     */
    public function resolveRole(array $data): ?string
    {
        $role = $data['role'] ?? null;

        if (is_string($role) && $role !== '') {
            return $role;
        }

        $actionId = $data['actions'][0]['id'] ?? null;

        if (! $actionId) {
            return null;
        }

        return $this->repo->find($actionId)?->role;
    }

   


}
