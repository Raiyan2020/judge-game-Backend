<?php

namespace App\Services;

use App\Repositories\SettingRepository;

class SettingService {


    public function __construct(protected SettingRepository $repo)
    {
    }

    public function getAllSettings()
    {
        return $this->repo->all();
    }

    public function getSettingsByType($type)
    {
        return $this->repo->getSettingsByType($type);
    }

    public function getPages()
    {
        return $this->repo->getSettingPages();
    }
    public function getSettingByPage($id)
    {
        return $this->repo->getSettingByPage($id);
    }
    public function getSettingByName($value)
    {
        return $this->repo->getSettingByName($value);

    }
    public function create($data)
    {
       return  $this->repo->store($data);
    }


}
