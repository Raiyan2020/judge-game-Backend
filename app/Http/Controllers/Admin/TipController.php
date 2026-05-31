<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\TipDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tip\StoreRequest;
use App\Models\Tip;
use App\Services\TipService;

class TipController extends Controller
{

    public function __construct(protected TipService $tipService)
    {
    }
    /**
     * Display a listing of the resource.
     */
    public function index(TipDataTable $dataTable )
    {
        return $dataTable->render('dashboard.tips.index');

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.tips.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        $this->tipService->create($request->validated());
        added();
        return redirect()->route('admin.tips.index');
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
    public function edit(Tip $tip)
    {
        return view('dashboard.tips.edit', ['tip' => $tip]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreRequest $request, Tip $tip)
    {
        $this->tipService->update($tip , $request->validated());
        updated();
        return redirect()->route('admin.tips.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tip $tip)
    {
        $this->tipService->delete($tip);
        deleted();
        return back();
    }

    public function changeStatus(Tip $tip)
    {
        $this->tipService->activation($tip);
        statusChange();
        return back();
    }

   

}
