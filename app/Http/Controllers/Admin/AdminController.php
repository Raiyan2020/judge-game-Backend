<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin;
use Illuminate\Http\Request;
use App\Services\RoleService;
use App\Services\AdminService;
use App\DataTables\AdminDataTable;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Admin\StoreRequest;
use App\Http\Requests\Admin\Admin\UpdateRequest;


class AdminController extends Controller
{


    public function __construct(protected AdminService $adminService )
    {
        
    }

    /**
     * Display a listing of the resource.
     */
    public function index(AdminDataTable $dataTable)
    {
        return $dataTable->render('dashboard.admins.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.admins.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
       
         $this->adminService->create($request->validated());
         added();
        return redirect()->route('admin.admins.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Admin $admin)
    {      
        return view('dashboard.admins.edit', ['admin' => $admin]);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Admin $admin)
    {
        $this->adminService->update($admin , $request->validated());
        updated();
        return redirect()->route('admin.admins.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Admin $admin)
    {
        $this->adminService->delete($admin);
        deleted();
        return back();
    }

   
}
