<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\LastUpdateDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LastUpdate\StoreRequest;
use App\Models\LastUpdate;
use App\Services\LastUpdateService;

class LastUpdateController extends Controller
{

    public function __construct(protected LastUpdateService $lastUpdateService)
    {
    }
    /**
     * Display a listing of the resource.
     */
    public function index(LastUpdateDataTable $dataTable )
    {
        return $dataTable->render('dashboard.last-updates.index');

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.last-updates.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        $this->lastUpdateService->create($request->validated());
        added();
        return redirect()->route('admin.last-updates.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(LastUpdate $lastUpdate)
    {
        return view('dashboard.last-updates.show', ['lastUpdate' => $lastUpdate]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LastUpdate $lastUpdate)
    {
        return view('dashboard.last-updates.edit', ['lastUpdate' => $lastUpdate]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreRequest $request, LastUpdate $lastUpdate)
    {
        $this->lastUpdateService->update($lastUpdate, $request->validated());
        updated();
        return redirect()->route('admin.last-updates.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LastUpdate $lastUpdate)
    {
        $this->lastUpdateService->delete($lastUpdate);
        deleted();
        return back();
    }

    public function changeStatus(LastUpdate $lastUpdate)
    {
        $this->lastUpdateService->activation($lastUpdate);
        statusChange();
        return back();
    }

   

}
