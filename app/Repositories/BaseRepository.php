<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseRepository
{
    protected Model $model;
    protected Builder $query;

    /**
     * BaseRepository constructor.
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->query = $model->query();
        $this->model = $model;

    }

    public function all()
    {
        return $this->model->latest()->get();
    }

    public function create(array $attributes = [])
    {
        if (!empty($attributes)) {
            
            $model = $this->model->create($attributes);
            return $model;
        }
        return false;
    }

    public function findBy(string $key , $value)
    {
        $model = $this->query->where($key, $value);
        return $model->first();
    }

    public function find($id)
    {
        return  $this->model->find($id);
    }

    public function first($key , $value)
    {
        return $this->model->where($key , $value)->first();
    }
    
    public function update(Model $model, array $attributes = [])
    {
         if (!empty($attributes)) {
            $model = tap($model)->update($attributes)->fresh();
            return $model;
        }
        return false;
    }

    public function delete(Model $model)
    {
        $model->delete();
        return true;
    }

    public function modelForSelect()
    {
        return $this->model->active()->latest()->pluck('name', 'id');
    }

    public function activation(Model $model)
    {
        $status = $model->is_active == 1 ? 0 : 1;
        $model->update(['is_active'=>$status]);
        return true;
    }
  

 

   }
