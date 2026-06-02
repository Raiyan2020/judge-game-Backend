<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\PackageSubscriptionDataTable;
use App\Http\Controllers\Controller;
use App\Services\PackageService;

class PackageSubscriptionController extends Controller
{

    public function __construct(protected PackageService $packageService){}
    /**
     * Display a listing of the resource.
     */
    public function index(PackageSubscriptionDataTable $dataTable)
    {
        return $dataTable->render('dashboard.packages-subscription.index');
    }

   
   

}
