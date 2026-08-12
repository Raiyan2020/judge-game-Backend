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
            $setting = $this->model->where('name', $key)->first();
            if (!$setting || $setting->type === 'file') {
                continue;
            }

            if (!$value) {
                continue;
            }

            if (!isset($value['ar'])) {
                $value['ar'] = $value['en'];
            }

            $this->model->where(['name' => $key])->update(['value' => $value]);
        }

        foreach ($this->model->where('type', 'file')->get() as $setting) {
            if (!request()->hasFile($setting->name)) {
                continue;
            }

            $path = uploader(request()->file($setting->name));
            $this->model->where('name', $setting->name)->update([
                'value' => ['en' => $path, 'ar' => $path],
            ]);
        }

        return true;
    }

    public function getPagesSetting($page)
    {
         return $this->model->wherePage($page)->get();
    }


}
