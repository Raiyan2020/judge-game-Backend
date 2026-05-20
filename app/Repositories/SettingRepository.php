<?php

namespace App\Repositories;
use App\Models\Setting;

class SettingRepository extends BaseRepository
{
/**
     * SettingRepository constructor.
     * @param Setting $model
     */
    public function __construct(Setting $model)
    {
        parent::__construct($model);
    }

    public function all()
    {
        return $this->model->pluck('value', 'name');
    }

    public function getSettingsByType($type)
    {
        return $this->model->whereSlug($type)->pluck('value', 'name');
    }
    public function getSettingPages()
    {
        return $this->model->pluck('page')->unique();
    }
    public function getSettingByPage($type)
    {
        return $this->model->whereSlug($type)->get();
    }
    public function getSettingByName($value)
    {
        return $this->model->whereName($value)->first();
    }
    public function store($data)
    {
        foreach ($data as $key => $value) {
            if(!$value ) continue;
            {
                if(!isset($value['ar'])) $value['ar'] = $value['en'];
                $this->model->where(['name'=> $key])->update(["value"=>$value]);
            }
        }
        return true;
    }

    public function getPagesSetting($page)
    {
         return $this->model->wherePage($page)->get();
    }


}
