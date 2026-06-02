<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\PackageSubscriptionDataTable;
use App\Http\Controllers\Controller;

class PackageSubscriptionController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(PackageSubscriptionDataTable $dataTable)
    {
        return $dataTable->render('dashboard.packages-subscription.index');
    }



}
