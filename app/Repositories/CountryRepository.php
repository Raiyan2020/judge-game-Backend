<?php

namespace App\Repositories;

use App\Models\Country;

class CountryRepository extends BaseRepository
{
    /**
     * CountryRepository constructor.
     * @param Country $model
     */
    public function __construct(Country $model)
    {
        parent::__construct($model);
    }
    public function index()
    {
        return $this->model->active()->get();
    }
    public function modelForSelect()
    {
        return $this->model->latest()->pluck('name', 'id');
    }

    public function getCountryByCode($code)
    {
        return $this->model->where('country_code',$code)->first();
    }
}
