<?php

namespace App\Repositories;

use App\Models\Contact;

class ContactRepository extends BaseRepository
{
    /**
     * ContactRepository constructor.
     * @param Contact $model
     */
    public function __construct(Contact $model)
    {
        parent::__construct($model);
    }

    public function index()
    {
        return $this->model->latest()->get();
    }
}