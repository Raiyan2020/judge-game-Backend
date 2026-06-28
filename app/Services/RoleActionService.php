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

   


}
