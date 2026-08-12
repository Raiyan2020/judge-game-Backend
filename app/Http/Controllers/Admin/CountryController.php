<?php

namespace App\Http\Controllers\Admin;

use App\Models\Country;
use Illuminate\Http\Request;
use App\Services\CountryService;
use App\DataTables\CountryDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Country\StoreRequest;

class CountryController extends Controller
{

    public function __construct(protected CountryService $countryService)
    {
    }
    /**
     * Display a listing of the resource.
     */
    public function index(CountryDataTable $dataTable)
    {
        return $dataTable->render('dashboard.countries.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.countries.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        $this->countryService->create($request->validated());
        added();
        return redirect()->route('admin.countries.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Country $country)
    {
        return view('dashboard.countries.show', ['country' => $country]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Country $country)
    {
        return view('dashboard.countries.edit', ['country' => $country]);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreRequest $request, Country $country)
    {
        $this->countryService->update($country , $request->validated());
        updated();
        return redirect()->route('admin.countries.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Country $country)
    {
        $this->countryService->delete($country);
        deleted();
        return back();
    }

    public function changeStatus(Country $country)
    {
        $this->countryService->activation($country);
        statusChange();
        return back();
    }
}
