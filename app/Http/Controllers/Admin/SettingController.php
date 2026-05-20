<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Services\SettingService;
use App\Http\Controllers\Controller;

class SettingController extends Controller
{

    public function __construct(protected SettingService $settingService)
    {

    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $settings = $this->settingService->getPages();
        
        return view('dashboard.settings.index',compact('settings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
          $data = $request->except('_token');
          $this->settingService->create($data);
          updated();
          return redirect()->route('admin.settings.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $settings = $this->settingService->getPagesSetting($id);
        $settings_page = $id;
        return view('dashboard.settings.form',compact('settings_page','settings'));

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
