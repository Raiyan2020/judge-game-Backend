<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\RoleTitleDataTable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleTitle\StoreRequest;
use App\Models\RoleTitle;
use App\Services\RoleTitleService;

class RoleTitleController extends Controller
{

    public function __construct(protected RoleTitleService $roleTitleService) {}
    /**
     * Display a listing of the resource.
     */
    public function index(RoleTitleDataTable $dataTable)
    {
        return $dataTable->render('dashboard.role-titles.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $roles = $this->roleTitleService->getRoles();
        return view('dashboard.role-titles.create', ['roles' => $roles]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        $this->roleTitleService->create($request->validated());
        added();
        return redirect()->route('admin.role-titles.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(RoleTitle $roleTitle)
    {
        $roleTitle->load('requirements.action');

        return view('dashboard.role-titles.show', ['roleTitle' => $roleTitle]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RoleTitle $roleTitle)
    {
        $roles = $this->roleTitleService->getRoles();

        return view('dashboard.role-titles.edit', ['roleTitle' => $roleTitle , 'roles'=>$roles]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreRequest $request, RoleTitle $roleTitle)
    {
        $this->roleTitleService->update($roleTitle, $request->validated(),);
        updated();
        return redirect()->route('admin.role-titles.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RoleTitle $roleTitle)
    {
        $this->roleTitleService->delete($roleTitle);
        deleted();
        return back();
    }

   
}
