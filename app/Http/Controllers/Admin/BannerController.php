<?php

namespace App\Http\Controllers\Admin;

use App\Models\Banner;
use Illuminate\Http\Request;
use App\Services\BannerService;
use App\DataTables\BannerDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Banner\StoreRequest;

class BannerController extends Controller
{

    public function __construct(protected BannerService $bannerService)
    {
    }
    /**
     * Display a listing of the resource.
     */
    public function index(BannerDataTable $dataTable )
    {
        return $dataTable->render('dashboard.banners.index');

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.banners.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        $this->bannerService->create($request->validated());
        added();
        return redirect()->route('admin.banners.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Banner $banner)
    {
        return view('dashboard.banners.show', ['banner' => $banner]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Banner $banner)
    {
        return view('dashboard.banners.edit', ['banner' => $banner]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreRequest $request, Banner $banner)
    {
        $this->bannerService->update($banner , $request->validated());
        updated();
        return redirect()->route('admin.banners.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Banner $banner)
    {
        $this->bannerService->delete($banner);
        deleted();
        return back();
    }

    public function changeStatus(Banner $banner)
    {
        $this->bannerService->activation($banner);
        statusChange();
        return back();
    }

   

}
