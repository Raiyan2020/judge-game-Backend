<?php

namespace App\Http\Controllers\Admin;

use App\Models\Package;
use App\Services\PackageService;
use App\DataTables\PackageDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Package\StoreRequest;

class PackageController extends Controller
{

    public function __construct(protected PackageService $packageService){}
    /**
     * Display a listing of the resource.
     */
    public function index(PackageDataTable $dataTable)
    {
        return $dataTable->render('dashboard.packages.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.packages.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        $this->packageService->create($request->validated());
        added();
        return redirect()->route('admin.packages.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Package $package)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Package $package)
    {
        return view('dashboard.packages.edit', ['package' => $package]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreRequest $request, Package $package)
    {
        $this->packageService->update($package , $request->validated());
        updated();
        return redirect()->route('admin.packages.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Package $package)
    {
        $this->packageService->delete($package);
        deleted();
        return back();
    }

    public function changeStatus(Package $package)
    {
        $this->packageService->activation($package);
        statusChange();
        return back();
    }

}
