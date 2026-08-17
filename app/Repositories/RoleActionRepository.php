<?php

namespace App\Repositories;
use App\Models\RoleAction;

class RoleActionRepository extends BaseRepository
{
/**
     * RoleActionRepository constructor.
     * @param RoleAction $model
     */
    public function __construct(RoleAction $model)
    {
        parent::__construct($model);
    }

    public function getRoles()
    {
        return $this->model->distinct()->pluck('role');
    }

    public function getActionsByRole($role)
    {
        return $this->model->where('role',$role)->get();
    }

    public function updatePoint($id, $points)
    {
        $points = max(0, min((int) $points, RoleAction::MAX_POINTS));

        $this->model->where('id', $id)->update(['points' => $points]);
    }

 
  

}
