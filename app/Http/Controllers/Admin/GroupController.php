<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\GroupDataTable;
use App\Http\Controllers\Controller;

class GroupController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(GroupDataTable $dataTable)
    {
        return $dataTable->render('dashboard.groups.index');
    }

   
   

}
