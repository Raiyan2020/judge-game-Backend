<?php

namespace App\Repositories;

use App\Models\Room;

class RoomRepository extends BaseRepository
{
    /**
     * RoomRepository constructor.
     * @param Room $model
     */
    public function __construct(Room $model)
    {
        parent::__construct($model);
    }

    public function index($request = [])
    {
        return $this->model
        ->with('users', 'group', 'admin')
        ->withCount('users')
        ->when(isset($request['group_id']), function($query) use ($request) {
            $query->where('group_id', $request['group_id']);
        })
        ->latest()
        ->get();
    }

  
}
